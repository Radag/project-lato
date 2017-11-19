<?php

namespace App\FrontModule\Presenters;

use App\FrontModule\Components\Account\IAccountSettings;
use \App\Model\Manager\UserManager;
use App\Model\Manager\PrivateMessageManager;
use App\Model\Manager\NotificationManager;
use App\FrontModule\Components\Stream\ICommitTaskFormFactory;
use App\Model\Manager\GroupManager;

class AccountPresenter extends BasePresenter
{
    
    /** @var Nette\Database\Context */
    private $database;
    
    /** @var IAccountSettings @inject */
    protected $accountSettings;
        
    /** @var UserManager  */
    protected $userManager;
       
    /** @var GroupManager  */
    protected $groupManager;
    
    /** @var NotificationManager  */
    protected $notificationManager;
    
    public function __construct(\Nette\Database\Context $database, 
        UserManager $userManager,
        PrivateMessageManager $privateMessageManager,
        ICommitTaskFormFactory $commitTaskFormFactory,
        GroupManager $groupManager,
        NotificationManager $notificationManager,
        IAccountSettings $accountSettings
    )
    {
        $this->database = $database;
        $this->userManager = $userManager;
        $this->privateMessageManager = $privateMessageManager;
        $this->commitTaskFormFactory = $commitTaskFormFactory;
        $this->groupManager = $groupManager;
        $this->notificationManager = $notificationManager;
        $this->accountSettings = $accountSettings;
    }
     
    public function actionDefault()
    {
        $this['topPanel']->setTitle('Nastavení');
        $this->setView('default');
    }

    public function createComponentAccountSettings()
    {
        return $this->accountSettings->create();
    } 
    
}
