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
use App\Model\Manager\ConversationManager;
use App\Model\Manager\NotificationManager;
use App\FrontModule\Components\NewGroupForm\NewGroupForm;
use App\FrontModule\Components\NewGroupForm\JoinGroupForm;


class TopPanel extends Control
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
        $template->unreadPrivMessages = 0;//$this->conversationManager->getUnreadNumber($this->presenter->activeUser);
        $template->privateMessages = $this->conversationManager->getConversations($this->presenter->activeUser);
        $template->groups = $this->groupManager->getUserGroups($this->presenter->activeUser);
        $template->colorScheme = $this->colorScheme;

        $template->setFile(__DIR__ . '/TopPanel.latte');
        $template->render();
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
    
    public function handlePrivateMessagesRead()
    {
        $this->privateMessageManager->setMessagesRead($this->activeUser->id);
        $this->redrawControl('messagesCount');   
    }
    
    public function handleReadNotification($id)
    {
          $redirect = $this->notificationManager->getReadNotification($id, $this->presenter->activeUser);
          $this->presenter->redirect($redirect->link, $redirect->args);
    }
    
    public function handleNotificationsRead()
    {
        $this->notificationManager->setAllNotificationRead($this->presenter->activeUser->id);
        $this->presenter->activeUser->unreadNotifications = 0;
        $this->redrawControl('notificationCount');
    }
    
}
