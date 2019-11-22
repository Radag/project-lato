<?php
namespace App\FrontModule\Components\Test;

use App\Model\Manager\TestManager;
use App\Model\Entities\Test\Test;
use App\Model\Entities\Test\Filling;
use App\Model\Entities\Test\Answer;
use App\Model\Entities\Test\TestSetup;
use App\Service\TestService;

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
    
    /** @var TestService **/
    private $testService = null;
    
    public function __construct(
        TestManager $testManager,
        TestService $testService
    )
    {
        $this->testManager = $testManager;
        $this->testService = $testService;        
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
        
        $this->testService->getTestForm($form, $this->test, $this->filling);

        $form->addSubmit('save', 'Průběžně uložit');
        $form->addSubmit('save_leave', 'Odevzdat');
        $form->onSuccess[] = [$this, 'processForm'];
        
        return $form;
    }
    
    public function setId($id) 
    {
        $this->filling = $this->testManager->getFilling($id);
        $this->test = $this->testManager->getTest($this->filling->setup->testId, $this->presenter->activeUser->id, $this->filling->questions);
        $this->testSetup = $this->filling->setup;
        
        $timeLeft = $this->getTimeLeft();
        if($timeLeft && $timeLeft->invert === 1) {
            $this->filling->isFinished = 1;
            $this->testManager->updateFilling($this->filling);
        }
        
        foreach($this->filling->answers as $answer) {
            foreach($answer->answer as $options) {
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
        $finish = isset($form->getHttpData()['save_leave']);
        $this->testService->saveTestForm($values, $this->filling, $this->test, $finish);   
        $this->presenter->redirect('this');
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
