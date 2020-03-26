<?php

namespace App\FrontModule\Components\Stream\Messages;

use App\Model\Manager\MessageManager;
use App\FrontModule\Components\Stream\ICommentForm;

abstract class Base extends \App\Components\BaseComponent
{
	/** @var MessageManager */
    protected $messageManager;
    
    /** @var ICommentForm **/
    protected $commentForm;
    	
    public function __construct(
        MessageManager $messageManager,
        ICommentForm $commentForm
    )
    {
        $this->messageManager = $messageManager;
        $this->commentForm = $commentForm;
    }
	
	public function handleSetLike($messageId, $enable) 
    {   
		if($this->messageManager->isMessageInGroup($messageId, $this->presenter->activeGroup->id)) {	
			$this->messageManager->setLike($messageId, $enable);
			$this->redrawControl('message-info');
		}
    }
	
	public function handleSetWatching($messageId, $enable) 
    {   
		if($this->messageManager->isMessageInGroup($messageId, $this->presenter->activeGroup->id)) {
			$this->messageManager->setWatching($messageId, $enable);
			$this->redrawControl('message-info');
		}        
    }
}
