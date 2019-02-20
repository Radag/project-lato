<?php

namespace App\FrontModule\Components\Account;

use App\Model\Manager\NotificationManager;
use App\Model\Entities\User;
use App\Model\Manager\UserManager;
use App\Model\Manager\SchoolManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\FileManager;
use Nette\Utils\Image;

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
    
    /** @var FileManager $fileManager */
    public $fileManager;
    
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
        GroupManager $groupManager,
        FileManager $fileManager    
    )
    {
        $this->userManager = $userManager;
        $this->notificationManager = $notificationManager;
        $this->schoolManager = $schoolManager;
        $this->groupManager = $groupManager;
        $this->fileManager = $fileManager;
    }
    
    public function render() {
        $this->template->userAccount = $this->presenter->activeUser;
        //$this->notificationTypes = $this->notificationManager->getNotificationTypes();
        //$this->template->notifications = $this->notificationTypes;
        $groups = $this->groupManager->getUserGroups($this->presenter->activeUser);
        $this->template->studentGroups = [];
        $this->template->teacherGroups = [];
        foreach($groups->groups as $group) {
            if($group->relation === 'owner') {
                $this->template->teacherGroups[] = $group;
            } else {
                $this->template->studentGroups[] = $group;
            }
        }
        parent::render();
    }
    
    protected function createComponentForm()
    {
       // $this->notificationTypes = $this->notificationManager->getNotificationTypes();
        //$this->notificationSettings = $this->notificationManager->getNotificationSettings($this->presenter->activeUser);
        $userSchool = $this->schoolManager->getSchool($this->presenter->activeUser);
        
        $form = $this->getForm();
        
        $form->addText('name')
             ->setRequired('Zadejte prosím svoje jméno')
             ->setDefaultValue($this->presenter->activeUser->name);
        $form->addText('surname')
             ->setRequired('Zadejte prosím svoje příjmení')
             ->setDefaultValue($this->presenter->activeUser->surname);
        $form->addText('email')
             ->setRequired('Zadejte svůj email')
             ->setDefaultValue($this->presenter->activeUser->email);
        $form->addText('birthday');
             //->setRequired('Zadejte datum narození');
        
        if($this->presenter->activeUser->birthday) {
            $form['birthday']->setDefaultValue($this->presenter->activeUser->birthday->format('d. m. Y'));
        }
            
        $form->addText('school');
        $form->addText('class');
        
        if($userSchool) {
            $form['school']->setDefaultValue($userSchool->school);
            $form['class']->setDefaultValue($userSchool->class);
        }
        $form->addHidden('deleteGroups');
        
        $form->addCheckbox('emailNotification')
             ->setDefaultValue($this->presenter->activeUser->emailNotification);
        
//        foreach($this->notificationTypes as  $type) {
//            $form->addCheckbox('notification_' . $type->ID_TYPE, $type->NAME);
//        }
//        
//        foreach($this->notificationSettings as  $setting) {
//            $form['notification_' . $setting->ID_TYPE]->setValue($setting->SHOW_NOTIFICATION);
//        }
        
        $form->addSubmit('submit', 'Odeslat');
        $form->onSuccess[] = function($form, $values) {
            $values->birthday = \DateTime::createFromFormat('d. m. Y', $values->birthday);
            $this->userManager->updateUser($values, $this->presenter->activeUser );
            
            if(!empty($values['school'])) {
                $this->schoolManager->insertSchool($values['school'], $values['class'], $this->presenter->activeUser);
            } else {
                $this->schoolManager->removeSchools($this->presenter->activeUser);
            }
            
            foreach($values as $key=>$val) {
                $a = explode('_', $key);
                if($a[0] === 'notification') {
                    
                    $this->notificationManager->setSettings($this->presenter->activeUser, $a[1], $val);
                }
            }
            
            $delete = json_decode($values['deleteGroups']);
            $groups = $this->groupManager->getUserGroups($this->presenter->activeUser)->groups;
            if($groups && $delete) {
                foreach($delete as $idGroup) {
                    if(isset($groups[$idGroup]) && $groups[$idGroup]->relation === 'owner') {
                        $this->groupManager->archiveGroup($idGroup);
                    } elseif(isset($groups[$idGroup])) {
                        $this->groupManager->removeUserFromGroup($groups[$idGroup], $this->presenter->activeUser);
                        $this->notificationManager->addLeftGroup($this->presenter->activeUser, $groups[$idGroup]);
                    }
                }  
            }
            $this->presenter->flashMessage('Nastavení uloženo', 'success');
            $this->redirect('this');
        };
        return $form;        
    }
    
    public function handleUploadProfileImage()
    {
        $files = $this->presenter->getRequest()->getFiles();
        $image = Image::fromFile($files['file']);
        if($image->width < 176 ||  $image->height < 176) {
            $this->presenter->flashMessage('Velikost obrázku musí být alespoň 176 × 176 pixelů', 'error');
        } else {
            $path = 'users/' . $this->presenter->activeUser->slug . '/profile';
            $file = $this->fileManager->saveFile($files['file'], $path, ['width' => 200, 'height' => 200, 'type' => ['image/png', 'image/jpeg']]);
            if($file) {
                $this->userManager->assignProfileImage($this->presenter->activeUser, $file);
            }
            $this->presenter->payload->image = $this->userManager->get($this->presenter->activeUser->id)->profileImage;
            $this->presenter->sendPayload();
        }   
    }
    
    public function handleUploadBackgroundImage()
    {
        $files = $this->presenter->getRequest()->getFiles();
        $image = Image::fromFile($files['file']);
        if($image->width < 1156 ||  $image->height < 420) {
            $this->presenter->flashMessage('Velikost obrázku musí být alespoň 1156 × 420 pixelů', 'error');
        } else {
            $path = 'users/' . $this->presenter->activeUser->slug . '/profile';
            $file = $this->fileManager->saveFile($files['file'], $path, ['width' => 1156, 'height' => 420, 'type' => ['image/png', 'image/jpeg']]);
            if($file) {
                $this->userManager->assignBackgroundImage($this->presenter->activeUser, $file);
            }
            $this->presenter->payload->image = $this->userManager->get($this->presenter->activeUser->id)->backgroundImage;
            $this->presenter->sendPayload();
        }
    }
}
