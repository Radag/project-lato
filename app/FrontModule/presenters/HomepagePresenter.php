<?php

namespace App\FrontModule\Presenters;

use App\Model\Manager\UserManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\PrivateMessageManager;
use App\Model\Manager\NotificationManager;
use App\Model\Manager\SchedulelManager;
use App\Model\Manager\TaskManager;
use App\Model\Manager\NoticeManager;
use App\Model\Manager\ClassificationManager;
use App\FrontModule\Components\TaskHeader\ITaskHeader;
use App\FrontModule\Components\NewNoticeForm\NewNoticeForm;
use App\FrontModule\Components\Stream\ICommitTaskFormFactory;


class HomepagePresenter extends BasePresenter
{
    protected $groupManager;
    protected $userManager;
    protected $privateMessageManager;
    protected $notificationManager;
    protected $scheduleManger;
    protected $taskManager;
    protected $noticeManager;
    protected $classificationManager;
    protected $taskHeaderFactory;
    protected $days = array('Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle');
    
    protected $tasks = array();
    /**
     * @var ICommitTaskFormFactory
     */
    public $commitTaskFormFactory;
    

    public function __construct(
        UserManager $userManager,
        GroupManager $groupManager,
        PrivateMessageManager $privateMessageManager,
        NotificationManager $notificationManager,
        SchedulelManager $scheduleManger,
        TaskManager $taskManager,
        NoticeManager $noticeManager,
        ClassificationManager $classificationManager,
        ITaskHeader $taskHeader,
        ICommitTaskFormFactory $commitTaskFormFactory
    )
    {
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->privateMessageManager = $privateMessageManager;
        $this->notificationManager = $notificationManager;
        $this->scheduleManger = $scheduleManger;
        $this->taskManager = $taskManager;
        $this->noticeManager = $noticeManager;
        $this->classificationManager = $classificationManager;
        $this->taskHeaderFactory = $taskHeader;
        $this->commitTaskFormFactory = $commitTaskFormFactory;
    }
    
    public function actionDefault()
    {
        if($this->user->isLoggedIn()) {
            $this->redirect(':Front:Homepage:noticeboard');
        }
    }
    
    public function actionClassification()
    {
        $this['topPanel']->setTitle('Klasifikace');
        $this->template->schoolPeriods = $this->classificationManager->getSchoolPeriods();
        $this->template->activePeriod = $this->activePeriod;
        $myClassification = $this->classificationManager->getMyClassification($this->activeUser, $this->activePeriod);
        $this->template->myClassification = $myClassification;
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
        
        $this->template->groups = $this->groupManager->getGroups($this->activeUser);
        $todaySchedule = $this->scheduleManger->getTodaySchedule($groups);
        
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
        
        $this->template->days = $this->days;
        $this->tasks = $this->taskManager->getClosestTask($groups);
        $this->template->actualTasks = $this->tasks;
        $this->template->actualNotices = $this->noticeManager->getNotices($this->activeUser, 3);
        $this->template->activeUser = $this->activeUser;
    }
    
    public function actionTasks()
    {
        $this['topPanel']->setTitle('Povinnosti');
        $groups = $this->groupManager->getUserGroups($this->activeUser);
        $this->tasks = $this->taskManager->getClosestTask($groups);
        $this->template->tasks = $this->tasks;
        $this->template->activeUser = $this->activeUser; 
    }
    
    public function createComponentTaskHeader()
    {
        return new \Nette\Application\UI\Multiplier(function ($idTask) {
            $taskHeader = $this->taskHeaderFactory->create();
            $taskHeader->setTask($this->tasks[$idTask]);
            return $taskHeader;
        });
    }
    
    public function actionNotices()
    {
        $this['topPanel']->setTitle('Poznámky');
        $this->template->notices = $this->noticeManager->getNotices($this->activeUser, 10);
    }
    
    public function actionTimetable()
    {
        $this['topPanel']->setTitle('Rozvrh');
        $groups = $this->groupManager->getUserGroups($this->activeUser);
        $schedule = $this->scheduleManger->getWeekSchedule($groups);
        
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
       
    protected function createComponentNoticeForm()
    {
        return new NewNoticeForm($this->noticeManager, $this->activeUser);
    }
    
    protected function createComponentCommitTaskForm()
    {
        $form = $this->commitTaskFormFactory->create();                
        $form->setActiveUser($this->presenter->activeUser);
        return $form;
    }
}
