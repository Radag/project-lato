<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Components\Stream\CommentForm;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\MessageManager;



/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class CommentForm extends Control
{
    
    private $idMessage = 0;
    

    /**
     *
     * @var CommentForm $messageManager
     */
    private $messageManager;
    
    
    public function __construct(MessageManager $messageManager)
    {
        $this->messageManager = $messageManager;
    }
    

    public function setMessage($id)
    {
        $this->idMessage = $id;
    }
    
    
    protected function createComponentForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->getElementPrototype()->class('ajax');
        $form->addTextArea('text', 'Zpráva')
                ->setAttribute('placeholder', 'Napište komentář ...')
            ->setRequired('Napište zprávu');
        $form->addHidden('idMessage', $this->idMessage);
        $form->addSubmit('send', 'Publikovat');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/CommentForm.latte');
        // vložíme do šablony nějaké parametry
        //$template->form = $this->form;
        // a vykreslíme ji
        $template->comments = $this->messageManager->getComments($this->idMessage);
        
        $template->id = $this->idMessage;
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $comment = new \App\Model\Entities\Comment();
        $comment->text = $values['text'];
        $comment->user = ($this->getPresenter()->getUser());
        $comment->idMessage = $values['idMessage'];
        
        $this->messageManager->createComment($comment);
        $this->redrawControl('comments');
    }
}
