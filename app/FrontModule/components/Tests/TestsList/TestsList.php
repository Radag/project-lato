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
        $test = $this->testManager->getTestForOwner($testId, $this->presenter->activeUser->id);
        if($test) {
            $this['testSetup']->setTestId($test->id);
        }
    }
    
    public function handleDeleteTest($testId)
    {
        $test = $this->testManager->getTestForOwner($testId, $this->presenter->activeUser->id);
        if($test) {
            $this->testManager->deleteTest($test->id);
        }
        $this->presenter->redirect('this');
    }
    
    public function createComponentTestSetup()
    {
        return $this->testSetup->create();
    }
}
