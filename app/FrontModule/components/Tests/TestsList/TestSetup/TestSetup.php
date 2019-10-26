<?php
namespace App\FrontModule\Components\Test;

use App\Model\Manager\TestManager;
use App\Model\Manager\GroupManager;
use App\Model\Entities\Group;

class TestSetup extends \App\Components\BaseComponent
{
   /** @var TestManager **/
    private $testManager;
    
    /** @var GroupManager **/
    private $groupManager;
    
    /** @var Group **/
    protected $selectedGroup = null;
          
    public function __construct(
        TestManager $testManager,            
        GroupManager $groupManager
    )
    {
        $this->testManager = $testManager;
        $this->groupManager = $groupManager;
    }
    
    public function render() 
    {
        $this->template->adminGroups = $this->groupManager->getUserGroups($this->presenter->activeUser, (object)['relation' => 'owner'])->groups;
        $this->template->selectedGroup = $this->selectedGroup;  
        $this->template->selectedGroup = $this->selectedGroup;
        parent::render();
    }
    
    
    protected function createComponentForm()
    {
        $form = $this->getForm();
        $form->addHidden('group_id');
        $form->addHidden('test_id');

        $form->onSuccess[] = function($form, $values) {
            $this->testManager->createGroupTest($values->test_id, $values->group_id);
            $this->presenter->flashMessage("Test byl zadán do skupiny.");
            $this->redirect('this');
        };
        return $form;
    }
    
    public function setTestId(int $testId)
    {
        $this->template->testId = $testId;
        $this->redrawControl();
    }
    
    public function handleSelectGroup($id, $testId)
    {
        if($id &&$testId) {
            $this->selectedGroup = $this->groupManager->getUserGroup($id, $this->presenter->activeUser, true);
            $this['form']->setDefaults([
                'group_id' => $id,
                'test_id' => $testId
            ]);
        } else {
            $this->selectedGroup = null;           
        }        
        $this->redrawControl();
    }
}
