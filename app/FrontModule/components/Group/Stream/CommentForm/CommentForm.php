<?php

namespace App\FrontModule\Components\Stream;

use \Nette\Application\UI\Form;
use App\Model\Manager\UserManager;
use App\Model\Manager\CommentsManager;

class CommentForm extends \App\Components\BaseComponent
{
	/** @var CommentsManager */
    protected $commentsManager;
    
    /** @var UserManager */
    protected $userManager;
 
    protected $message = null;
    
    protected $comments = [];
      
    public function __construct(
		UserManager $userManager,
		CommentsManager $commentsManager
	)
    {
        $this->userManager = $userManager; 
        $this->commentsManager = $commentsManager; 
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm();
        $form->getElementPrototype();
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
        
        $this->commentsManager->createComment($comment);
        
        $this['form']['text']->setValue('');
        $this['replyForm']['text']->setValue('');
        $this->comments = $this->commentsManager->getMessageComments($this->message->id);
        $this->redrawControl('comments');
    }
    
    public function render()
    {
		if($this->comments) {
			$this->template->comments = $this->comments->comments;
			$this->template->commentsCount = $this->comments->count;
		} else {
			$this->template->comments = [];
			$this->template->commentsCount = 0;
		}        
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
}
