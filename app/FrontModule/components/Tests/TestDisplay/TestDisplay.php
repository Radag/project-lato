<?php
namespace App\FrontModule\Components\Test;

use App\Model\Manager\TestManager;
use App\Model\Entities\Test\Filling;
use App\Model\Entities\Test\Test;
use App\Model\Entities\Test\TestSetup;

class TestDisplay extends \App\Components\BaseComponent
{
    /** @var TestManager **/
    private $testManager;
    
    /** @var Test **/
    private $test = null;
    
    /** @var int **/
    private $setupId = null;
    
    /** @var TestSetup **/
    private $testSetup = null;
    
    public function __construct(TestManager $testManager)
    {
        $this->testManager = $testManager;
    }
    
    public function render() 
    {
        $this->template->test = $this->test;
        $this->template->testSetup = $this->testSetup;
        parent::render();
    }
    
    
    public function setId(int $setupId) 
    {
        $this->setupId = $setupId;
        $this->testSetup = $this->testManager->getTestSetup($setupId);
        $this->test = $this->testManager->getTest($this->testSetup->testId, $this->presenter->activeUser->id);    
    }

  
}
