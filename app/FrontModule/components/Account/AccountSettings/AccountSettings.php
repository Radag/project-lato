<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Account;

use App\Model\Manager\NotificationManager;
use App\Model\Entities\User;
use App\Model\Manager\UserManager;
use App\Model\Manager\SchoolManager;
use App\Model\Manager\GroupManager;

/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class AccountSettings extends \App\Components\BaseComponent
{
    /** @var User $activeUser */
    protected $activeUser;
    
    /** @var UserManager $userManager */
    public $userManager;
    
    /** @var SchoolManager $schoolManager */
    public $schoolManager;
    
    /** @var GroupManager $groupManager */
    public $groupManager;
    
    /**
     * @var NotificationManager $notificationManager
     */
    protected $notificationManager;
    
    protected $notificationTypes = array();
    protected $notificationSettings = array();
    
    public function __construct(
        NotificationManager $notificationManager,
        UserManager $userManager,
        SchoolManager $schoolManager,
        GroupManager $groupManager
    )
    {
        $this->userManager = $userManager;
        $this->notificationManager = $notificationManager;
        $this->schoolManager = $schoolManager;
        $this->groupManager = $groupManager;
    }
    
    public function render() {
        $this->template->userAccount = $this->presenter->activeUser;
        $this->notificationTypes = $this->notificationManager->getNotificationTypes();
        $this->template->notifications = $this->notificationTypes;
        $this->template->groups = $this->groupManager->getUserGroups($this->presenter->activeUser);
        parent::render();
    }
    
    protected function createComponentForm()
    {
        $this->notificationTypes = $this->notificationManager->getNotificationTypes();
        $this->notificationSettings = $this->notificationManager->getNotificationSettings($this->presenter->activeUser);
        $userSchool = $this->schoolManager->getSchool($this->presenter->activeUser);
        
        $form = $this->getForm();
        
        $form->addText('name')
             ->setRequired('Zadejte prosím svoje jméno')
             ->setAttribute("placeholder","Jméno")
             ->setDefaultValue($this->presenter->activeUser->name);
        $form->addText('surname')
             ->setRequired('Zadejte prosím svoje příjmení')
             ->setAttribute("placeholder","Příjmení")
             ->setDefaultValue($this->presenter->activeUser->surname);
        $form->addText('email')
             ->setRequired('Zadejte svůj email')
             ->setAttribute("placeholder","Emailová adresa")
             ->setDefaultValue($this->presenter->activeUser->email);
        $form->addText('birthday')
             ->setRequired('Zadejte datum narození')
             ->setAttribute('type', 'date')
             ->setAttribute("placeholder","Datum narození")
             ->setDefaultValue($this->presenter->activeUser->birthday->format('Y-m-d'));
        $form->addText('school')
             ->setAttribute("placeholder", "Název školy (nepovinné)")
             ->setDefaultValue($userSchool->SCHOOL);
        $form->addText('class')
             ->setAttribute("placeholder", "Třída (nepovinné)")
             ->setDefaultValue($userSchool->CLASS);
        $form->addHidden('deleteGroups');
        
        $form->addCheckbox('emailNotification')
             ->setDefaultValue($this->presenter->activeUser->emailNotification);
        
        foreach($this->notificationTypes as  $type) {
            $form->addCheckbox('notification_' . $type->ID_TYPE, $type->NAME);
        }
        
        foreach($this->notificationSettings as  $setting) {
            $form['notification_' . $setting->ID_TYPE]->setValue($setting->SHOW_NOTIFICATION);
        }
        
        $form->addSubmit('submit', 'Odeslat');
        $form->onSuccess[] = function($form, $values) {
            $values->birthday = \DateTime::createFromFormat('Y-m-d', $values->birthday);
            $this->userManager->updateUser($values, $this->presenter->activeUser );
            
            $this->schoolManager->insertSchool($values['school'], $values['class'], $this->presenter->activeUser);
            
            foreach($values as $key=>$val) {
                $a = explode('_', $key);
                if($a[0] === 'notification') {
                    
                    $this->notificationManager->setSettings($this->presenter->activeUser, $a[1], $val);
                }
            }
            
            $delete = json_decode($values['deleteGroups']);
            $groups = $this->groupManager->getUserGroups($this->presenter->activeUser);
            if($groups) {
              foreach($delete as $idGroup) {
                    if($groups[$idGroup]->relation === 'OWNER') {
                        $this->groupManager->archiveGroup($idGroup);
                    } else {
                        $this->groupManager->removeUserFromGroup($idGroup, $this->presenter->activeUser->id);
                    }
                }  
            }
            $this->flashMessage('Nastavení notifikací uloženo', 'success');
            $this->redirect('this');
        };
        return $form;        
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
