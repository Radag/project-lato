<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Group;

use \Nette\Application\UI\Form;
use App\Model\Manager\UserManager;
use App\Model\Manager\GroupManager;



/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class AddUserForm extends \App\Components\BaseComponent
{
        
    protected $userManager;
    protected $groupManager;
    protected $activeGroup;


    public function __construct(UserManager $userManager,
            GroupManager $groupManager,
            \App\Model\Entities\Group $activeGroup)
    {
        $this->groupManager = $groupManager;
        $this->userManager = $userManager;
        $this->activeGroup = $activeGroup;
        
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
        $this->template->activeGroup = $this->activeGroup;
        parent::render();
    }
    
    public function handleSearchUsers()
    {
        $term = $this->presenter->getParameter('term');
        $userList = $this->userManager->searchGroupUser($term);
        $this->template->userList = $userList;
        $this->redrawControl('users-list');
    }

    public function processForm(Form $form, $values) 
    {
        if($values->user_id) {
            $this->groupManager->addUserToGroup($this->activeGroup, $values->user_id, GroupManager::RELATION_STUDENT);
            $this->presenter->flashMessage('Uživatel byl přidán', 'success');
            $this->presenter->redirect(':Front:Group:users');
        }        
    }
}
