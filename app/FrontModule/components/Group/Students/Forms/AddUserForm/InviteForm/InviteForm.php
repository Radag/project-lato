<?php
namespace App\FrontModule\Components\Group\AddUserForm;

use \Nette\Application\UI\Form;
use App\Model\Manager\UserManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\NotificationManager;

class InviteForm extends \App\Components\BaseComponent
{    
    /** @var UserManager */
    protected $userManager;
    
    /** @var GroupManager */
    public $groupManager;
        
    /** @var NotificationManager */
    public $notificationManager;

    public function __construct(
        UserManager $userManager,
        GroupManager $groupManager,
        NotificationManager $notificationManager
    )
    {
        $this->groupManager = $groupManager;
        $this->userManager = $userManager;
        $this->notificationManager = $notificationManager;
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm();
        $form->addText('userName', 'Název hodnocení');
        $form->addHidden('users');
        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }
    
    public function render()
    {
        $this->template->activeGroup = $this->presenter->activeGroup;
        parent::render();
    }
    
    public function handleSearchUsers()
    {
        $term = $this->presenter->getParameter('term');
        $userList = $this->userManager->searchGroupUser($term, [$this->presenter->activeUser->id]);
        if(empty($userList)) {
            $userList = [];
        }
        $this->template->userList = $userList;
        $this->redrawControl('users-list');
    }

    public function processForm(Form $form, $values) 
    {
        if($values->users) {
            $users = $this->userManager->getMultiple(explode(',', $values->users), false);
            foreach($users as $user) {
                $added = $this->groupManager->addUserToGroup($this->presenter->activeGroup, $user->id, GroupManager::RELATION_STUDENT);
                if($added) {
                    $this->notificationManager->addNotificationInviteGroupMember($this->presenter->activeGroup, $user);
                    $this->presenter->flashMessage("Student " . $user->name . " " . $user->surname . " byl přidán", 'success');
                } else {
                    $this->presenter->flashMessage("Student " . $user->name . " " . $user->surname . " je již členem skupiny", 'success');
                }
            }
            $this->presenter->redirect('this');
        }        
    }
}
