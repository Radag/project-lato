<?php
namespace App\FrontModule\Components\Conversation;

use App\Model\Manager\ConversationManager;
use App\Model\Manager\UserManager;

class Chat extends \App\Components\BaseComponent
{
    /** @var ConversationManager @inject */
    public $conversationManager;
    
    /** @var UserManager @inject */
    public $userManager;
    
    public $conversationId = null;
    
    public $attenders = [];    
    public $messages = [];
    
    public function __construct(
        ConversationManager $conversationManager,
        UserManager $userManager
    )
    {
        $this->conversationManager = $conversationManager;
        $this->userManager = $userManager;
    }
    
    public function render() 
    {          
        foreach($this->attenders as $att) {
            $ids[] = $att->id;
        }
        $this['form']->setValues([
            'users' => implode(',', $ids),
            'conversation_id' => $this->conversationId
        ]);        
        $this->template->attenders = $this->attenders;
        $this->template->messages = $this->messages;
        parent::render();
    }
    
    public function setConversation($id, $users)
    {
        if(empty($id) && !empty($users)) {
            $this->attenders = $this->userManager->getMultiple(explode(',', $users), true);
        } elseif(!empty($id)) {
            $conversation = $this->conversationManager->getConversation($id, $this->presenter->activeUser);
            if(!$conversation || empty($conversation->users)) {
                $this->presenter->redirect('Conversation:list');
            }
            $this->conversationManager->setConversationRead($this->presenter->activeUser, $conversation);
            $this->conversationId = $id;
            $this->messages = $conversation->messages;
            $this->attenders = $conversation->users;
        } else {
            $this->presenter->redirect('Conversation:list');
        }
        
        $attName = [];
        foreach($this->attenders as $user) {
            if($user->id != $this->presenter->activeUser->id) {
                $attName[] = $user->name . ' ' . $user->surname;
            }
        }
        $this->presenter['topPanel']->setTitle(implode(', ', $attName));
    }
    
    public function handleReloadMessages()
    {
        $this->redrawControl("conversation-list-messages");
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm();
        $form->addTextArea('text', 'Obsah')
             ->setAttribute('placeholder', 'Napište zprávu ..')
             ->setRequired('Prosím napiště text zprávy.');
        $form->addHidden('users');
        $form->addHidden('conversation_id');
        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = function($form, $values) {
            if(empty($values->conversation_id)) {
                $attenders = explode(',', $values->users);
                $attenders[] = $this->presenter->activeUser->id;
                $values->conversation_id = $this->conversationManager->createConversation($attenders, $this->presenter->activeUser);
            }
            $this->conversationManager->insertMessage($values->conversation_id, $values->text, $this->presenter->activeUser);
            $this->presenter->redirect('this', ['id' => $values->conversation_id, 'users' => null]);
            
        };
        $form->onValidate[] = function($form, $values) {
            
            
        };

        return $form;
    }
}
