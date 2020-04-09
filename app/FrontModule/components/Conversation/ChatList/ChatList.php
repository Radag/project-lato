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
				$index = $message->created->format("Ymd");
                if(!isset($return[$index])) {
                    $return[$index] = (object)['messages' => [], 'name' => 'Dnes']; 
                }
                $return[$index]->messages[$message->created->format("Ymdhm")] = $message;
            } elseif($diffDays === -1) {
				$index = $message->created->format("Ymd");
                if(!isset($return[$index])) {
                    $return[$index] = (object)['messages' => [], 'name' => 'Včera']; 
                }
                $return[$index]->messages[$message->created->format("Ymdhm")] = $message;
            } else {
				$index = $message->created->format("Ym01");
                if(!isset($return[$index])) {
                    $return[$index] = (object)['messages' => [], 'name' => $this->months[$message->created->format("n") - 1]]; 
                }
                $return[$index]->messages[$message->created->format("Ymdhm")] = $message;
            }
        }		
		krsort($return);
		foreach($return as $group) {
			krsort($group->messages);
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
