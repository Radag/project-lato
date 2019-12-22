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
        $this->presenter->flashMessage("Opustil jste skupinu " . $this->presenter->activeGroup->name);
        $this->presenter->redirect(':Front:Homepage:noticeboard');
    }
    
    public function handleArchiveGroup()
    {
        if($this->presenter->activeGroup->relation === GroupManager::RELATION_OWNER) {
            $this->groupManager->archiveGroup($this->presenter->activeGroup->id);
            $this->presenter->flashMessage("Archivoval jste skupinu " . $this->presenter->activeGroup->name);
            $this->presenter->redirect(':Front:Homepage:noticeboard');
        }
    }
    
    public function handleUnarchiveGroup()
    {
        if($this->presenter->activeGroup->relation === GroupManager::RELATION_OWNER) {
            $this->groupManager->unarchiveGroup($this->presenter->activeGroup->id);
            $this->presenter->flashMessage("Obnovil jste skupinu " . $this->presenter->activeGroup->name);
            $this->presenter->redirect('this');
        }
    }
    
    public function handleDeleteGroup()
    {
        if($this->presenter->activeGroup->relation === GroupManager::RELATION_OWNER) {
            $this->groupManager->deleteGroup($this->presenter->activeGroup->id);
            $this->presenter->flashMessage("Smazal jste skupinu " . $this->presenter->activeGroup->name);
            $this->presenter->redirect(':Front:Homepage:noticeboard');
        }
    }
}
