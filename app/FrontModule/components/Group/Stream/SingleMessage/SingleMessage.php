<?php

namespace App\FrontModule\Components\Stream;

use App\Model\Manager\MessageManager;
use App\FrontModule\Components\Stream\ICommitTaskForm;
use App\FrontModule\Components\Stream\Messages\ITest;
use App\FrontModule\Components\Stream\Messages\INormal;
use App\FrontModule\Components\Stream\Messages\ITask;

class SingleMessage extends \App\Components\BaseComponent
{
    
    /** @var  MessageManager @inject */
    protected $messageManager;  
            
    /** @var  ITest @inject */
    protected $testMessage;
    
    /** @var  INormal @inject */
    protected $normalMessage;
    
    /** @var  ITask @inject */
    protected $taskMessage;
        
    /** @var ICommitTaskForm */
    protected $commitTaskForm;  
          
    protected $id = null;
    protected $message = null;
    
    public function __construct(
        MessageManager $messageManager,
        ICommitTaskForm $commitTaskForm,          
        ITest $testMessage,
        INormal $normalMessage,            
        ITask $taskMessage
    )
    {
        $this->messageManager = $messageManager;
        $this->commitTaskForm = $commitTaskForm;
        $this->testMessage = $testMessage;
        $this->normalMessage = $normalMessage;
        $this->taskMessage = $taskMessage;
    }    
        
    public function render() 
    {
        $this->template->type = $this->message->type === 'task' ? 'task' : 'normal';
        $this->template->activeGroup = $this->presenter->activeGroup;
        $this->template->isOwner = $this->presenter->activeGroup->relation === 'owner' ? true : false;
        parent::render();
    } 
    
    public function setId($id)
    {
        $this->message = $this->messageManager->getMessage($id, $this->presenter->activeUser, $this->presenter->activeGroup);
        if($this->message) {
            //$this->comments = $this->messageManager->getMessageComments($id);
        } else {
            $this->presenter->redirect('Group:default', ['id'=>$this->presenter->activeGroup->id]);
        }
    }
    
    public function createComponentCommitTaskForm()
    {
        return $this->commitTaskForm->create();    
    }
    
    public function createComponentMessage()
    {
        $normal = $this->normalMessage->create();            
        $normal->setMessage($this->message->id, $this->message);
        return $normal;
    }
    
    public function createComponentTask()
    {
        $task = $this->taskMessage->create();
        $task->setMessage($this->message->id, $this->message, true);
        return $task;
    }
    
    public function createComponentTest()
    {
        $test = $this->testMessage->create();
        $test->setTest($this->message->id, $this->message);
        return $test;
    }
}
