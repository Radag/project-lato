<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream;

use App\FrontModule\Components\Stream\IMessagesColumnFactory;
use App\FrontModule\Components\Stream\IRightColumnFactory;
use App\FrontModule\Components\Stream\MessageForm\IMessageFormFactory;
use App\FrontModule\Components\Stream\ICommitTaskFormFactory;

/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class Stream extends \App\Components\BaseComponent
{
    
    /** @var  IMessagesColumnFactory @inject */
    protected $messageColumn;

    /** @var  IRightColumnFactory @inject */
    protected $rightColumn;

    /** @var  IMessageFormFactory @inject */
    protected $messageForm;
    
    /** @var  ICommitTaskFormFactory @inject */
    protected $commitTaskForm;
    
    public $singleMode = false;
    
    public function __construct(
        IMessagesColumnFactory $messageColumn, 
        IRightColumnFactory $rightColumn,
        IMessageFormFactory $messageForm,
        ICommitTaskFormFactory $commitTaskForm
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
        return $this->messageForm->create();
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
}
