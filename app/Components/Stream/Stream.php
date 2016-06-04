<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Components\Stream\Stream;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\UserManager;
use App\Model\MessageManager;
use App\Components\Stream\MessageForm\MessageForm;



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
    
    public function __construct(UserManager $userManager, MessageManager $messageManager)
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
    }
    
    protected function create()
    {
        
    }
    
    public function render()
    {
        $template = $this->template;
        $template->messages = $this->messageManager->getMessages();
        $template->setFile(__DIR__ . '/Stream.latte');
        $template->render();
    }
    
 
    
    public function createComponentMessageForm()
    {
        $form = new MessageForm($this->messageManager, $this);
        return $form;
    }
}
