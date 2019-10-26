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
        foreach($this->filling->answers as $answer) {
            $correctAnswers = json_decode($answer->answer);
            foreach($correctAnswers as $options) {
                foreach($options as $optionId) {
                    $this['form']->setDefaults([
                        'opt_' . $answer->questionId . '_' . $optionId => true
                    ]);    
                }  
            }
        }
    }
    
    public function processForm(\Nette\Application\UI\Form $form, $values) 
    {   
        $this->testManager->clearTestAnswers($this->filling->id);
        $questions = $this->testManager->getTest($this->filling->testId, $this->presenter->activeUser->id);
        
        $answers = [];
        foreach($values as $key => $val) {
            $name = explode('_', $key);
            if($name[0] === 'opt') {
                if(!isset($answers[$name[1]])) {
                    $answer = new Answer();
                    $answer->fillingId = $this->filling->id;
                    $answer->questionId = $name[1];
                    $answer->question = $questions->questions[$name[1]];
                    if($answer->question->type === 'options') {
                        $answer->answer = (object)['options' => []];
                    }
                    $answers[$name[1]] = $answer;
                }
                if($answers[$name[1]]->question->type === 'options' && $val) {
                    $answer->answer->options[] = (int)$name[2];
                }
            }
        }
        
        foreach($answers as $answer) {
            $answer->isCorrect = $this->isCorrect($answer);
            $this->testManager->saveAnswer($answer);
        }
        
        
        if(isset($form->getHttpData()['save_leave'])) {
            $this->filling->isFinished = true;
            $this->testManager->updateFilling($this->filling);
        }
        $this->presenter->redirect('this');
    }
    
    private function isCorrect(Answer $answer)
    {
        if($answer->question->type === 'options') {
            $correct = [];
            foreach($answer->question->options as $option) {
                if($option->isCorrect) {
                    $correct[] = $option->id;
                }                
            }
            sort($correct);
            sort($answer->answer->options);
            if($correct == $answer->answer->options) {
                return true;
            }
        }
        return false;        
    }
}
