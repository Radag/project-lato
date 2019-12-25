<?php

namespace App\FrontModule\Components\Stream;

use App\Model\Manager\MessageManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\TaskManager;
use App\Model\Manager\TestSetupManager;
use App\FrontModule\Components\Stream\ICommentForm;
use App\FrontModule\Components\TaskHeader\ITaskHeader;
use App\FrontModule\Components\Stream\ICommitTaskForm;
use App\Model\Manager\TestManager;
use App\FrontModule\Components\Test\ITestSetup;
use App\FrontModule\Components\Stream\Messages\ITest;
use App\FrontModule\Components\Stream\Messages\INormal;
use App\FrontModule\Components\Stream\Messages\ITask;
use Nette\Application\UI\Multiplier;

class MessagesColumn extends \App\Components\BaseComponent
{
    
    /** @var  MessageManager @inject */
    protected $messageManager;
    
    /** @var  GroupManager @inject */
    protected $groupManager;    
    
    /** @var  TestManager @inject */
    protected $testManager;    
    
    /** @var  ITaskHeader @inject */
    protected $taskHeaderFactory;

    /** @var  ICommentForm @inject */
    protected $commentForm;
    
    /** @var  TaskManager @inject */
    protected $taskManager;   
    
    /** @var  TestSetupManager @inject */
    protected $testSetupManager;
    
    /** @var  ITest @inject */
    protected $testMessage;
    
    /** @var  INormal @inject */
    protected $normalMessage;
    
    /** @var  ITask @inject */
    protected $taskMessage;
    
    /** @var  ITestSetup @inject */
    protected $testSetup;
    
    /** @var ICommitTaskForm */
    protected $commitTaskForm;  
      
    protected $filter = 'all';
    
    protected $singleMode = false;
    
    protected $messages = [];    
    protected $tests = [];
    
    protected $comments = [];
    
    public function __construct(
        MessageManager $messageManager,
        GroupManager $groupManager,
        ICommentForm $commentForm,
        ITaskHeader $taskHeaderFactory,
        TaskManager $taskManager,            
        ICommitTaskForm $commitTaskForm,            
        TestManager $testManager,
        ITestSetup $testSetup,            
        ITest $testMessage,
        INormal $normalMessage,
        ITask $taskMessage,
        TestSetupManager $testSetupManager
    )
    {
        $this->messageManager = $messageManager;
        $this->groupManager = $groupManager;
        $this->commentForm = $commentForm;
        $this->taskHeaderFactory = $taskHeaderFactory;
        $this->taskManager = $taskManager;
        $this->commitTaskForm = $commitTaskForm;
        $this->testManager = $testManager;
        $this->testSetup = $testSetup;
        $this->testSetupManager = $testSetupManager;
        $this->testMessage = $testMessage;
        $this->normalMessage = $normalMessage;
        $this->taskMessage = $taskMessage;
    }    
        
    public function render() 
    {
        $streamMessages = [];
        if(!$this->singleMode) {            
            $this->tests = $this->testManager->getGroupTests($this->presenter->activeGroup->id);
            $data = $this->messageManager->getMessages($this->presenter->activeGroup, $this->presenter->activeUser, $this->filter);
            $this->messages = $data['messages'];
            foreach($data['messages'] as $message) {
                $streamMessages[$message->created->getTimestamp()] = (object)[
                    'id' => $message->id,
                    'type' => $message->type === 'task' ? 'task' : 'normal'
                ];
            }
            foreach($this->tests  as $test) {
                $streamMessages[$test->created->getTimestamp()] = (object)[
                    'id' => $test->id,
                    'type' => 'test'
                ];
            }
            krsort($streamMessages);
            $this->comments = $data['comments'];
        } else {
            $message = $this->messageManager->getMessage($this->singleMode, $this->presenter->activeUser, $this->presenter->activeGroup);
            if($message) {
                $this->comments[$this->singleMode] = $this->messageManager->getMessageComments($this->singleMode);
                $this->messages = [$message->id => $message];
                $this->template->singleMessage = $message;
            }
        }
        $this->template->singleMode = $this->singleMode;
        $this->template->messages = $streamMessages;
        $this->template->activeGroup = $this->presenter->activeGroup;
        $this->template->isOwner = $this->presenter->activeGroup->relation === 'owner' ? true : false;
        parent::render();
    } 
        
    public function redrawTasks()
    {
        $this->redrawControl('messages');
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;
        $this->redrawControl();
    }
    
    public function setSingleMode($idMessage)
    {
        $this->singleMode = $idMessage;
    }
    
    public function createComponentCommitTaskForm()
    {
        return $this->commitTaskForm->create();    
    }
    
    public function createComponentTestSetupForm()
    {
        return $this->testSetup->create();
    }
    
    public function createComponentMessage()
    {
        return new Multiplier(function($id) 
        {
            $normal = $this->normalMessage->create();            
            $normal->setMessage($id, isset($this->message[$id]) ? $this->message[$id] : null);
            return $normal;
        });
    }
    
    public function createComponentTask()
    {
        return new Multiplier(function($id) 
        {
            $task = $this->taskMessage->create();
            $task->setMessage($id, isset($this->message[$id]) ? $this->message[$id] : null);
            return $task;
        });
    }
    
    public function createComponentTest()
    {
        return new Multiplier(function($id) 
        {
            $test = $this->testMessage->create();
            $test->setTest($id, isset($this->tests[$id]) ? $this->tests[$id] : null);
            return $test;
        });
    }
}
