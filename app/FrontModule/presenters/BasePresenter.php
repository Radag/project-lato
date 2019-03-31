<?php

namespace App\FrontModule\Presenters;

use App\FrontModule\Components\TopPanel\ITopPanel;
use App\FrontModule\Components\PrivateMessageForm\IPrivateMessageForm;
use App\Helpers\HelpersList;
use App\Model\Manager\GroupManager;
use App\Model\Manager\NotificationManager;
use \App\Model\Manager\UserManager;

class BasePresenter extends \Nette\Application\UI\Presenter
{    
    /** @var UserManager @inject */
    public $userManager;
       
    /** @var GroupManager @inject */
    public $groupManager;
    
    /** @var NotificationManager @inject */
    public $notificationManager;

    /** @var IPrivateMessageForm @inject */
    public $privateMessageForm;
        
    /** @var ITopPanel @inject */
    public $topPanel;
    
    /** @var \App\Model\Entities\User */
    public $activeUser;
           
    public $days = ['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle'];
     
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
        $this->activeUser = $this->userManager->get($this->user->id);
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
    
    protected function createComponentPrivateMessageForm()
    {
        return $this->privateMessageForm->create();
    }
       
    public function handleShowPrivateMessageForm($idUserTo)
    {
        $this['privateMessageForm']->setIdUserTo($idUserTo);
        $this->redrawControl('privateMessageForm');
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
    
    public function flashMessage($message, $type = 'info') {
        parent::flashMessage($message, $type);
        $this->redrawControl('flashMessages');
    }
    
    public function redrawTasks()    {
        
    }
    
    public function handleLogout()
    {
        $this->user->logout(true);
        $this->redirect(':Public:Homepage:default');
    }
}
