<?php
namespace App\FrontModule\Components\Test;

use App\Model\Manager\TestManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\ClassificationManager;
use App\Model\Entities\Group;
use App\Model\Entities\ClassificationGroup;

class TestSetup extends \App\Components\BaseComponent
{
    /** @var TestManager **/
    private $testManager;
    
    /** @var GroupManager **/
    private $groupManager;
    
    /** @var ClassificationManager **/
    private $classificationManager;
    
    /** @var Group **/
    protected $selectedGroup = null;
          
    public function __construct(
        TestManager $testManager,            
        GroupManager $groupManager,        
        ClassificationManager $classificationManager
    )
    {
        $this->testManager = $testManager;
        $this->groupManager = $groupManager;
        $this->classificationManager = $classificationManager;
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
        
        $form->addCheckbox('use_publication_date', "Datum publikace");
        $form->addText('publication_date')->setType('date');
        $form->addText('publication_time')->setType('time');
        
        $form->addCheckbox('use_deadline', "Datum odevzdání");
        $form->addText('deadline_date')->setType('date');
        $form->addText('deadline_time')->setType('time');
        
        $form->addSelect('questions_count', "Počet otázek", [
            '0' => "Použít všechny",
            '5' => "5 náhodných",
            '10' => "10 náhodných",
            '15' => "15 náhodných"
        ]);
        
        $form->addCheckbox('classification', 'Vytvořit známkování');
        $form->addCheckbox('random_sort', 'Náhodné pořadí otázek');
        
        $form->addSelect('number_of_repetitions', "Kolikrát se dá vyplnit", [
            '0' => "Neomezeně",
            '1' => "1x",
            '5' => "5x",
            '10' => "10x",            
            '20' => "20x"
        ]);
        
        $form->onSuccess[] = function($form, $values) {
            $group = $this->groupManager->getUserGroup($values->group_id, $this->presenter->activeUser, true);
            $test = $this->testManager->getTestForOwner($values->test_id, $this->presenter->activeUser->id, false);
            if(!$group || !$test) {
                $this->presenter->flashMessage("Skupina neexistuje.");
                $this->redirect('this');
            } 
            $testSetup = new \App\Model\Entities\Test\TestSetup;
            $testSetup->testId = $values->test_id;
            $testSetup->groupId = $values->group_id;
            $testSetup->numberOfRepetitions = $values->number_of_repetitions;
            $testSetup->timeLimit = $values->time_limit * 60;
            $testSetup->questionsCount = $values->questions_count == 0 ? null : $values->questions_count;
            $testSetup->randomSort = $values->random_sort ? true : false;
            if($values->classification) {
                $groupClassification = new ClassificationGroup();
                $groupClassification->group = new Group();
                $groupClassification->group->id = $testSetup->groupId;
                $groupClassification->type = ClassificationGroup::TYPE_TEST;
                $groupClassification->idPeriod = 1;
                $groupClassification->forAll = true;
                $groupClassification->name = $test->name;
                $testSetup->classificationGroupId = $this->classificationManager->createGroupClassification($groupClassification);
            }
            if($values->use_publication_date && $values->publication_date && $values->publication_time) {
               $testSetup->publicationTime = new \DateTime();
                $date = explode('-', $values->publication_date);
                $time = explode(':', $values->publication_time);
                $testSetup->publicationTime->setDate($date[0], $date[1], $date[2]);
                $testSetup->publicationTime->setTime($time[0], $time[1]); 
            } else {
               $testSetup->publicationTime = null; 
            }
            
            if($values->use_deadline && $values->deadline_date && $values->deadline_time) {
               $testSetup->deadline = new \DateTime();
                $date = explode('-', $values->deadline_date);
                $time = explode(':', $values->deadline_time);
                $testSetup->deadline->setDate($date[0], $date[1], $date[2]);
                $testSetup->deadline->setTime($time[0], $time[1]); 
            } else {
               $testSetup->deadline = null; 
            }
            if($values->id) {
                $testSetup->id = $values->id;
                $this->testManager->updateTestSetup($testSetup);
                $this->presenter->flashMessage("Zadání testu bylo upraveno.");
            } else {
                $this->testManager->createTestSetup($testSetup);
                $this->presenter->flashMessage("Test byl zadán do skupiny.");
            }
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
            'time_limit' => $testSetup->timeLimit ? ($testSetup->timeLimit/60) : null,
            'questions_count' => $testSetup->questionsCount,
            'classification' => !empty($testSetup->classificationGroupId),
            'random_sort' => $testSetup->randomSort,
            'number_of_repetitions' => $testSetup->numberOfRepetitions,
            'group_id' => $testSetup->groupId,
            'test_id' => $testSetup->testId,
            'id' => $testSetup->id
        ]);
        if($testSetup->deadline) {
            $this['form']->setDefaults([
                'use_deadline' => true,
                'deadline_date' => $testSetup->deadline->format("Y-m-d"),
                'deadline_time' => $testSetup->deadline->format("H:i")
            ]);
        }
        if($testSetup->publicationTime) {
            $this['form']->setDefaults([
                'use_publication_date' => true,
                'publication_date' => $testSetup->publicationTime->format("Y-m-d"),
                'publication_time' => $testSetup->publicationTime->format("H:i")
            ]);
        }
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
