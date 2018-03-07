<?php
namespace App\FrontModule\Components\Group\AddUserForm;


use App\Model\Manager\GroupManager;

class ImportForm extends \App\Components\BaseComponent
{
    
    /** @var GroupManager */
    protected $groupManager;
        
    protected $selectedGroup = null;
    
    public function __construct(
        GroupManager $groupManager
    )
    {
        $this->groupManager = $groupManager;
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

        $form->onSuccess[] = function($form, $values) {
            
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
