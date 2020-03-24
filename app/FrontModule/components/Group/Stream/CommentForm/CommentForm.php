<?php

namespace App\FrontModule\Components\Stream;

use \Nette\Application\UI\Form;
use App\Model\Manager\MessageManager;
use App\Model\Manager\UserManager;

class CommentForm extends \App\Components\BaseComponent
{
    /** @var MessageManager */
    protected $messageManager;
    
    /** @var UserManager */
    protected $userManager;
 
    protected $message = null;
    
    protected $comments = [];
      
    public function __construct(MessageManager $messageManager, UserManager $userManager)
    {
        $this->messageManager = $messageManager;
        $this->userManager = $userManager; 
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm();
        $form->getElementPrototype()->class('ajax');
        $form->addTextArea('text', 'Zpráva')
             ->setAttribute('placeholder', 'Napište komentář ...')
             ->setMaxLength(300)
             ->setRequired('Napište zprávu')
             ->addRule(\Nette\Forms\Form::FILLED, 'Zpráva musí obsahovat text');
        $form->addSubmit('send', 'Publikovat');
        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }
	
	protected function createComponentReplyForm()
    {
        $form = $this->createComponentForm();		
        $form->addHidden('idReply');
        return $form;
    }
    
    public function processForm(Form $form, $values) 
    {		
        $comment = new \App\Model\Entities\Comment();
        $comment->text = trim($values->text);
        $comment->user = $this->presenter->activeUser;
        $comment->idMessage = $this->message->id;
		if(isset($values->idReply)) {
			$comment->replyCommentId = $values->idReply;
		}
        
        $this->messageManager->createComment($comment);
        
        $this['form']['text']->setValue('');
        $this->comments = $this->messageManager->getMessageComments($this->message->id);
        $this->redrawControl('comments');
    }
    
    public function render()
    {
        $this->template->comments = $this->comments;
        $this->template->discutionMembers = $this->getDicscutionMembers();
        $this->template->activeUser = $this->presenter->activeUser;
        $this->template->id = $this->message->id;
        $this->template->showForm = $this->message->deleted == 0;
        parent::render();
    }
    
    public function setMessage($mesage)
    {
        $this->message = $mesage;
    }
    
    public function setComments($comments)
    {
        $this->comments = $comments;
    }
    
    public function getDicscutionMembers() 
    {
        $membr = [];   ; 
        foreach($this->comments as $c) {
            $id = (int)$c->user->id;
            if(!array_key_exists($id, $membr)) {
                $membr[$id] = $c->user;
            }
        }
        return $membr;
    }

}
