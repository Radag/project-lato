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
    
    /** @var \App\Model\Entities\Message */
    protected $message = null;
    
    protected $id = null;
    
    protected $comments = [];
      
    public function __construct(
        MessageManager $messageManager,
        ICommentForm $commentForm
    )
    {
        $this->messageManager = $messageManager;
        $this->commentForm = $commentForm;
    }
    
    public function render()
    {
        $this->template->message = $this->getMessage();
        $this->template->activeGroup = $this->presenter->activeGroup;
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
            if(isset($this->messages[$idMessage])) {
                $commentForm->setMessage($this->messages[$idMessage]);
            } else {
                $commentForm->setMessage($this->messageManager->getMessage($idMessage, $this->presenter->activeUser, $this->presenter->activeGroup));
            }
            if(isset($this->comments[$idMessage])) {
                $commentForm->setComments($this->comments[$idMessage]);
            }
            return $commentForm;
        });
    }
    
    protected function getMessage()
    {
        if($this->message === null) {
            $this->message = $this->messageManager->getMessage($this->id, $this->presenter->activeUser, $this->presenter->activeGroup);
        }
        return $this->message;
    }
    
    public function setMessage($id, $message = null)
    {
        $this->id = $id;
        if($message) {
            $this->message = $message;
        }
    }
}
