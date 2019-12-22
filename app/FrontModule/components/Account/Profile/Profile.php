<?php
namespace App\FrontModule\Components\Account;


use App\Model\Manager\ClassroomManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\UserManager;

class Profile extends \App\Components\BaseComponent
{
    /** @var ClassroomManager @inject */
    public $classroomManager;
    
    /** @var GroupManager @inject */
    public $groupManager;
    
    /** @var UserManager @inject */
    public $userManager;
    
    /** @var \App\Model\Entities\User @inject */
    private $user = null;
    
    public function __construct(
        ClassroomManager $classroomManager,
        GroupManager $groupManager,
        UserManager $userManager
    )
    {
        $this->classroomManager = $classroomManager;
        $this->groupManager = $groupManager;
        $this->userManager = $userManager;
    }
    
    public function render() 
    {
        $this->template->isMe = false;
        if($this->user === null) {
            $this->user = $this->presenter->activeUser; 
            $this->template->isMe = true;
        }
        
        $this->template->schools = $this->classroomManager->getClasses($this->user);
        $this->template->profileUser = $this->user;
        $this->template->groups = $this->groupManager->getProfileUserGroups($this->user->id, $this->presenter->activeUser->id);
        parent::render();
    }
    
    
    public function setUser($slug) 
    {
        if($slug !== $this->presenter->activeUser->slug) {
            $this->user = $this->userManager->get($slug, true);
            $this->presenter['topPanel']->setTitle($this->user->name . " " . $this->user->surname);
        }
    }
    
    public function renderDefault($id = null)
    {
        
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
