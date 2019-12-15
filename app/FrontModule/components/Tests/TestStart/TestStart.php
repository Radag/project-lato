<?php
namespace App\FrontModule\Components\Test;

use App\Model\Manager\TestManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\TestSetupManager;
use App\Model\Entities\Test\Filling;
use App\Model\Entities\Test\Test;
use App\Model\Entities\Test\TestSetup;

class TestStart extends \App\Components\BaseComponent
{
    /** @var TestManager **/
    private $testManager;
    
    /** @var TestSetupManager **/
    private $testSetupManager;
    
    /** @var GroupManager **/
    private $groupManager;
    
    /** @var Test **/
    private $test = null;
    
    /** @var int **/
    private $setupId = null;
    
    /** @var TestSetup **/
    private $testSetup = null;
    
    public function __construct(
        TestManager $testManager,
        GroupManager $groupManager,            
        TestSetupManager $testSetupManager
    )
    {
        $this->testManager = $testManager;
        $this->groupManager = $groupManager;
        $this->testSetupManager = $testSetupManager;
    }
    
    public function render() 
    {
        $this->template->test = $this->test;
        $this->template->testSetup = $this->testSetup;
        $this->template->testLimitText = $this->getTimeLimitText();
        parent::render();
    }
    
    
    public function setId(int $setupId) 
    {
        $this->setupId = $setupId;
        $this->testSetup = $this->testSetupManager->getTestSetup($setupId);
        if(!$this->testSetup || !$this->groupManager->isUserInGroup($this->presenter->activeUser->id, $this->testSetup->groupId)) {
            $this->presenter->flashMessage("Tento test neexistuje!");
            $this->presenter->redirect(':Front:Homepage:noticeboard');
        }
        $summary = $this->testManager->getStudentTestSummary($this->testSetup->id, $this->presenter->activeUser->id);
        if($this->testSetup->numberOfRepetitions !== null && $summary->filledCount >= $this->testSetup->numberOfRepetitions) {
            $this->presenter->flashMessage("Tento test již nemůžete vyplnit.");
            $this->presenter->redirect(':Front:Homepage:noticeboard');
        }        
        $this->test = $this->testManager->getTestForUser($this->testSetup->testId);
        
//        if($this->groupTestId) {
//            
//        } else {
//            $this->testSetup = new TestSetup();
//            $this->testSetup->numberOfRepetitions = 0;
//            $this->testSetup->timeLimit = 0;
//            $this->testSetup->questionsCount = null;
//        }
    }

    public function handleStartTest()
    {
        $filling = new Filling();
        $filling->userId = $this->presenter->activeUser->id;
        $filling->setupId = $this->testSetup->id;
        $selectedQuestions = [];
        if($this->testSetup->questionsCount === null) {
            $this->testSetup->questionsCount = $this->test->questionsCount;
            foreach($this->test->questions as $question) {
                $selectedQuestions[] = $question->id;
            }
        } else {
            if($this->test->questionsCount < $this->testSetup->questionsCount) {
                $this->testSetup->questionsCount = $this->test->questionsCount;
            }
            $randoms = [];
            while(count($randoms) < $this->testSetup->questionsCount) {
                $rand = rand(1, $this->test->questionsCount);
                if(!in_array($rand, $randoms)) {
                    $randoms[] = $rand;
                }
            }
            
            $i = 1;
            foreach($this->test->questions as $question) {
                if(in_array($i, $randoms)) {
                    $selectedQuestions[] = $question->id;
                }
                $i++;
            } 
        }
        if($this->testSetup->randomSort) {
            shuffle($selectedQuestions);
        }
        
        $filling->questionsCount = $this->testSetup->questionsCount;
        $filling->questions = $selectedQuestions;
        
        $fillingId = $this->testManager->createFilling($filling);
        $this->presenter->redirect("Tests:filling", ['id' => $fillingId]);
    }
    
    private function getTimeLimitText()
    {
        if($this->testSetup->timeLimit > 0) {
            $minutes = null;
            if($this->testSetup->timeLimit >= 60) {
                $minutes = floor($this->testSetup->timeLimit / 60);
                if($minutes == 1) {
                    $minutes .= " minuta";
                } else if($minutes > 1 && $minutes < 5) {
                    $minutes .= " minuty";
                } else {
                    $minutes .= " minut";
                }
            }
            $seconds = $this->testSetup->timeLimit % 60;
            if($seconds == 0) {
                $seconds = null;
            } else if($seconds === 1) {
                $seconds .= " sekunda";
            } else if($seconds > 1 && $seconds < 5) {
                $seconds .= " sekundy";
            } else {
                $seconds .= " sekund";
            }
            if($seconds && $minutes) {
                $testLimitText = $minutes . " a " . $seconds;
            } else if($seconds != 0) {
                $testLimitText = $seconds;
            } else {
                $testLimitText = $minutes;
            }
            return $testLimitText;
        } else {
            return null;
        }
    }
}
