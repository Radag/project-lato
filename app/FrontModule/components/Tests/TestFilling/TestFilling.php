<?php
namespace App\FrontModule\Components\Test;

use App\Model\Manager\TestManager;
use App\Model\Entities\Test\Test;
use App\Model\Entities\Test\Filling;
use App\Model\Entities\Test\Answer;
use App\Model\Entities\Test\TestSetup;

class TestFilling extends \App\Components\BaseComponent
{
    /** @var TestManager **/
    private $testManager;
    
    /** @var Test **/
    private $test = null;
    
    /** @var Filling **/
    private $filling = null;
    
    /** @var TestSetup **/
    private $testSetup = null;
    
    public function __construct(TestManager $testManager)
    {
        $this->testManager = $testManager;
    }
    
    public function render() 
    {
        $this->template->test = $this->test;
        $this->template->filling = $this->filling;
        $this->template->timeLeft = $this->getTimeLeft();
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
            if(in_array($question->id, $this->filling->questions)) {
                foreach($question->options as $option) {
                    $form->addCheckbox('opt_' . $question->id . '_' . $option->id);
                }
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
        $this->test = $this->testManager->getTest($this->filling->testId, $this->presenter->activeUser->id, $this->filling->questions);
        $this->testSetup = $this->testManager->getTestSetup($this->test->id, $this->filling->groupId);
        
        $timeLeft = $this->getTimeLeft();
        if($timeLeft && $timeLeft->invert === 1) {
            $this->filling->isFinished = 1;
            $this->testManager->updateFilling($this->filling);
        }
        
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
        
        $correctCount = 0;
        foreach($answers as $answer) {
            $answer->isCorrect = $this->isCorrect($answer);
            if($answer->isCorrect) {
                $correctCount++;
            }
            $this->testManager->saveAnswer($answer);
        }
        
        if(isset($form->getHttpData()['save_leave'])) {
            $this->filling->isFinished = true;
            $this->filling->correctCount = $correctCount;
            $this->filling->percent = round(100/$this->test->questionsCount * $correctCount);
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
    
    private function getTimeLeft() : ?\DateInterval
    {
        if($this->testSetup->timeLimit !== 0) {
             $interval = $this->secondsToTime($this->testSetup->timeLimit);
            $endDate = $this->filling->created->add($interval);
            return (new \DateTime())->diff($endDate);
        } else {
           return null;
        }       
    }

    private function secondsToTime($seconds) 
    {
        $dtF = new \DateTime("@0");
        $dtT = new \DateTime("@$seconds");
        return $dtF->diff($dtT);
    }
    
}
