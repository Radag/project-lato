<?php

namespace App\FrontModule\Presenters;

use App\Model\Manager\ClassroomManager;
use App\Model\Manager\GroupManager;

class ProfilePresenter extends BasePresenter
{
    /** @var ClassroomManager @inject */
    public $classroomManager;
    
    /** @var GroupManager @inject */
    public $groupManager;
    
    /** @persistent */
    public $messagesFilter = null;
        
    public function renderDefault($id = null)
    {
        $this['topPanel']->setTitle('Profil');
        $this->template->activeUser = $this->activeUser;
        $myClasses = $this->classroomManager->getClasses($this->activeUser);
        if($id === null || $this->activeUser->slug === $id) {  
            $this->template->profileUser = $this->activeUser;
            $this->template->isMe = true;
            $this->template->schools = $myClasses;
            $profileId = $this->activeUser->id;
        } else {
            $profileUser = $this->userManager->get($id, true);
            if(!$profileUser) {
                $this->flashMessage("Tento uživatel neexistuje");
                $this->redirect('Homepage:noticeboard');
            }
            $profileId = $profileUser->id;
            $this->template->activeUser = $profileUser; 
            $this->template->isMe = false;
            $this->template->schools = $this->classroomManager->getClasses($profileUser);
            //$this->template->relation = $this->classroomManager->getRelation($profileUser, $myClasses);
            //$this->template->isFriend = $this->userManager->isFriend($this->activeUser->id, $profileUser->id);
        }
        $this->template->groups = $this->groupManager->getProfileUserGroups($profileId, $this->activeUser->id);
    }
    
    public function handleAddFriend($idUser)
    {
        $this->userManager->switchUserRelation($this->activeUser->id, $idUser, true);
        $this->flashMessage('Uživatel byl přidán mezi přátele');   
        $this->redrawControl('profileMenu');
    }
    
    public function handleRemoveFriend($idUser)
    {
        $this->userManager->switchUserRelation($this->activeUser->id, $idUser, false);
        $this->flashMessage('Uživatel byl odebrán z přátel');
        $this->redrawControl('profileMenu');
    }
}
