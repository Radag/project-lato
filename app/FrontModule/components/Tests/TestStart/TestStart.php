<?php
namespace App\FrontModule\Components\Test;

use App\Model\Manager\TestManager;
use App\Model\Entities\Test\Filling;
use App\Model\Entities\Test\Test;

class TestStart extends \App\Components\BaseComponent
{
    /** @var TestManager **/
    private $testManager;
    
    /** @var Test **/
    private $test = null;
    
    /** @var int **/
    private $groupId = null;
    
    public function __construct(TestManager $testManager)
    {
        $this->testManager = $testManager;
    }
    
    public function render() 
    {
        $this->template->test = $this->test;
        
        parent::render();
    }
    
    
    public function setId($id, $groupId) 
    {
        $this->groupId = $groupId;
        $this->test = $this->testManager->getTest($id, $this->presenter->activeUser->id);    
    }

    public function handleStartTest()
    {
        $filling = new Filling();
        $filling->testId = $this->test->id;
        $filling->userId = $this->presenter->activeUser->id;
        $filling->groupId = $this->groupId;
        $filling->questionCount = $this->test->questionsCount;
        $fillingId = $this->testManager->createFilling($filling);
        $this->presenter->redirect("Tests:filling", ['id' => $fillingId]);
    }
}
