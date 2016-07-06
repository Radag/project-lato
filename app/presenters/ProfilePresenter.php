<?php

namespace App\Presenters;

use App\Model\Manager\UserManager;
use App\Model\Manager\MessageManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\PrivateMessageManager;
use App\Model\Manager\NotificationManager;
use App\Components\Authetication\TopPanel\TopPanel;
use App\Model\Manager\FileManager;

class ProfilePresenter extends BasePresenter
{

    private $userManager;
    private $messageManager;
    private $groupManager;
    private $privateMessageManager;
    private $notificationManager;
    private $fileManager;
    
    public function startup() {
        parent::startup();
        if(!$this->getUser()->isLoggedIn()) {
            $this->redirect('Homepage:default');
        }
    }
    
    
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
    
    public function actionProfile($idUser = null)
    {
        if($idUser === null) {          
            $this->template->activeUser = $this->userManager->get($this->user->id);
        } else {
            $this->template->activeUser = $this->userManager->get($idUser);        
        }
    }
    
    public function actionAccount()
    {
        $this->template->userAccount = $this->userManager->get($this->user->id);
    }
    
    protected function createComponentTopPanel()
    {
        return new TopPanel($this->userManager, $this->groupManager, null, $this->privateMessageManager, $this->notificationManager);
    }
    
    public function handleUploadProfileImage()
    {
        $files = $this->getRequest()->getFiles();
        $path = 'users/' . $this->user->getIdentity()->data['URL_ID'] . '/profile';
        $idFile = $this->fileManager->uploadFile($files['file'], $path);
        if($idFile) {
            $this->userManager->assignProfileImage($this->user, $idFile);
        }
        $this->payload->image = $this->userManager->get($this->user->id)->profileImage;
        $this->sendPayload();
    }
    
}
