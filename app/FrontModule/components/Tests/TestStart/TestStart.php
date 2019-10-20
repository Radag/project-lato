<?php
namespace App\FrontModule\Components\Test;

use App\Model\Manager\TestManager;

class TestStart extends \App\Components\BaseComponent
{
    /** @var TestManager **/
    private $testManager;
    
    /** @var Test **/
    private $test = null;
    
    public function __construct(TestManager $testManager)
    {
        $this->testManager = $testManager;
    }
    
    public function render() 
    {
        $this->template->test = $this->test;
        
        parent::render();
    }
    
    
    public function setId($id) 
    {
        $this->test = $this->testManager->getTest($id, $this->presenter->activeUser->id);    
    }

    public function handleStartTest()
    {
        $fillingId = $this->testManager->createFilling($this->test, $this->presenter->activeUser);
        $this->presenter->redirect("Tests:filling", ['id' => $fillingId]);
    }
}
