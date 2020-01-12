<?php
namespace App\FrontModule\Components\Conversation;

use App\Model\Manager\ConversationManager;

class ChatList extends \App\Components\BaseComponent
{
    /** @var ConversationManager @inject */
    public $conversationManager;
    
    /** @persistent */
    public $messagesFilter = null;
    
    public $months = ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'];
    
    public function __construct(ConversationManager $conversationManager)
    {
        $this->conversationManager = $conversationManager;
    }
    
    public function render()
    {
        $return = [];
        $messages = $this->conversationManager->getConversations($this->presenter->activeUser, $this->messagesFilter);
        
        foreach($messages as $message) {
            $diff = (new \DateTime())->diff($message->created);
            $diffDays = (integer)$diff->format( "%R%a" );
            $message->diffDays = $diffDays;
            if($diffDays === 0) {
                if(!isset($return['today'])) {
                    $return['today'] = (object)['messages' => [], 'name' => 'Dnes']; 
                }
                $return['today']->messages[] = $message;
            } elseif($diffDays === -1) {
                if(!isset($return['yesterday'])) {
                    $return['yesterday'] = (object)['messages' => [], 'name' => 'Včera']; 
                }
                $return['yesterday']->messages[] = $message;
            } else {
                if(!isset($return[$message->created->format("n")])) {
                    $return[$message->created->format("n")] = (object)['messages' => [], 'name' => $this->months[$message->created->format("n") - 1]]; 
                }
                $return[$message->created->format("n")]->messages[] = $message;
            }
        }
        $this->template->filter = $this->messagesFilter;
        $this->template->messages = $return;
        parent::render();
    }
    
     public function handleFilterMessages($filter = null)
    {
        $this->messagesFilter = $filter;
        $this->redrawControl("messagesList");
    }
    
    public function handleNotificationsRead()
    {
        $this->conversationManager->setAllMessagesRead($this->presenter->activeUser, true);
        $this->conversationManager->setAllMessagesRead($this->presenter->activeUser, false);
        $this->presenter->activeUser->unreadPrivateMessages = 0;
        $this->presenter['topPanel']->redrawControl();
        $this->redrawControl("messagesList");
    }
    
    
}
