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
                'id' => $test->setup->id,
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
	
	public function handleMessageDisplayed()
	{
		$messageId = $this->presenter->getParameter('postId');
		if($this->messageManager->isMessageInGroup($messageId, $this->presenter->activeGroup->id)) {
			$this->messageManager->setMessageDisplayed($messageId, $this->presenter->activeUser->id);		
		}
		
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
			$message = isset($this->messages[$id]) ? $this->messages[$id] : null;
			$comments = isset($this->comments[$id]) ? $this->comments[$id] : null;
            $normal->setMessage($id, $message, $comments);
            return $normal;
        });
    }
    
    public function createComponentTask()
    {
        return new Multiplier(function($id) 
        {
            $task = $this->taskMessage->create();
			$message = isset($this->messages[$id]) ? $this->messages[$id] : null;
			$comments = isset($this->comments[$id]) ? $this->comments[$id] : null;
            $task->setMessage($id, $message, $comments);
            return $task;
        });
    }
    
    public function createComponentTest()
    {
        return new Multiplier(function($id) 
        {
            $test = $this->testMessage->create();
			$testData = isset($this->tests[$id]) ? $this->tests[$id] : null;
			$comments = [];
			if($testData) {
				$comments = isset($this->comments[$testData->message->id]) ? $this->comments[$testData->message->id] : [];
			}
            $test->setTest($id, $testData, $this->presenter->activeGroup, $comments);
            return $test;
        });
    }
}
