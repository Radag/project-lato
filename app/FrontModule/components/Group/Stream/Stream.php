<?php

namespace App\FrontModule\Components\Stream;

use App\FrontModule\Components\Stream\IMessagesColumn;
use App\FrontModule\Components\Stream\IRightColumn;
use App\FrontModule\Components\Stream\MessageForm\IMessageForm;
use App\FrontModule\Components\Stream\ICommitTaskForm;

class Stream extends \App\Components\BaseComponent
{
    /** @var  IMessagesColumn @inject */
    protected $messageColumn;

    /** @var  IRightColumn @inject */
    protected $rightColumn;

    /** @var  IMessageForm @inject */
    protected $messageForm;
    
    /** @var  ICommitTaskForm @inject */
    protected $commitTaskForm;
    
    public $singleMode = false;
    
    public function __construct(
        IMessagesColumn $messageColumn, 
        IRightColumn $rightColumn,
        IMessageForm $messageForm,
        ICommitTaskForm $commitTaskForm
    )
    {
        $this->messageColumn = $messageColumn;
        $this->rightColumn = $rightColumn;
        $this->messageForm = $messageForm;
        $this->commitTaskForm = $commitTaskForm;
    }
        
    public function render()
    {
        $this->template->singleMode = $this->singleMode;
        $this->template->activeUser = $this->presenter->activeUser;
        $this->template->groupPermission = $this->presenter->groupPermission;
        $this->template->isOnwer = ($this->presenter->activeGroup->relation === 'owner');
        parent::render();
    }
    
    public function createComponentMessagesColumn()
    {
        return $this->messageColumn->create();
    }
    
    public function createComponentRightColumn()
    {
        return $this->rightColumn->create();
    }
    
    public function createComponentMessageForm()
    {
        $component = $this->messageForm->create();  
        return $component;
    }
    
    public function createComponentCommitTaskForm()
    {
        return $this->commitTaskForm->create();
    }
    
    public function setSingleMode($idMessage)
    {
        $this->singleMode = $idMessage;
        $this['messagesColumn']->setSingleMode($idMessage);
    }
    
    public function handleResetForm($type)
    {
        $this['messageForm']->handleResetForm($type);
        $this->redrawControl('messageForm');
    }
}
