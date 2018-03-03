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
        
    protected $days = array('Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle');
    
    protected $tasks = array();

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
        $this->tasks = $this->taskManager->getClosestTask($groups->groups);
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
        $this->tasks = $this->taskManager->getClosestTask($selectGroups);
        $this->template->tasks = $this->tasks;
        $this->template->activeUser = $this->activeUser; 
    }
    
    public function createComponentTaskHeader()
    {
        return new \Nette\Application\UI\Multiplier(function ($idTask) {
            $taskHeader = $this->taskHeaderFactory->create();
            $task = $this->taskManager->getTask($idTask, $this->presenter->activeUser);
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
