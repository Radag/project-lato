<?php

namespace App\FrontModule\Presenters;

use App\Model\Manager\GroupManager;
use App\Model\Manager\SchedulelManager;
use App\FrontModule\Components\SearchForm;
use App\Model\Manager\TaskManager;
use App\Model\Manager\SearchManager;
use App\FrontModule\Components\TaskHeader\ITaskHeader;
use App\FrontModule\Components\Stream\ICommitTaskFormFactory;
use App\FrontModule\Components\Task\TaskCard;
use App\Model\Manager\ClassificationManager;

class HomepagePresenter extends BasePresenter
{
    /** @var GroupManager @inject */
    public $groupManager;
    
    /** @var SchedulelManager @inject */
    public $scheduleManger;
    
    /** @var SearchManager @inject */
    public $searchManager;
    
    /** @var TaskManager @inject */
    public $taskManager;
    
    /** @var ClassificationManager @inject */
    public $classificationManager;
        
    /** @var ITaskHeader @inject */
    public $taskHeaderFactory;
    
    /** @var ICommitTaskFormFactory @inject */
    public $commitTaskForm; 
        
    protected $days = ['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle'];
    
    protected $tasks = [];

    public function actionDefault()
    {
        if($this->user->isLoggedIn()) {
            $this->redirect(':Front:Homepage:noticeboard');
        }
    }
    
    public function actionClassification()
    {
        $this['topPanel']->setTitle('Klasifikace');
        $this->template->myClassification = $this->classificationManager->getMyClassification($this->activeUser);;
    }
    
    public function actionLogout()
    {
        $this->user->logout(true);
        $this->redirect(':Public:Homepage:default');
    }
    
    public function actionNoticeboard()
    {        
        $this['topPanel']->setTitle('Nástěnka');
        
        $groups = $this->groupManager->getUserGroups($this->activeUser);
        $todaySchedule = $this->scheduleManger->getTodaySchedule($groups->groups);
        
        $maxHour = 0;
        $minHour = 24;
        
        foreach($todaySchedule as $hour) {
            if($maxHour < $hour->TIME_FROM->format("%H")) {
                $maxHour = $hour->TIME_FROM->format("%H");
            }
            if($minHour > $hour->TIME_FROM->format("%H")) {
                $minHour = $hour->TIME_FROM->format("%H");
            }
        }
        
        $this->template->maxHour = $maxHour;
        $this->template->minHour = $minHour;
        $this->template->todaySchedule = $todaySchedule;
        $this->template->groups = $groups;
        $this->template->days = $this->days;
        $this->tasks = $this->taskManager->getClosestTask($groups->groups, true, $this->activeUser);
        $this->template->actualTasks = $this->tasks;
        //$this->template->actualNotices = $this->noticeManager->getNotices($this->activeUser, 3);
        $this->template->activeUser = $this->activeUser;
    }
    
    public function actionTasks($group)
    {
        $this['topPanel']->setTitle('Povinnosti');
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
            $this->tasks = $this->taskManager->getClosestTask($selectGroups, false, $this->presenter->activeUser);
        } else {
            $this->tasks = $this->taskManager->getClosestTask($selectGroups, true, $this->presenter->activeUser);
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
    
    public function actionNotices()
    {
        $this['topPanel']->setTitle('Poznámky');
        $this->template->notices = $this->noticeManager->getNotices($this->activeUser, 10);
    }
    
    public function actionTimetable()
    {
        $this['topPanel']->setTitle('Rozvrh');
        $groups = [];//$this->groupManager->getUserGroups($this->activeUser, true);
        $schedule = [];//$this->scheduleManger->getWeekSchedule($groups);
        
        $maxHour = 0;
        $minHour = 24;
        foreach($schedule as $day) {
            foreach($day as $hour) {
                if($maxHour < $hour->TIME_FROM->format("%H")) {
                    $maxHour = $hour->TIME_FROM->format("%H");
                }
                if($minHour > $hour->TIME_FROM->format("%H")) {
                    $minHour = $hour->TIME_FROM->format("%H");
                }
            }
        }
        
        $this->template->maxHour = $maxHour;
        $this->template->minHour = $minHour;
        $this->template->schedule = $schedule;
        $this->template->days = $this->days;
        
    }    
        
    protected function createComponentTaskCard()
    {
        return new TaskCard();
    }  
    
    public function redrawTasks() {
        $groups = $this->groupManager->getUserGroups($this->activeUser);
        $this->tasks = $this->taskManager->getClosestTask($groups);
        $this->redrawControl('actualTasks');
    }
        
    protected function createComponentSearchForm()
    {
        $form = new SearchForm($this->searchManager); 
        return $form;
    }
}
