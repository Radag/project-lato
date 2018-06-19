<?php

namespace App\FrontModule\Presenters;

use App\Model\Manager\GroupManager;
use App\Model\Manager\TaskManager;
use App\Model\Manager\SearchManager;
use App\FrontModule\Components\TaskHeader\ITaskHeader;
use App\FrontModule\Components\Stream\ICommitTaskFormFactory;
use App\FrontModule\Components\Task\TaskCard;

class TaskPresenter extends BasePresenter
{
    /** @var GroupManager @inject */
    public $groupManager;
    
    /** @var SearchManager @inject */
    public $searchManager;
    
    /** @var TaskManager @inject */
    public $taskManager;
        
    /** @var ITaskHeader @inject */
    public $taskHeaderFactory;
    
    /** @var ICommitTaskFormFactory @inject */
    public $commitTaskForm; 
        
    protected $days = ['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle'];
    
    protected $tasks = [];
   
    public function startup() {
        parent::startup();
        $groups = $this->groupManager->getUserGroups($this->activeUser);
        $tasks = $this->taskManager->getTaskStats($groups->groups, $this->presenter->activeUser);
        $this['topPanel']->setTitle('Připomenutí');
        $different = ($tasks->tasks - $tasks->owner) !== 0;
        if($different && ($tasks->tasks > 0 && $tasks->owner > 0)) {
            $this['topPanel']->addToMenu((object)['name' => 'Povinnosti', 'link' => $this->link('default'), 'active' => $this->isLinkCurrent('default')]);
            $this['topPanel']->addToMenu((object)['name' => 'Práce', 'link' => $this->link('work'), 'active' => $this->isLinkCurrent('work')]);
        } else {
            if(!$this->isLinkCurrent('Task:work') && empty($tasks->tasks) && !empty($tasks->owner)) {
                $this->redirect('Task:work');
            } elseif(!$this->isLinkCurrent('Task:default') && !empty($tasks->tasks) && empty($tasks->owner)) {
                $this->redirect('Task:default');
            }
        }
    }
    
    protected function loadTask($group, $work)
    {
        $groups = $this->groupManager->getUserGroups($this->activeUser);
        if($group) {
            foreach($groups->groups as $gr) {
                if($gr->slug === $group) {
                    $selectGroups[$gr->id] = $gr;
                }
            }
        } else {
            $selectGroups = $groups->groups;
        }
        if($this->getParameter('filter') == 'closed') {
            $this->tasks = $this->taskManager->getClosestTask($selectGroups, false, $this->presenter->activeUser, $work);
        } else {
            $this->tasks = $this->taskManager->getClosestTask($selectGroups, true, $this->presenter->activeUser, $work);
        }
        
        $tasks = [];
        foreach($this->tasks as $task) {
            $diff = (new \DateTime())->diff($task->deadline);
            $diffDays = (integer)$diff->format( "%R%a" );
            if($diffDays < -30) {
                if(!isset($tasks['last_month'])) {
                    $tasks['last_month'] = (object)['tasks' => [], 'name' => 'Před měsícem']; 
                }
                $tasks['last_month']->tasks[] = $task;
            } elseif($diffDays < 0) {
                if(!isset($tasks['this_month'])) {
                    $tasks['this_month'] = (object)['tasks' => [], 'name' => 'Tento měsíc']; 
                }
                $tasks['this_month']->tasks[] = $task;
            } elseif($diffDays <= 7) {
                if(!isset($tasks['this_week'])) {
                    $tasks['this_week'] = (object)['tasks' => [], 'name' => 'Na tento týden']; 
                }
                $tasks['this_week']->tasks[] = $task;
            } elseif($diffDays <= 14) {
                if(!isset($tasks['next_week'])) {
                    $tasks['next_week'] = (object)['tasks' => [], 'name' => 'Na příští týden']; 
                }
                $tasks['next_week']->tasks[] = $task;
            } else {
                if(!isset($tasks['all'])) {
                    $tasks['all'] = (object)['tasks' => [], 'name' => 'Za déle než týden']; 
                }
                $tasks['all']->tasks[] = $task;
            }
        }
        return $tasks;
    }
    
    public function actionWork($group)
    {
        $tasks = $this->loadTask($group, true);
        $this->template->filter = $this->getParameter('filter');
        $this->template->tasks = $tasks;
        $this->template->activeUser = $this->activeUser; 
    }
    
    public function actionDefault($group)
    {
        $tasks = $this->loadTask($group, false);
        $this->template->filter = $this->getParameter('filter');
        $this->template->tasks = $tasks;
        $this->template->activeUser = $this->activeUser; 
    }
    
    public function createComponentTaskHeader()
    {
        return new \Nette\Application\UI\Multiplier(function ($idTask) {
            $taskHeader = $this->taskHeaderFactory->create();
            if(isset($this->tasks[$idTask])) {
                $task = $this->tasks[$idTask];
            } else {
                $task = $this->taskManager->getTask($idTask, $this->presenter->activeUser);
            }
            $taskHeader->setTask($task);
            $taskHeader->setCommitTaskForm($this['commitTaskForm']);
            return $taskHeader;
        });
    }
    
    public function createComponentCommitTaskForm()
    {
        return $this->commitTaskForm->create();
    }
    
    protected function createComponentTaskCard()
    {
        return new TaskCard();
    }  
    
    public function redrawTasks() {
        $groups = $this->groupManager->getUserGroups($this->activeUser);
        if($this->getParameter('filter') == 'closed') {
            $this->tasks = $this->taskManager->getClosestTask($groups->groups, false, $this->presenter->activeUser);
        } else {
            $this->tasks = $this->taskManager->getClosestTask($groups->groups, true, $this->presenter->activeUser);
        }
        $this->redrawControl('tasks');
    }
}
