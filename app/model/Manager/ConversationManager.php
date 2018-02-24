<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use App\Model\Entities\PrivateMessage;
use App\Model\Entities\User;
use App\Model\Entities\Conversation;

/**
 * Description of MessageManager
 *
 * @author Radaq
 */
class ConversationManager extends BaseManager
{    
       
    public function createConversation($attenders, User $createdBy)
    {
        $this->db->query("INSERT INTO conversation", [
            'created_by' => $createdBy->id
        ]);
        $idConversation = $this->db->getInsertId();
        foreach($attenders as $att) {
            $this->db->query("INSERT INTO converastion_attendant", [
                'conversation_id' => $idConversation,
                'user_id' => $att
            ]);
        }
        return $idConversation;
    }
    
    public function insertMessage($idConversation, $message, User $createdBy)
    {
        $this->db->query("INSERT INTO conversation_message", [
            'created_by' => $createdBy->id,
            'message' => $message,
            'conversation_id' => $idConversation
        ]);   
    }
    
    public function getConversation($idConversation, User $user)
    {
        $return = (object)['users' => [], 'messages' => []];
        $attenders = $this->db->fetchAll("SELECT T2.* FROM converastion_attendant T1 JOIN user T2 ON T1.user_id=T2.id WHERE T1.conversation_id=?", $idConversation);
        foreach($attenders as $att) {
            $return->users[$att->id] = new User($att);
        }
        $messages = $this->db->fetchAll("SELECT * FROM conversation_message WHERE conversation_id=?", $idConversation);
        foreach($messages as $message) {
            $mes = new PrivateMessage($message);
            $mes->user = $return->users[$message->created_by];
            if($message->created_by == $user->id) {
                $mes->fromMe = true;
            }
            $return->messages[] = $mes;
        }
        return $return;
    }
    
    public function getConversations(User $user)
    {
        $return = [];
        $sql = "SELECT T3.*, T1.id AS conv_id, IFNULL(T4.created_when, T1.created_when) AS conv_created_when, T4.message
                FROM conversation T1 
                JOIN converastion_attendant T2 ON T1.id=T2.conversation_id 
                JOIN user T3 ON T2.user_id=T3.id 
                LEFT JOIN conversation_message T4 ON (T4.conversation_id=T1.id AND T4.top=1)
                WHERE T2.user_id=?";
        $conversations = $this->db->fetchAll($sql, $user->id);
        foreach($conversations as $conv) {
            $mes = new Conversation($conv);
            $mes->user = new User($conv);
            $return[] = $mes;
        }
        return $return;
    }
      
    
}
