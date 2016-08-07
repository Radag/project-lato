<?php

namespace App\FrontModule\Presenters;

use App\Model\Manager\UserManager;
use App\Model\Manager\MessageManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\PrivateMessageManager;
use App\Model\Manager\NotificationManager;
use App\FrontModule\Components\TopPanel\TopPanel;
use App\Model\Manager\FileManager;

class ProfilePresenter extends BasePresenter
{

    protected $userManager;
    protected $messageManager;
    protected $groupManager;
    protected $privateMessageManager;
    protected $notificationManager;
    protected $fileManager;
        
    
    public function __construct(UserManager $userManager, 
            MessageManager $messageManager, 
            GroupManager $groupManager,
            PrivateMessageManager $privateMessageManager,
            NotificationManager $notificationManager,
            FileManager $fileManager)
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->groupManager = $groupManager;
        $this->privateMessageManager = $privateMessageManager;
        $this->notificationManager = $notificationManager;
        $this->fileManager = $fileManager;        
    }
    
    public function actionDefault($idUser = null)
    {
        if($idUser === null) {          
            $this->template->activeUser = $this->activeUser;
        } else {
            $this->template->activeUser = $this->userManager->get($idUser);        
        }
    }
    
    public function actionAccount()
    {
        $this->template->userAccount = $this->activeUser;
    }
    
    protected function createComponentTopPanel()
    {
        return new TopPanel($this->userManager, $this->groupManager, null, $this->privateMessageManager, $this->notificationManager, $this->activeUser);
    }
    
    public function handleUploadProfileImage()
    {
        $files = $this->getRequest()->getFiles();
        $path = 'users/' . $this->activeUser->urlId . '/profile';
        $idFile = $this->fileManager->uploadFile($files['file'], $path);
        if($idFile) {
            $this->userManager->assignProfileImage($this->activeUser, $idFile);
        }
        $this->payload->image = $this->userManager->get($this->user->id)->profileImage;
        $this->sendPayload();
    }
    
}
