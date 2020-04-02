<?php

namespace App\FrontModule\Components\Stream\Messages;


class Normal extends Base
{
	
    /** @var \App\Model\Entities\Message */
    protected $message = null;
    
    protected $isDetail = false;
    
    protected $id = null;
	
    protected $comments = [];
    
    public function render()
    {
        $this->template->message = $this->getMessage();
        $this->template->activeGroup = $this->presenter->activeGroup;
        $this->template->isDetail = $this->isDetail;
        parent::render();
    }
    
    public function handleEditMessage()
    {
        if($this->messageManager->canUserEditMessage($this->id, $this->presenter->activeUser, $this->presenter->activeGroup)) {
            $this->presenter['stream']['messageForm']->setDefaults($this->getMessage());
            $this->presenter['stream']['messageForm']->redrawControl('messageForm');
        }        
    }

    public function handleDeleteMessage() 
    {   
        if($this->messageManager->canUserEditMessage($this->id, $this->presenter->activeUser, $this->presenter->activeGroup)) {
            $this->messageManager->deleteMessage($this->id, true);
            $this->presenter->flashMessage('Zpráva byla smazána.');
        }
        $this->redrawControl();
    }

    public function handleRenewMessage() 
    {   
        if($this->messageManager->canUserEditMessage($this->id, $this->presenter->activeUser, $this->presenter->activeGroup)) {
            $this->messageManager->deleteMessage($this->id, false);
            $this->presenter->flashMessage('Zpráva byla obnovena.');
        }
        $this->redrawControl();
    }
    
	
    public function handleTopMessage($enable = true) 
    {
        if($this->presenter->activeGroup->relation === 'owner') {
            $this->messageManager->topMessage($this->id, $enable);
            if($enable) {
                $this->presenter->flashMessage('Zpráva byla posunuta nahoru.');
            } else {
                $this->presenter->flashMessage('Zrušeno topování zprávy.'); 
            }
        }
        
        $this->redrawControl();
    }

    public function createComponentCommentForm()
    {
        return new \Nette\Application\UI\Multiplier(function ($idMessage) {
            $commentForm = $this->commentForm->create();
			$commentForm->setComments($this->comments);
			$commentForm->setMessage($this->getMessage());
            return $commentForm;
        });
    }
    
    protected function getMessage()
    {		
        if($this->message === null || $this->isControlInvalid()) {
            $this->message = $this->messageManager->getMessage($this->id, $this->presenter->activeUser, $this->presenter->activeGroup);
        }
        return $this->message;
    }
    
    public function setMessage($id, $message = null, $comments = [], $isDetail = false)
    {
        $this->id = $id;
        $this->isDetail = $isDetail;
		$this->comments = $comments;
        if($message) {
            $this->message = $message;
        }
    }
}
