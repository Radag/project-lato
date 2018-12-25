<?php

namespace App\FrontModule\Components\Group\About;

use App\Model\Manager\GroupManager;
use App\Model\Manager\NotificationManager;

class AboutGroup extends \App\Components\BaseComponent
{
        
    /** @var GroupManager @inject */
    public $groupManager;
    
    /** @var NotificationManager @inject */
    public $notificationManager;
    
    public function __construct(
        GroupManager $groupManager,
        NotificationManager $notificationManager
    )
    {
        $this->groupManager = $groupManager;
        $this->notificationManager = $notificationManager;
    }    
    
    public function render() 
    {
        $this->template->periods = $this->groupManager->getGroupPeriods($this->presenter->activeGroup);
        $this->template->activeGroup = $this->presenter->activeGroup;
        parent::render();
    }
    
    public function handleLeaveGroup()
    {
        $this->groupManager->removeUserFromGroup($this->presenter->activeGroup, $this->presenter->activeUser);
        $this->notificationManager->addLeftGroup($this->presenter->activeUser, $this->presenter->activeGroup);
        $this->flashMessage("Opustil jste skupinu " . $this->presenter->activeGroup->name);
        $this->presenter->redirect(':Front:Homepage:noticeboard');
    }
}
