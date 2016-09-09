<?php

namespace App\FrontModule\Presenters;

use App\Model\Manager\UserManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\PrivateMessageManager;
use App\Model\Manager\NotificationManager;
use App\Model\Manager\SchedulelManager;
use App\Model\Manager\TaskManager;

class HomepagePresenter extends BasePresenter
{
    protected $groupManager;
    protected $userManager;
    protected $privateMessageManager;
    protected $notificationManager;
    protected $scheduleManger;
    protected $taskManager;


    public function __construct(
        UserManager $userManager,
        GroupManager $groupManager,
        PrivateMessageManager $privateMessageManager,
        NotificationManager $notificationManager,
        SchedulelManager $scheduleManger,
        TaskManager $taskManager
    )
    {
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->privateMessageManager = $privateMessageManager;
        $this->notificationManager = $notificationManager;
        $this->scheduleManger = $scheduleManger;
        $this->taskManager = $taskManager;
    }
    
    public function actionDefault()
    {
        if($this->user->isLoggedIn()) {
            $this->redirect(':Front:Homepage:groups');
        }
    }
    
    public function actionLogout()
    {
        $this->user->logout(true);
        $this->redirect(':Public:Homepage:default');
    }
    
    public function actionGroups()
    {
        $this['topPanel']->setTitle('Nástěnka');
        
        $groups = $this->groupManager->getUserGroups($this->activeUser);
        
        $this->template->groups = $this->groupManager->getGroups($this->activeUser);;
        $this->template->todaySchedule = $this->scheduleManger->getTodaySchedule($groups);
        $this->template->days = array('Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle');
        $this->template->actualTasks = $this->taskManager->getClosestTask($groups);
        \Tracy\Debugger::barDump($this->taskManager->getClosestTask($groups));
    }
    
    public function actionTasks()
    {
        $this['topPanel']->setTitle('Povinnosti');
    }
    
    public function actionTimetable()
    {
        $this['topPanel']->setTitle('Rozvrh');
    }
    
}
