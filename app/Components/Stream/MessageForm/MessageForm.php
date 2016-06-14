<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Components\Stream\MessageForm;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\MessageManager;



/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class MessageForm extends Control
{
    
    /**
     *
     * @var MessageManager $messageManager
     */
    private $messageManager;
    
    private $stream;
    
    public function __construct(MessageManager $messageManager, $stream)
    {
        $this->messageManager = $messageManager;
        $this->stream = $stream;
    }
    
    protected function createComponentForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->getElementPrototype()->class('ajax');
        $form->addTextArea('text', 'Zpráva')
                ->setAttribute('placeholder', 'Sem napište Vaši zprávu ...')
            ->setRequired('Napište zprávu');

        $form->addSubmit('send', 'Publikovat');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/MessageForm.latte');
        // vložíme do šablony nějaké parametry
        //$template->form = $this->form;
        // a vykreslíme ji
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $message = new \App\Model\Entities\Message;
        $message->setText($values['text']);
        $message->setUser($this->getPresenter()->getUser());
        $message->idGroup = $this->stream->getActiveGroup()->id;
        
        $this->messageManager->createMessage($message);
        $this->stream->redrawControl('messages');
    }
}
