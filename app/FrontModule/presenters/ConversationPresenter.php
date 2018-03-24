<?php

namespace App\FrontModule\Presenters;

use App\Model\Manager\ConversationManager;

class ConversationPresenter extends BasePresenter
{
    
    
    /** @var ConversationManager @inject */
    public $conversationManager;

    /** @persistent */
    public $messagesFilter = null;
        
    
    public function renderList()
    {
        $this['topPanel']->setTitle('Konverzace');
        $months = ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'];
        $return = [];
        $messages = $this->conversationManager->getConversations($this->activeUser);
        
        foreach($messages as $message) {
            $diff = (new \DateTime())->diff($message->created);
            $diffDays = (integer)$diff->format( "%R%a" );
            $message->diffDays = $diffDays;
            if($diffDays === 0) {
                if(!isset($return['today'])) {
                    $return['today'] = (object)array('messages' => array(), 'name' => 'Dnes'); 
                }
                $return['today']->messages[] = $message;
            } elseif($diffDays === -1) {
                if(!isset($return['yesterday'])) {
                    $return['yesterday'] = (object)array('messages' => array(), 'name' => 'Včera'); 
                }
                $return['yesterday']->messages[] = $message;
            } else {
                if(!isset($return[$message->created->format("n")])) {
                    $return[$message->created->format("n")] = (object)array('messages' => array(), 'name' => $months[$message->created->format("n") - 1]); 
                }
                $return[$message->created->format("n")]->messages[] = $message;
            }
        }
        $this->template->filter = $this->messagesFilter;
        $this->template->messages = $return;
    }
        
    
    public function renderDefault($id = null, $users)
    {
        $ids = [];
        $messages = [];
        if(empty($id) && !empty($users)) {
            $this->template->attenders = $this->userManager->getMultiple(explode(',', $users), true);
            foreach($this->template->attenders as $att) {
                $ids[] = $att->id;
            }
        } elseif(!empty($id)) {
            $conversation = $this->conversationManager->getConversation($id, $this->activeUser);
            if(empty($conversation->users)) {
                $this->redirect('Conversation:list');
            }
            $messages = $conversation->messages;
            $this->template->attenders = $conversation->users;
        } else {
            $this->redirect('Conversation:list');
        }
        
        $attName = [];
        foreach($this->template->attenders as $att) {
            $attName[] = $att->name . ' ' . $att->surname;
        }
        $this['topPanel']->setTitle('Konverzace s ' . implode(', ', $attName));
        $this['form']->setValues([
            'users' => implode(',', $ids),
            'conversation_id' => $id
        ]);
        $this->template->messages = $messages;
    }
    
    public function handleFilterMessages($filter = null)
    {
        $this->messagesFilter = $filter;
        $this->redrawControl("messagesList");
    }
    
    protected function createComponentForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->addTextArea('text', 'Obsah')
             ->setAttribute('placeholder', 'Napište zprávu ..')
             ->setRequired('Prosím napiště text zprávy.');
        $form->addHidden('users');
        $form->addHidden('conversation_id');
        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = function($form, $values) {
            if(empty($values->conversation_id)) {
                $attenders = explode(',', $values->users);
                $attenders[] = $this->activeUser->id;
                $values->conversation_id = $this->conversationManager->createConversation($attenders, $this->activeUser);
            }
            $this->conversationManager->insertMessage($values->conversation_id, $values->text, $this->activeUser);
            $this->redirect('this', ['id' => $values->conversation_id, 'users' => null]);
            
        };
        $form->onValidate[] = function($form, $values) {
            
            
        };

        return $form;
    }
}
