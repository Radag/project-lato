<?php
namespace App\FrontModule\Components\Test;

use App\Model\Manager\TestManager;
use App\Model\Entities\Test\Filling;
use App\Model\Entities\Test\Test;
use App\Model\Entities\Test\TestSetup;
use App\Model\Manager\UserManager;

class TestDisplay extends \App\Components\BaseComponent
{
    /** @var TestManager **/
    private $testManager;
    
    /** @var UserManager **/
    private $userManager = null;
    
    /** @var Test **/
    private $test = null;
    
    /** @var Filling **/
    private $filling = null;
    
    /** @var TestSetup **/
    private $testSetup = null;
    
    
    
    public function __construct(
        TestManager $testManager,
        UserManager $userManager
    )
    {
        $this->testManager = $testManager;
        $this->userManager = $userManager;
    }
    
    public function render() 
    {
        $this->template->test = $this->test;
        $this->template->testSetup = $this->testSetup;
        $this->template->filling = $this->filling;
        $this->template->user = $this->userManager->get($this->filling->userId);
        parent::render();
    }
    
    
    public function setId(int $fillingId) 
    {
        $this->filling = $this->testManager->getFilling($fillingId);
        $this->testSetup = $this->filling->setup;
        $this->test = $this->testManager->getTest($this->testSetup->testId, $this->presenter->activeUser->id, $this->filling->questions);
        $this->presenter['topPanel']->setTitle($this->test->name . " - procházení");
        foreach($this->test->questions as $question) {
            foreach($question->options as $option) {
                if(in_array($option->id, $this->filling->answers[$question->id]->answer->options)) {
                    if($option->isCorrect) {
                        $option->answerCorrection = "correct";
                    } else {
                        $option->answerCorrection = "wrong";
                    }
                } elseif($option->isCorrect) {
                    $option->answerCorrection = "correction";
                }    
            }                
        }
    }

  
}
