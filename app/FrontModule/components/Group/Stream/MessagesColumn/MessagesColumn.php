<?php

namespace App\FrontModule\Components\Stream;

use App\Model\Manager\MessageManager;
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

    /** @var  TestManager @inject */
    protected $testManager;    
            
    /** @var  ITest @inject */
    protected $testMessage;
    
    /** @var  INormal @inject */
    protected $normalMessage;
    
    /** @var  ITask @inject */
    protected $taskMessage;
    
    /** @var  ITestSetup @inject */
    protected $testSetup;
     
    protected $filter = 'all';
        
    public $messages = [];    
    public $tests = [];
    public $comments = [];
    
    public function __construct(
        MessageManager $messageManager,           
        TestManager $testManager,
        ITestSetup $testSetup,            
        ITest $testMessage,
        INormal $normalMessage,            
        ITask $taskMessage
    )
    {
        $this->messageManager = $messageManager;
        $this->testManager = $testManager;
        $this->testSetup = $testSetup;
        $this->testMessage = $testMessage;
        $this->normalMessage = $normalMessage;
        $this->taskMessage = $taskMessage;
    }    
        
    public function render() 
    {
        $streamMessages = [];
        $this->tests = $this->testManager->getGroupTests($this->presenter->activeGroup);
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
        
        $this->template->messages = $streamMessages;
        $this->template->activeGroup = $this->presenter->activeGroup;
        $this->template->isOwner = $this->presenter->activeGroup->relation === 'owner' ? true : false;
        parent::render();
    } 
    
    public function setFilter($filter)
    {
        $this->filter = $filter;
        $this->redrawControl();
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
            $normal->setMessage($id, isset($this->messages[$id]) ? $this->messages[$id] : null);
            return $normal;
        });
    }
    
    public function createComponentTask()
    {
        return new Multiplier(function($id) 
        {
            $task = $this->taskMessage->create();
            $task->setMessage($id, isset($this->messages[$id]) ? $this->messages[$id] : null);
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
