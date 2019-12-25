<?php

namespace App\FrontModule\Components\Stream\Messages;

use App\Model\Manager\TestManager;
use App\Model\Manager\TestSetupManager;

class Test extends \App\Components\BaseComponent
{
    /** @var TestManager **/
    protected $testManager;
    
    /** @var TestSetupManager **/
    protected $testSetupManager;
     
    protected $test = null;
   
    protected $id = null;
      
    public function __construct(TestManager $testManager, TestSetupManager $testSetupManager)
    {
        $this->testManager = $testManager;
        $this->testSetupManager = $testSetupManager;
    }
    
    public function render()
    {
        $this->template->test = $this->getTest();
        $this->template->activeGroup = $this->presenter->activeGroup;
        parent::render();
    }
    
    public function handleEditTest($setupId)
    {
        if($this->testSetupManager->checkOwner($setupId)) {
            $this->presenter['stream']['messagesColumn']['testSetupForm']->setDefault($setupId);
            $this->presenter['stream']['messagesColumn']->redrawControl('testSetupForm');    
        }
    }

    public function handleDeleteTest($setupId) 
    {   
        if($this->testSetupManager->checkOwner($setupId)) {
            $this->testManager->deleteGroupTest($setupId);
            $this->presenter->flashMessage('Test byl smazán.');
        }
        $this->presenter['stream']['messagesColumn']->redrawControl();
    }
    
    protected function getTest()
    {
        if($this->test === null) {
            $this->test = $this->testManager->getTestForUser($this->id);
        }
        return $this->test;
    }
    
    public function setTest($id, $test)
    {
        $this->id = $id;
        if($test) {
            $this->test = $test;
        }
    }
}
