<?php

namespace App\FrontModule\Components\Account;

use App\Model\Manager\NotificationManager;
use App\Model\Entities\User;
use App\Model\Manager\UserManager;
use App\Model\Manager\SchoolManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\FileManager;
use App\Service\FileService;
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
    
    /** @var FileService $fileService */
    public $fileService;
    
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
        FileManager $fileManager,
        FileService $fileService
    )
    {
        $this->userManager = $userManager;
        $this->notificationManager = $notificationManager;
        $this->schoolManager = $schoolManager;
        $this->groupManager = $groupManager;
        $this->fileManager = $fileManager;
        $this->fileService = $fileService;
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
        $form->addHidden('archiveGroups');
        $form->addHidden('leaveGroups');
        
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
            $archive = json_decode($values['archiveGroups']);
            $leave = json_decode($values['leaveGroups']);
            $groups = $this->groupManager->getUserGroups($this->presenter->activeUser)->groups;
            if($groups) {
                foreach($groups as $group) {
                    if($group->relation === GroupManager::RELATION_OWNER) {
                        if(is_array($archive) && in_array($group->id, $archive)) {
                            $this->groupManager->archiveGroup($group->id);
                        } elseif(is_array($delete) &&  in_array($group->id, $delete)) {
                            $this->groupManager->deleteGroup($group->id);
                        }
                    } else {
                        if(is_array($leave) && in_array($group->id, $leave)) {
                            $this->groupManager->removeUserFromGroup($group, $this->presenter->activeUser);
                            $this->notificationManager->addLeftGroup($this->presenter->activeUser, $group);
                        }                        
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
        $file = $this->presenter->request->getFiles();
        try {
            if(empty($file['file'])) { 
                throw new \Lato\FileUploadException("Soubor se nepodařilo nahrát.");
            }
            $restrictions = ['image' => true, 'min-width' => 176, 'min-height' => 176, 'mime' => ['image/png', 'image/jpeg']];
            $settings = ['preview-width' => 200, 'preview-height' => 200];
            $image = $this->fileService->uploadFile($file['file'], FileService::FILE_PURPOSE_AVATAR, $restrictions, $settings);;
            $this->presenter->payload->image = $image;
            $this->userManager->assignProfileImage($this->presenter->activeUser, $image);
        } catch (\Lato\FileUploadException $ex) {
            $this->presenter->payload->error = $ex->getMessage();
        }
        $this->presenter->sendPayload();
    }
    
    protected function createComponentAvatarForm()
    {
        $form = $this->getForm();
        
        $form->addRadioList('avatar', 'avatar', AccountActivated::$avatarList)
             ->setRequired();
        
        $form->addSubmit('submit', 'Odeslat');
        $form->onSuccess[] = function($form, $values) {
            $file = new \App\Model\Entities\File();
            $file->fullPath = "https://www.lato.cz" . AccountActivated::$avatarList[$values->avatar];
            $this->userManager->assignProfileImage($this->presenter->activeUser, $file);
            $this->presenter->redirect('this');
        };
        return $form;        
    }
    
    protected function createComponentBackgroundForm()
    {
        $form = $this->getForm();
        $form->addRadioList('avatar', 'avatar', AccountActivated::$backroudsList)
             ->setRequired();
        
        $form->addSubmit('submit', 'Odeslat');
        $form->onSuccess[] = function($form, $values) {
            if(isset(AccountActivated::$backroudsList[$values->avatar])) {
            	$image = [
					'fullPath' => '/images/account-headers/' . AccountActivated::$backroudsList[$values->avatar]
				];
				$this->userManager->assignBackgroundImage($this->presenter->activeUser, $image);
			}
            $this->presenter->redirect('this');
        };
        return $form;        
    }
}
