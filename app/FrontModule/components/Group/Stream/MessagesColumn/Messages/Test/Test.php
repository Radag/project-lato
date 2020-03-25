<?php

namespace App\FrontModule\Components\Stream\Messages;

use App\Model\Manager\TestManager;
use App\Model\Manager\TestSetupManager;
use App\FrontModule\Components\Stream\ICommentForm;

class Test extends \App\Components\BaseComponent
{
    /** @var TestManager **/
    protected $testManager;
    
    /** @var TestSetupManager **/
    protected $testSetupManager;
     
	/** @var ICommentForm **/
    protected $commentForm;	
	
    protected $test = null;
   
    protected $id = null;
      
    public function __construct(
		TestManager $testManager, 
		TestSetupManager $testSetupManager,
		ICommentForm $commentForm
	)
    {
        $this->testManager = $testManager;
        $this->testSetupManager = $testSetupManager;
        $this->commentForm = $commentForm;
    }
    
    public function render()
    {
        $this->template->test = $this->getTest();
        $this->template->messageId = $this->getTest()->setup->messageId;
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
    
    public function setTest($id, $test, $group)
    {
        $this->id = $id;
        if($test) {
            $this->test = $test;
        } else {
			$this->test = $this->testManager->getGroupTests($group, [$id])[$id];
		}
    }
	
	public function createComponentCommentForm()
    {
        return new \Nette\Application\UI\Multiplier(function ($idMessage) {
            $commentForm = $this->commentForm->create();
			$message = new \App\Model\Entities\Message();
			$message->id = $idMessage;
            $commentForm->setMessage($message);
            if(isset($this->parent->parent->comments[$idMessage])) {
                $commentForm->setComments($this->parent->parent->comments[$idMessage]);
            }
            return $commentForm;
        });
    }
}
