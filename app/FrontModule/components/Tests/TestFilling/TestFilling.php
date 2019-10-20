<?php
namespace App\FrontModule\Components\Test;

use App\Model\Manager\TestManager;
use App\Model\Entities\Test\Test;
use App\Model\Entities\Test\Filling;
use App\Model\Entities\Test\Answer;

class TestFilling extends \App\Components\BaseComponent
{
    /** @var TestManager **/
    private $testManager;
    
    /** @var Test **/
    private $test = null;
    
    /** @var Filling **/
    private $filling = null;
    
    public function __construct(TestManager $testManager)
    {
        $this->testManager = $testManager;
    }
    
    public function render() 
    {
        $this->template->test = $this->test;
        if($this->filling->isFinished) {
            $this->setTemplateName('TestResults');
        } else {
            $this->setTemplateName('TestBody');
        }
        parent::render();
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm(true);
        foreach($this->test->questions as $question) {
            foreach($question->options as $option) {
                $form->addCheckbox('opt_' . $question->id . '_' . $option->id);
            }
        }
        
        $form->addSubmit('save', 'Průběžně uložit');
        $form->addSubmit('save_leave', 'Odevzdat');
        $form->onSuccess[] = [$this, 'processForm'];
        
        return $form;
    }
    
    public function setId($id) 
    {
        $this->filling = $this->testManager->getFilling($id);
        $this->test = $this->testManager->getTest($this->filling->testId, $this->presenter->activeUser->id);
    }
    
    public function processForm(\Nette\Application\UI\Form $form, $values) 
    {   
        foreach($values as $key => $val) {
            $name = explode('_', $key);
            if($name[0] === 'opt') {
                $answer = new Answer();
                $answer->fillingId = $this->filling->id;
                $answer->questionId = $name[1];
                $answer->optionId = $name[2];
                $answer->answerBinary = $val;
                $this->testManager->saveAnswer($answer);
            }
        }
        if(isset($form->getHttpData()['save_leave'])) {
            $this->filling->isFinished = true;
            $this->testManager->updateFilling($this->filling);
        }
        $this->presenter->redirect('this');
    }
}
