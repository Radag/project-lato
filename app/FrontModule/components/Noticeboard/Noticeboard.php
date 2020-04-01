<?php

namespace App\FrontModule\Components;

use App\Model\Manager\GroupManager;
use App\Model\Manager\ScheduleManager;
use App\Model\Manager\TaskManager;
use App\FrontModule\Components\Stream\ICommitTaskForm;
use App\FrontModule\Components\Noticeboard\ITask;

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

    /** @var ITask */
    public $task;
    
    protected $tasks = [];
    
    public function __construct(
        GroupManager $groupManager,
        ScheduleManager $scheduleManager,
        TaskManager $taskManager,            
        ICommitTaskForm $commitTaskForm,
        ITask $task
    )
    {
        $this->groupManager = $groupManager;
        $this->scheduleManager = $scheduleManager;
        $this->taskManager = $taskManager;
        $this->commitTaskForm = $commitTaskForm;
        $this->task = $task;
    }
    
    public function render() 
    {
        $groups = $this->groupManager->getUserGroups($this->presenter->activeUser);
//        $todaySchedule = $this->scheduleManager->getTodaySchedule($groups->groups);
//        
//        $maxHour = 0;
//        $minHour = 24;
//        
//        foreach($todaySchedule as $hour) {
//            if($maxHour < $hour->TIME_FROM->format("%H")) {
//                $maxHour = $hour->TIME_FROM->format("%H");
//            }
//            if($minHour > $hour->TIME_FROM->format("%H")) {
//                $minHour = $hour->TIME_FROM->format("%H");
//            }
//        }
        
//        $this->template->maxHour = $maxHour;
//        $this->template->minHour = $minHour;
//        $this->template->todaySchedule = $todaySchedule;
        $this->template->groups = $groups;
//        $this->template->days = $this->presenter->days;
        $this->tasks = $this->taskManager->getClosestTask($groups->groups, true, $this->presenter->activeUser);
        $this->template->actualTasks = $this->tasks;
        //$this->template->actualNotices = $this->noticeManager->getNotices($this->activeUser, 3);
        $this->template->activeUser = $this->presenter->activeUser;
        parent::render();
    } 
    
    public function redrawTasks() 
    {
        $this->redrawControl('actualTasks');
    }
    
        
    public function createComponentTaskCard()
    {
        return new \Nette\Application\UI\Multiplier(function ($idTask) {
            $task = $this->task->create();
            if(isset($this->tasks[$idTask])) {
                $taskData = $this->tasks[$idTask];
				$task->setTask($taskData);
				return $task;
            } else {
				return null;
			}
        });
    }
    
    public function createComponentCommitTaskForm()
    {
        return $this->commitTaskForm->create();
    }
}
