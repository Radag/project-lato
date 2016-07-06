<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Components\Stream\Stream;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\Manager\UserManager;
use App\Model\Manager\MessageManager;
use App\Components\Stream\MessageForm\MessageForm;
use App\Components\Stream\CommentForm\CommentForm;



/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class Stream extends Control
{
    
    /**
     *
     * @var UserManager $userManager
     */
    private $userManager;
    private $messageManager;
    private $fileManager;
    
    private $commentForm = null;
    private $activeGroup;
    
    public function __construct(UserManager $userManager, MessageManager $messageManager, $activeGroup, $fileManager)
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->activeGroup = $activeGroup;
        $this->fileManager = $fileManager;
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
        $template->activeUser = $this->userManager->get($this->getPresenter()->user->id);
//        foreach($messages as $message) {
//            $message->commentForm = $this['commentForm'];
//            $message->commentForm->setMessage($message->id);
//        }
        
        $template->messages = $messages;
        $template->setFile(__DIR__ . '/Stream.latte');
        $template->render();
    }
    
 
    
    public function createComponentMessageForm()
    {
        $form = new MessageForm($this->userManager, $this->messageManager, $this, $this->fileManager);
        return $form;
    }
    
    public function createComponentCommentForm()
    {
        return new \Nette\Application\UI\Multiplier(function ($idMessage) {
            $commentForm = new CommentForm($this->messageManager, $this->userManager);
            $commentForm->setMessage($idMessage);
            return $commentForm;
        });
    }
}
