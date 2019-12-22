<?php

namespace App\FrontModule\Presenters;

use App\FrontModule\Components\TopPanel\ITopPanel;
use App\FrontModule\Components\Conversation\INewChat;
use App\Helpers\HelpersList;
use App\Model\Manager\GroupManager;
use App\Model\Manager\NotificationManager;
use \App\Model\Manager\UserManager;
use App\Model\LatoSettings;
use App\Service\ConversationService;

class BasePresenter extends \Nette\Application\UI\Presenter
{
    /** @var LatoSettings @inject */
    public $lattoSettings;
    
    /** @var UserManager @inject */
    public $userManager;
       
    /** @var GroupManager @inject */
    public $groupManager;
    
    /** @var NotificationManager @inject */
    public $notificationManager;
    
    /** @var ConversationService @inject */
    public $conversationService;

    /** @var INewChat @inject */
    public $newChat;
        
    /** @var ITopPanel @inject */
    public $topPanel;
    
    public $days = ['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle'];
     
    /** @var \App\Model\Entities\User */
    public $activeUser;           
    
    protected function startup()
    {
        parent::startup();
        if(!$this->getUser()->isLoggedIn()) {
            $this->redirect(':Public:Homepage:default');
        } else {
            $this->setActiveUser();
            $this->userManager->setLastLogin($this->user->id);
        }
        
        $this->template->addFilter('timeDifferceText',function($timeLeft) {
            return HelpersList::timeDifferceText($timeLeft);
        });
    }
    
    protected function setActiveUser()
    {
        $this->lattoSettings->setUser($this->userManager->get($this->user->id));
        $this->activeUser = $this->lattoSettings->getUser();
        if($this->activeUser === null) {
            $this->user->logout();
            $this->redirect(':Public:Homepage:default');
        } elseif($this->activeUser->emailVerification != 1 && !$this->isLinkCurrent('Homepage:confirm') && !$this->isLinkCurrent('Homepage:confirmSuccess')) {
            $this->redirect('Homepage:confirm');
        } elseif(!$this->activeUser->avatar && !$this->isLinkCurrent('Homepage:confirmSuccess')) {
            $this->redirect('Homepage:confirmSuccess');
        }
    }
    
    protected function createComponentTopPanel()
    {
        return $this->topPanel->create();
    }
    
    protected function createComponentNewChatForm()
    {
        return $this->newChat->create();
    }
       
    public function handleNewMessage($idUserTo)
    {
        $user = $this->userManager->get($idUserTo);
        if($user) {
            $this->redirect(':Front:Conversation:default', $this->conversationService->getConversationParams([$user]));
        }
    }
    
    public function handleGetUserList()
    {
        $users = $this->userManager->getUsersList();
        $this->presenter->payload->users = json_encode($users);
        $this->presenter->sendPayload();
    }
    
    public function handleChangePeriod($idPeriod)
    {
        $this->activePeriod = $idPeriod;
        $this->redirect('this');
    }
    
    public function flashMessage($message, string $type = 'info'): \stdClass 
    {
        $flash = parent::flashMessage($message, $type);
        $this->redrawControl('flashMessages');
        return $flash;
    }
    
    public function handleLogout()
    {
        $this->user->logout(true);
        $this->redirect(':Public:Homepage:default');
    }
}
