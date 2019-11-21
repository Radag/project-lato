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
        parent::render();
    }
    
    
    protected function createComponentForm()
    {
        $form = $this->getForm();
        $form->addHidden('group_id');
        $form->addHidden('test_id');
        $form->addHidden('id');
        $form->addSelect('time_limit', "Čas na vypracování", [
            '5' => "5 minut",
            '10' => "10 minut",
            '15' => "15 minut",
            '20' => "20 minut",
            '30' => "30 minut",
        ]);
        $form->addText('publication_date')->setType('date');
        $form->addText('publication_time')->setType('time');
        
        
        $form->addSelect('questions_count', "Počet otázek", [
            '0' => "Použít všechny",
            '5' => "5 náhodných",
            '10' => "10 náhodných",
            '15' => "15 náhodných"
        ]);
        
        $form->onSuccess[] = function($form, $values) {
            $testSetup = new \App\Model\Entities\Test\TestSetup;
            $testSetup->testId = $values->test_id;
            $testSetup->groupId = $values->group_id;
            $testSetup->numberOfRepetitions = 0;
            $testSetup->timeLimit = $values->time_limit * 60;
            $testSetup->questionsCount = $values->questions_count == 0 ? null : $values->questions_count;
            
            if($values->publication_date && $values->publication_time) {
               $testSetup->publicationTime = new \DateTime();
                $date = explode('-', $values->publication_date);
                $time = explode(':', $values->publication_time);
                $testSetup->publicationTime->setDate($date[0], $date[1], $date[2]);
                $testSetup->publicationTime->setTime($time[0], $time[1]); 
            } else {
               $testSetup->publicationTime = null; 
            }            
            if($values->id) {
                
            } else {
                $this->testManager->createTestSetup($testSetup);
            }           
            
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
    
    public function setDefault(int $setupId)
    {
        $testSetup = $this->testManager->getTestSetup($setupId);
        $this->selectedGroup = $this->groupManager->getUserGroup($testSetup->groupId, $this->presenter->activeUser, true);
        $this['form']->setDefaults([
            'group_id' => $testSetup->groupId,
            'test_id' => $testSetup->testId,
            'id' => $testSetup->id
        ]);
        $this->template->setupId = $setupId;
    }
    
    public function handleSelectGroup($id, $testId)
    {
        if($id && $testId) {
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
