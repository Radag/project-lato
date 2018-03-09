<?php
namespace App\FrontModule\Components\Group\AddUserForm;


use App\Model\Manager\GroupManager;
use App\Model\Manager\FictiveUserManager;

class ImportForm extends \App\Components\BaseComponent
{
    
    /** @var GroupManager */
    protected $groupManager;
    
    /** @var FictiveUserManager */
    protected $fictiveUserManager;
        
    protected $selectedGroup = null;
    
    public function __construct(
        GroupManager $groupManager,
        FictiveUserManager $fictiveUserManager
    )
    {
        $this->groupManager = $groupManager;
        $this->fictiveUserManager = $fictiveUserManager;
    }
   
    
    public function render()
    {
        $this->template->adminGroups = $this->groupManager->getUserGroups($this->presenter->activeUser, (object)['relation' => 'owner', 'skip_ids' => $this->presenter->activeGroup->id])->groups;
        $this->template->selectedGroup = $this->selectedGroup;
        parent::render();
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm();
        $form->addHidden('group_id');
        $form->addHidden('users');

        $form->onSuccess[] = function($form, $values) {
            $group = $this->groupManager->getUserGroup($values->group_id, $this->presenter->activeUser, true);
            if(!empty($group)) {
                $studentsIds = json_decode($values->users);
                $students = $this->groupManager->getGroupUsers($values->group_id, 'student', $studentsIds);
                foreach($students as $student) {
                    $userId = $this->fictiveUserManager->createFictiveUser($student, $group);
                    $this->groupManager->addUserToGroup($this->presenter->activeGroup, $userId, GroupManager::RELATION_FIC_STUDENT);
                }
                $this->presenter->flashMessage('Studenti naimportováni.');      
            } else {
                $this->presenter->flashMessage('Nemáte přístup do dané skupiny.');      
            }
            $this->redirect('this');
        };
        return $form;
    }
    
    
    public function handleSelectGroup($id)
    {
        if(empty($id)) {
            $this->selectedGroup = null;
        } else {
            $this->selectedGroup = $this->groupManager->getUserGroup($id, $this->presenter->activeUser, true);
            $this->template->students = $this->groupManager->getGroupUsers($id, 'student');
            $this['form']->setDefaults([
                'group_id' => $id
            ]);
        }
        
        $this->redrawControl();
    }
}
