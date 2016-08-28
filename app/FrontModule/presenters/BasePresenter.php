<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Presenters;

use Nette;
use \App\Model\Manager\UserManager;


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
    protected $activeUser;
    
    /**
     * @var UserManager 
     */
    protected $userManager;
    
    public function __construct(Nette\Database\Context $database, UserManager $userManager)
    {
        $this->database = $database;
        $this->userManager = $userManager;
    }
    
    protected function startup()
    {
        parent::startup();
        if(!$this->getUser()->isLoggedIn()) {
            $this->redirect(':Public:Homepage:default');
        } else {
            $this->setActiveUser();
        }
    }
    
    protected function setActiveUser()
    {
        $this->activeUser = $this->userManager->get($this->user->id);
    }
}
