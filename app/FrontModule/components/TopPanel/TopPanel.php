<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\TopPanel;


use App\Model\Manager\UserManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\ConversationManager;
use App\Model\Manager\NotificationManager;
use App\FrontModule\Components\NewGroupForm\NewGroupForm;
use App\FrontModule\Components\NewGroupForm\JoinGroupForm;


class TopPanel extends \App\Components\BaseComponent
{
    /** @var UserManager */
    public $userManager;
    
    /** @var GroupManager */
    public $groupManager;
    
    /** @var ConversationManager */
    public $conversationManager;

    /** @var NotificationManager */
    public $notificationManager;

    /** @var \App\Model\Entities\Group */
    public $activeGroup = null;
    
    /** @var \App\Model\Entities\User */
    protected $activeUser;
    
    protected $topMenu = array();
    protected $colorScheme = null;
    protected $backArrow = false;
    
    protected $title = "";
    
    public function __construct(
        UserManager $userManager,
        GroupManager $groupManager, 
        ConversationManager $conversationManager,
        NotificationManager $notificationManager
    )
    {
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->conversationManager = $conversationManager;
        $this->notificationManager = $notificationManager;
    }

    public function addToMenu($item)
    {
        $this->topMenu[] = $item;
    }
    
    public function render()
    {
        $template = $this->template;
        $template->topMenu = $this->topMenu;
        $template->title = $this->title;
        if(isset($this->presenter->activeGroup)) {
            $template->activeGroup = $this->presenter->activeGroup;
        }
        
        $template->activeUser = $this->presenter->activeUser;
        $template->backArrow = $this->backArrow;
        $template->notifications = $this->notificationManager->getNotifications($this->presenter->activeUser);
        $template->privateMessages = $this->conversationManager->getConversations($this->presenter->activeUser);
        $template->groups = $this->groupManager->getUserGroups($this->presenter->activeUser);
        $template->colorScheme = $this->colorScheme;
        
        parent::render();
    }
    
    public function setTitle($title)
    {
        if($this->activeGroup === null) {
            $this->title = $title;
        } else {
            $this->title = $this->activeGroup->name;
        }
    }
    
    public function setScheme($colorScheme) {
        $this->colorScheme = $colorScheme;
    }
    
    public function setActiveGroup($activeGroup)
    {
        $this->activeGroup = $activeGroup;
        $this->title = $this->activeGroup->name;
    }
    
    public function activateBackArrow($link)
    {
        $this->backArrow = $link;
    }
    
    public function createComponentNewGroupForm()
    {
        $form = new NewGroupForm($this->groupManager);
        return $form;
    }
    
    public function createComponentJoinGroupForm()
    {
        $form = new JoinGroupForm($this->groupManager, $this->notificationManager);
        return $form;
    }
    
    
    
    public function handleReadNotification($id)
    {
          $redirect = $this->notificationManager->getReadNotification($id, $this->presenter->activeUser);
          $this->presenter->redirect($redirect->link, $redirect->args);
    }
    
    public function handleNotificationsRead($global = true)
    {
        $this->notificationManager->setAllNotificationRead($this->presenter->activeUser->id, $global);
        $this->presenter->activeUser->unreadNotifications = 0;
        
        if($global) {
            $this->redrawControl('notificationCount');
        } else {
            $this->redrawControl('right-notification-list');
        }
    }
    
    public function handlePrivateMessagesRead($global = true)
    {
        $this->conversationManager->setAllMessagesRead($this->presenter->activeUser, $global);
        $this->presenter->activeUser->unreadPrivateMessages = 0;
        if($global) {
            $this->redrawControl('unreadPrivateMessages');   
        } else {
            $this->redrawControl('right-conversation-list');
        }
        
    }
    
}
