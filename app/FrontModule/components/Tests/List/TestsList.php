<?php
namespace App\FrontModule\Components\Test;

use App\Model\Manager\TestManager;

class TestsList extends \App\Components\BaseComponent
{
    private $testManager;
    
    public function __construct(TestManager $testManager)
    {
        $this->testManager = $testManager;
    }
    
    public function render() {
        $this->template->tests = $this->testManager->getTests($this->presenter->activeUser);
        parent::render();
    }
    
}
