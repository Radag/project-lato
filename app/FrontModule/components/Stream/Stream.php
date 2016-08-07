<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream;

use \Nette\Application\UI\Control;
use App\Model\Manager\UserManager;
use App\Model\Manager\MessageManager;
use App\FrontModule\Components\Stream\MessageForm\MessageForm;
use App\FrontModule\Components\Stream\CommentForm\CommentForm;



/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class Stream extends Control
{
    
    /**
     * @var UserManager $userManager
     */
    protected $userManager;
    
    /**
     * @var MessageManager $messageManager
     */
    protected $messageManager;
    
    /**
     * @var \App\Model\Manager\FileManager $messageManager
     */
    protected $fileManager;
    
    /**
     * @var CommentForm; 
     */
    protected $commentForm = null;
    
    /**
     * @var \App\Model\Entities\Group $activeGroup
     */
    protected $activeGroup;
    
    /**
     * @var \App\Model\Entities\User $activeUser
     */
    protected $activeUser;
    
    public function __construct(UserManager $userManager, MessageManager $messageManager, $activeGroup, $fileManager, \App\Model\Entities\User $activeUser)
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->activeGroup = $activeGroup;
        $this->fileManager = $fileManager;
        $this->activeUser = $activeUser;
    }
    
    public function getActiveGroup()
    {
        return $this->activeGroup;
    }
 
    
    protected function create()
    {
        
    }
    
    public function render()
    {
        $template = $this->template;
        $messages = $this->messageManager->getMessages($this->activeGroup);
        $template->activeUser = $this->activeUser;        
        $template->messages = $messages;
        $template->setFile(__DIR__ . '/Stream.latte');
        $template->render();
    }
    
 
    
    public function createComponentMessageForm()
    {
        $form = new MessageForm($this->userManager, $this->messageManager, $this, $this->fileManager, $this->activeUser);
        return $form;
    }
    
    public function createComponentCommentForm()
    {
        return new \Nette\Application\UI\Multiplier(function ($idMessage) {
            $commentForm = new CommentForm($this->messageManager, $this->userManager, $this->activeUser);
            $commentForm->setMessage($idMessage);
            return $commentForm;
        });
    }
}
