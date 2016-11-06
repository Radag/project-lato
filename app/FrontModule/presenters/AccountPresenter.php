<?php

namespace App\FrontModule\Presenters;

use App\Model\Manager\UserManager;
use App\Model\Manager\MessageManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\PrivateMessageManager;
use App\Model\Manager\NotificationManager;
use App\FrontModule\Components\TopPanel\TopPanel;
use App\Model\Manager\FileManager;
use App\Model\Manager\ClassroomManager;
use App\FrontModule\Components\Account\PersonalSettings\PersonalSettings;
use App\FrontModule\Components\Account\NotificationSettings\NotificationSettings;
use App\FrontModule\Components\Account\SharingSettings\SharingSettings;

class AccountPresenter extends BasePresenter
{

    protected $userManager;
    protected $messageManager;
    protected $groupManager;
    protected $privateMessageManager;
    protected $notificationManager;
    protected $fileManager;
    protected $classroomManager;
        
    
    public function __construct(UserManager $userManager, 
            MessageManager $messageManager, 
            GroupManager $groupManager,
            PrivateMessageManager $privateMessageManager,
            NotificationManager $notificationManager,
            ClassroomManager $classroomManager,
            FileManager $fileManager)
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->groupManager = $groupManager;
        $this->privateMessageManager = $privateMessageManager;
        $this->notificationManager = $notificationManager;
        $this->fileManager = $fileManager;       
        $this->classroomManager = $classroomManager;
    }
    
    protected function startup()
    {
        parent::startup();
        $this->template->userAccount = $this->activeUser;
    }
        
    public function actionDefault()
    {
        $this['topPanel']->setTitle('Osobní nastavení');
        $this->setView('default');
    }
    
    public function actionSharing()
    {
        $this['topPanel']->setTitle('Nastavení sdílení');
        $this->setView('default');
    }
    
    public function actionNotification()
    {
        $this['topPanel']->setTitle('Nastavení oznámení');
        $this->setView('default');
    }
    
    
    public function createComponentNotificationSettings($id)
    {
        return new NotificationSettings($this->activeUser, $this->notificationManager);
    }
    
    public function createComponentPersonalSettings($id)
    {
        return new PersonalSettings($this->userManager, $this->activeUser);
    }
    
    public function createComponentSharingSettings($id)
    {
        return new SharingSettings($this->activeUser);
    }
    
    
    public function handleUploadProfileImage()
    {
        $files = $this->getRequest()->getFiles();
        $path = 'users/' . $this->activeUser->urlId . '/profile';
        $file = $this->fileManager->uploadFile($files['file'], $path);
        if($file) {
            $this->userManager->assignProfileImage($this->activeUser, $file);
        }
        $this->payload->image = $this->userManager->get($this->user->id)->profileImage;
        $this->sendPayload();
    }
}
