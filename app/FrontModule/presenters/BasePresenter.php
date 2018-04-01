<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Presenters;

use Nette;
use \App\Model\Manager\UserManager;
use App\FrontModule\Components\TopPanel\ITopPanelFactory;
use App\Model\Manager\NotificationManager;
use App\FrontModule\Components\PrivateMessageForm\IPrivateMessageFormFactory;
use App\Helpers\HelpersList;
use App\Model\Manager\GroupManager;

/**
 * Description of BasePresenter
 *
 * @author Radaq
 */
class BasePresenter extends Nette\Application\UI\Presenter
{    
    /** @var UserManager @inject */
    public $userManager;
       
    /** @var GroupManager @inject */
    public $groupManager;
    
    /** @var NotificationManager @inject */
    public $notificationManager;

    /** @var IPrivateMessageFormFactory @inject */
    public $privateMessageForm;
    
    /** @var ITopPanelFactory @inject */
    public $topPanel;
    
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
        $this->activeUser = $this->userManager->get($this->user->id);
        if($this->activeUser === null) {
            $this->user->logout();
            $this->redirect(':Public:Homepage:default');
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
}
