<?php

namespace App\FrontModule\Components;

use App\Model\Manager\GroupManager;
use App\Model\Manager\ScheduleManager;
use App\Model\Manager\TaskManager;
use App\FrontModule\Components\Task\TaskCard;
use App\FrontModule\Components\Stream\ICommitTaskForm;
use App\FrontModule\Components\TaskHeader\ITaskHeader;

class Noticeboard extends \App\Components\BaseComponent
{
    /** @var GroupManager */
    public $groupManager;
    
    /** @var ScheduleManager */
    public $scheduleManager;
    
    /** @var TaskManager */
    public $taskManager;
       
    /** @var ICommitTaskForm */
    public $commitTaskForm;

    /** @var ITaskHeader */
    public $taskHeader;
    
    protected $tasks = [];
    
    public function __construct(
        GroupManager $groupManager,
        ScheduleManager $scheduleManager,
        TaskManager $taskManager,            
        ICommitTaskForm $commitTaskForm,
        ITaskHeader $taskHeader
    )
    {
        $this->groupManager = $groupManager;
        $this->scheduleManager = $scheduleManager;
        $this->taskManager = $taskManager;
        $this->commitTaskForm = $commitTaskForm;
        $this->taskHeader = $taskHeader;
    }
    
    public function render() 
    {
        $groups = $this->groupManager->getUserGroups($this->presenter->activeUser);
        $todaySchedule = $this->scheduleManager->getTodaySchedule($groups->groups);
        
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
        $this->template->days = $this->presenter->days;
        $this->tasks = $this->taskManager->getClosestTask($groups->groups, true, $this->presenter->activeUser);
        $this->template->actualTasks = $this->tasks;
        //$this->template->actualNotices = $this->noticeManager->getNotices($this->activeUser, 3);
        $this->template->activeUser = $this->presenter->activeUser;
        parent::render();
    } 
    
    public function redrawTasks() {
        $groups = $this->groupManager->getUserGroups($this->activeUser);
        if($this->getParameter('filter') == 'closed') {
            $this->tasks = $this->taskManager->getClosestTask($groups->groups, false, $this->presenter->activeUser);
        } else {
            $this->tasks = $this->taskManager->getClosestTask($groups->groups, true, $this->presenter->activeUser);
        }
        $this->redrawControl('actualTasks');
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
        
}
