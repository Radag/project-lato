<?php

namespace App\FrontModule\Presenters;

use App\FrontModule\Components\Conversation\IChat;
use App\FrontModule\Components\Conversation\IChatList;

class ConversationPresenter extends BasePresenter
{    
    /** @var IChat @inject */
    public $chat;
    
    /** @var IChatList @inject */
    public $chatList;
                
    public function actionList()
    {
        $this['topPanel']->setTitle('Konverzace');
    }
    
    public function actionDefault($id, $users)
    {
        $this['chat']->setConversation($id, $users);
    }
    
    protected function createComponentChat()
    {
        return $this->chat->create();
    }
    
    protected function createComponentChatList()
    {
        return $this->chatList->create();
    }
}
