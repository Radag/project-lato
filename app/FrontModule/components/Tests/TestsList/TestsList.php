<?php
namespace App\FrontModule\Components\Test;

use App\Model\Manager\TestManager;
use App\FrontModule\Components\Test\ITestSetup;

class TestsList extends \App\Components\BaseComponent
{
    /** @var TestManager */
    private $testManager;
    
    /** @var ITestSetup */
    private $testSetup;
    
    public function __construct(
        TestManager $testManager,
        ITestSetup $testSetup
    )
    {
        $this->testManager = $testManager;
        $this->testSetup = $testSetup;
    }
    
    public function render() {
        $this->template->tests = $this->testManager->getTests($this->presenter->activeUser);
        parent::render();
    }
    
    public function handleSetupTest($testId)
    {
        $this['testSetup']->setTestId($testId);
    }
    
    public function handleDeleteTest($testId)
    {
        $this->testManager->deleteTest($testId);
        $this->presenter->redirect('this');
    }
    
    public function createComponentTestSetup()
    {
        return $this->testSetup->create();
    }
}
