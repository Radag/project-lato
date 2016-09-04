<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\TopPanel;

use \Nette\Application\UI\Control;
use App\Model\Manager\UserManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\PrivateMessageManager;
use App\Model\Manager\NotificationManager;
use App\FrontModule\Components\NewGroupForm\NewGroupForm;

/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class TopPanel extends Control
{
    
    /**
     * @var UserManager $userManager
     */
    protected $userManager;
    
    /**
     * @var GroupManager $groupManager
     */
    protected $groupManager;
    
    /**
     * @var PrivateMessageManager $privateMessageManager
     */
    protected $privateMessageManager;

    /**
     * @var NotificationManager $notificationManager
     */
    protected $notificationManager;
    
    /**
     * @var \App\Model\Entities\Group $activeGroup
     */
    protected $activeGroup = null;
    
    /**
     * @var \App\Model\Entities\User $activeUser
     */
    protected $activeUser;
    
    protected $title = "";
    
    public function __construct(UserManager $userManager,
            GroupManager $groupManager, 
            PrivateMessageManager $privateMessageManager,
            NotificationManager $notificationManager,
            \App\Model\Entities\User $activeUser)
    {
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->privateMessageManager = $privateMessageManager;
        $this->notificationManager = $notificationManager;
        $this->activeUser = $activeUser;
    }
    
    protected function create()
    {
        
    }
    
    public function render()
    {
        $template = $this->template;
        
        $template->title = $this->title;
        $template->activeGroup = $this->activeGroup;
        $template->activeUser = $this->activeUser;
        $groups = $this->groupManager->getUserGroups($this->activeUser);
        $subject = array();
        $others = array();
        foreach($groups as $group) {
            if($group->groupType == 1) {
                $subject[] = $group;
            } else {
                $others[] = $group;
            }
        }
        $template->notifications = $this->notificationManager->getNotifications($this->activeUser);
        $template->unreadNotifications = $this->notificationManager->getUnreadNumber($this->activeUser);
        $template->unreadPrivMessages = $this->privateMessageManager->getUnreadNumber($this->activeUser);
        $template->privateMessages = $this->privateMessageManager->getMessages($this->activeUser);
        $template->subjects = $subject;
        $template->groups = $others;
        $template->setFile(__DIR__ . '/TopPanel.latte');
        $template->render();
    }
    
    public function setTitle($title)
    {
        if($this->activeGroup === null) {
            $this->title = $title;
        } else {
            $this->title = $this->activeGroup->name . " - " . $title;
        }
    }
    
    public function setActiveGroup($activeGroup)
    {
        $this->activeGroup = $activeGroup;
        $this->title = $this->activeGroup->name;
    }
    
    public function createComponentNewGroupForm()
    {
        $form = new NewGroupForm($this->groupManager);
        return $form;
    }
    
    
    public function handlePrivateMessagesRead()
    {
        $this->privateMessageManager->setMessagesRead($this->activeUser->id);
        $this->redrawControl('messagesCount');   
    }
    
    public function handleNotificationRead()
    {
        $this->notificationManager->setNotificationRead($this->activeUser->id);
        $this->redrawControl('notificationCount');
    }
    
}
