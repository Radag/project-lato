<?php

namespace App\FrontModule\Components\Stream\Messages;

use App\Model\Manager\TestManager;
use App\Model\Manager\TestSetupManager;
use App\Model\Manager\MessageManager;
use App\FrontModule\Components\Stream\ICommentForm;

class Test extends Base
{
    /** @var TestManager **/
    protected $testManager;
    
	//is neede in base
	/** @var MessageManager **/
    protected $messageManager;
   
    /** @var TestSetupManager **/
    protected $testSetupManager;
	
    protected $test = null;
   
    protected $id = null;	
	
    protected $comments = [];
      
    public function __construct(
		MessageManager $messageManager,
		TestManager $testManager,
		TestSetupManager $testSetupManager,
		ICommentForm $commentForm
	)
    {
        $this->messageManager = $messageManager;
        $this->testManager = $testManager;
        $this->testSetupManager = $testSetupManager;
        $this->commentForm = $commentForm;
    }
    
    public function render()
    {
        $this->template->test = $this->getTest();
        $this->template->message = $this->getTest()->message;
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
        if($this->test === null || $this->isControlInvalid()) {
            $this->test = current($this->testManager->getGroupTests($this->presenter->activeGroup, [$this->id]));
        }
        return $this->test;
    }
    
    public function setTest($id, $test, $group, $comments)
    {
        $this->id = $id;
        if($test) {
            $this->test = $test;
        } else {
			$this->test = $this->testManager->getGroupTests($group, [$id])[$id];
		}
		$this->comments = $comments;
    }
	
	public function createComponentCommentForm()
    {
        return new \Nette\Application\UI\Multiplier(function ($idMessage) {
            $commentForm = $this->commentForm->create();
			$message = new \App\Model\Entities\Message();
			$message->id = $idMessage;
            $commentForm->setMessage($message);
            $commentForm->setComments($this->comments);
            return $commentForm;
        });
    }
}
