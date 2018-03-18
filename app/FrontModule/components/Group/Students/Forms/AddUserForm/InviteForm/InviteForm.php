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
        $form->addHidden('user_id');
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
        if($values->user_id) {
            $added = $this->groupManager->addUserToGroup($this->presenter->activeGroup, $values->user_id, GroupManager::RELATION_STUDENT);
            if($added) {
                $this->presenter->flashMessage('Uživatel byl přidán', 'success');
            } else {
                $this->presenter->flashMessage('Uživatel je již členem skupiny', 'warning');
            }
            $this->presenter->redirect(':Front:Group:users');
        }        
    }
}
