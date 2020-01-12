<?php

namespace App\FrontModule\Components\Stream;

use App\FrontModule\Components\Stream\IMessagesColumn;
use App\FrontModule\Components\Stream\IRightColumn;
use App\FrontModule\Components\Stream\MessageForm\IMessageForm;

class Stream extends \App\Components\BaseComponent
{
    /** @var  IMessagesColumn @inject */
    public $messageColumn;

    /** @var  IRightColumn @inject */
    public $rightColumn;

    /** @var  IMessageForm @inject */
    public $messageForm;
        
    public function __construct(
        IMessagesColumn $messageColumn, 
        IRightColumn $rightColumn,
        IMessageForm $messageForm
    )
    {
        $this->messageColumn = $messageColumn;
        $this->rightColumn = $rightColumn;
        $this->messageForm = $messageForm;
    }
        
    public function render()
    {
        $this->template->activeUser = $this->presenter->activeUser;
        $this->template->activeGroup = $this->presenter->activeGroup;
        $this->template->isOwner = ($this->presenter->activeGroup->relation === 'owner');
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
        
    public function handleResetForm($type)
    {
        $this['messageForm']->handleResetForm($type);
        $this->redrawControl('messageForm');
    }
}
