<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Presenters;

use Nette;
use \App\Model\Manager\UserManager;
use App\FrontModule\Components\TopPanel\TopPanel;
use App\Model\Manager\PrivateMessageManager;
use App\Model\Manager\NotificationManager;
use App\FrontModule\Components\PrivateMessageForm\PrivateMessageForm;
use App\FrontModule\Components\Stream\ICommitTaskFormFactory;
use App\Helpers\HelpersList;

/**
 * Description of BasePresenter
 *
 * @author Radaq
 */
class BasePresenter extends Nette\Application\UI\Presenter
{
    /** @var Nette\Database\Context */
    private $database;

    /**
     * Aktivní uživatel, pod kterým se zobrazuje celý frontend
     *  
     * @var \App\Model\Entities\User
     */
    public $activeUser;
    
    /**
     * @var ICommitTaskFormFactory
     */
    public $commitTaskFormFactory;
    
    /**
     * @var UserManager 
     */
    protected $userManager;
    
    protected $privateMessageManager;
    protected $notificationManager;
    
    /** @persistent */
    public $activePeriod = 1;
    
    
    public function __construct(Nette\Database\Context $database, 
            UserManager $userManager,
            PrivateMessageManager $privateMessageManager,
            ICommitTaskFormFactory $commitTaskFormFactory
            )
    {
        $this->database = $database;
        $this->userManager = $userManager;
        $this->privateMessageManager = $privateMessageManager;
        $this->commitTaskFormFactory = $commitTaskFormFactory;
    }
    
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
    }
    
    protected function createComponentTopPanel()
    {
        return new TopPanel($this->userManager, $this->groupManager, $this->privateMessageManager, $this->notificationManager, $this->activeUser);
    }
    
    protected function createComponentPrivateMessageForm()
    {
        return new PrivateMessageForm($this->privateMessageManager, $this->activeUser, $this->userManager);
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
