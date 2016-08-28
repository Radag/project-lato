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

class ProfilePresenter extends BasePresenter
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
    
    public function actionDefault($idUser = null)
    {
        $this->template->activeUser = $this->activeUser;
        if($idUser === null) {          
            $this->template->profileUser = $this->activeUser;
            $this->template->isMe = true;
        } else {
            $profileUser = $this->userManager->get($idUser);
            $this->template->activeUser = $profileUser; 
            $this->template->isMe = false;
            $myClasses = $this->classroomManager->getClasses($this->activeUser);
            $this->template->relation = $this->classroomManager->getRelation($profileUser, $myClasses);
        }
    }
    
    
    protected function createComponentAccountSettingsForm()
    {
        $form = new \Nette\Application\UI\Form;

        $form->addText('name','Jméno')
             ->setAttribute("placeholder","Jméno")
             ->setDefaultValue($this->activeUser->name);
        $form->addText('surname','Příjmení')
             ->setAttribute("placeholder","Příjmení")
             ->setDefaultValue($this->activeUser->surname);
        $form->addText('email','Emailová adresa')
             ->setAttribute("placeholder","Emailová adresa")
             ->setDefaultValue($this->activeUser->email);
        $form->addText('birthday','Datum narození')
             ->setAttribute("placeholder","Datum narození");

        $form->addSubmit('submit', 'Odeslat');
        $form->onSuccess[] = function($form, $values) {
            $this->userManager->updateUser($values, $this->activeUser );
            $this->flashMessage('Nastavení uživatele uloženo');
            $this->redirect('this');
        };
        return $form;        
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
        $file = $this->fileManager->uploadFile($files['file'], $path);
        if($file) {
            $this->userManager->assignProfileImage($this->activeUser, $file);
        }
        $this->payload->image = $this->userManager->get($this->user->id)->profileImage;
        $this->sendPayload();
    }
    
}
