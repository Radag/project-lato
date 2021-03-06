<?php
namespace App\FrontModule\Components\Test;

use App\Model\Manager\TestManager;
use App\Model\Entities\Test\Option;
use App\Model\Entities\Test\Question;
use App\Model\Entities\Test\Test;
use Nette\Application\UI\Form;


class Editor extends \App\Components\BaseComponent
{
    /** @var TestManager **/
    private $testManager;
    
    /** @var Test **/
    private $test = null;
    
    public function __construct(TestManager $testManager)
    {
        $this->testManager = $testManager;
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm(true);
        $form->addText('name', 'Název testu')
             ->setRequired("Musíte zadat název testu."); 
        $form->addSubmit('save', 'Uložit');
        $form->addSubmit('save_leave', 'Uložit a odejít');
        $form->addHidden('id');
        $form->onSuccess[] = [$this, 'processForm'];
        
        return $form;
    }
    
    public function render() 
    {
        if($this->test === null) {
            $this->template->questions = [
                (object)[
                    'number' => 1,
                    'question' => "",
                    'id' => "",
                    'options' => []
                ]
            ]; 
        } else {
            $this->template->questions = $this->test->questions;
        }
       
        parent::render();      
    }

    public function setId($id) 
    {
        $this->test = $this->testManager->getTestForOwner($id, $this->presenter->activeUser->id);
        $this['form']->setDefaults([
            'name' => $this->test->name,
            'id' => $id
        ]);
    }
    
    public function processForm(Form $form, $values) 
    {
        $test = $this->saveTest($values);

        $this->testManager->saveHistory($test->id ,$form->getHttpData()['questions'], $values->name);
        if(isset($form->getHttpData()['questions']) && is_array($form->getHttpData()['questions'])) {
            $this->saveQuestions($test->id, $form->getHttpData()['questions']);
        }

        if(isset($form->getHttpData()['optionsToDelete']) && is_array($form->getHttpData()['optionsToDelete'])) {
            foreach($form->getHttpData()['optionsToDelete'] as $optionId) {
                $this->testManager->deleteOption($optionId, $test->id);
            }
        }

        if(isset($form->getHttpData()['questionsToDelete']) && is_array($form->getHttpData()['questionsToDelete'])) {
            foreach($form->getHttpData()['questionsToDelete'] as $questionId) {
                $this->testManager->deleteQuestion($questionId, $test->id);
            }
        }

        $this->presenter->payload->invalidForm = true;
        $this->presenter->flashMessage('Uoženo', 'success');
        if(isset($form->getHttpData()['save_leave'])) {
            $this->presenter->redirect(':Front:Tests:list');
        } else {
            $this->presenter->redirect('this', ['id' => $test->id]);
        }        
    }
    
    private function saveTest($values) 
    {
        $test = null;
        if($values->id) {
            $test = $this->testManager->getTestForOwner($values->id, $this->presenter->activeUser->id);
        }
        if($test === null) {
            $test = new Test();
        }
        $test->name = $values->name;
        if($test->id === null) {
            $test->id = $this->testManager->createTest($test, $this->presenter->activeUser);
        } else {
            $this->testManager->updateTest($test, $this->presenter->activeUser);
        }
        return $test;
    }
    
    private function saveQuestions(int $testId, array $questions) 
    {
        $number = 1;
        foreach($questions as $question) {
        	if($question['name']) {
				$questionObject = new Question();
				$questionObject->question = $question['name'];
				$questionObject->testId = $testId;
				$questionObject->number = $number;

				if(empty($question['id'])) {
					$questionObject->id = $this->testManager->insertQuestion($questionObject);
				} else {
					$questionObject->id = $question['id'];
					$this->testManager->updateQuestion($questionObject, $testId);
				}

				if(isset($question['options']) && is_array($question['options'])) {
					$this->saveOptions($questionObject->id, $testId, $question['options']);
				}
				$number++;
			}
        }
    }
    
    private function saveOptions(int $questionId, int $testId, array $options) 
    {
    	$optionNumber = 0;
        foreach($options as $i => $option) {
            if(!empty($option['text'])) {
				$optionNumber++;
                $optionObject = new Option();
                $optionObject->questionId = $questionId;
                $optionObject->name = $option['text'];
                $optionObject->number = $optionNumber;
                $optionObject->isCorrect = isset($option['correct']) ? true : false;

                if(empty($option['id'])) {
                    $optionObject->id = $this->testManager->insertOption($optionObject);
                } else {
                    $optionObject->id = $option['id'];
                    $this->testManager->updateOption($optionObject, $testId);
                }
            } else if(!empty($option['id'])) {
                $this->testManager->deleteOption($option['id'], $testId);
            }
        }
    }
}
