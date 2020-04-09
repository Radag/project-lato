<?php

namespace App\Model\Manager;

use App\Model\Entities\PrivateMessage;
use App\Model\Entities\User;
use App\Model\Entities\Conversation;

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
        $this->db->query("UPDATE conversation_message SET top=0 WHERE conversation_id=?", $idConversation);
        $this->db->query("INSERT INTO conversation_message", [
            'created_by' => $createdBy->id,
            'message' => $message,
            'conversation_id' => $idConversation,
            'top' => 1
        ]);
        $messageId = $this->db->getInsertId();
        $attendanst = $this->db->fetchAll("SELECT * FROM converastion_attendant WHERE conversation_id=?", $idConversation);
        foreach($attendanst as $att) {
            if($createdBy->id != $att->user_id) {
                $this->db->query("INSERT INTO converastion_attendant_read", [
                    'message_id' => $messageId,
                    'user_id' => $att->user_id
                ]);
                $this->db->query("UPDATE user_real SET has_new_private_message=has_new_private_message+1 WHERE id=?", $att->user_id);
            }
        }
    }
    
    public function getConversation($idConversation, User $user)
    {
        $return = (object)['users' => [], 'messages' => []];
        $attenders = $this->db->fetchAll("SELECT T2.* FROM converastion_attendant T1 JOIN user T2 ON T1.user_id=T2.id WHERE T1.conversation_id=?", $idConversation);
        foreach($attenders as $att) {
            $return->users[$att->id] = new User($att);
        }
        if(!in_array($user->id, array_keys($return->users))) {
            return null;
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
    
    public function getConversations(User $user, $filter = null)
    {
        $return = [];
        
        if($filter === 'unread') {
            $sql = "SELECT T3.*, T7.profile_image, T1.id AS conv_id, IFNULL(T4.created_when, T1.created_when) AS conv_created_when, T4.message, IF(T4.created_by=T2.user_id, 1, 0) AS last_is_me, IF(T4.created_by=T2.user_id, NOW(), T6.`read`) AS `read`
                FROM conversation T1 
                JOIN converastion_attendant T2 ON T1.id=T2.conversation_id 
                JOIN conversation_message T4 ON (T4.conversation_id=T1.id AND T4.top=1)
                JOIN (SELECT MIN(user_id) AS user_id, conversation_id FROM converastion_attendant WHERE user_id!=? GROUP BY conversation_id) T5 ON T5.conversation_id=T1.id
                JOIN user T3 ON T3.id=T5.user_id
                LEFT JOIN converastion_attendant_read T6 ON T6.message_id=T4.id AND T6.user_id=T2.user_Id
				JOIN user_real T7 ON T7.id = T3.id
                WHERE T2.user_id=? AND IF(T4.created_by=T2.user_id, NOW(), T6.`read`) IS NULL
				ORDER BY T4.created_when DESC";
        } else {
            $sql = "SELECT T3.*, T7.profile_image, T1.id AS conv_id, IFNULL(T4.created_when, T1.created_when) AS conv_created_when, T4.message, IF(T4.created_by=T2.user_id, 1, 0) AS last_is_me, IF(T4.created_by=T2.user_id, NOW(), T6.`read`) AS `read`
                FROM conversation T1 
                JOIN converastion_attendant T2 ON T1.id=T2.conversation_id 
                JOIN conversation_message T4 ON (T4.conversation_id=T1.id AND T4.top=1)
                JOIN (SELECT MIN(user_id) AS user_id, conversation_id FROM converastion_attendant WHERE user_id!=? GROUP BY conversation_id) T5 ON T5.conversation_id=T1.id
                JOIN user T3 ON T3.id=T5.user_id
                LEFT JOIN converastion_attendant_read T6 ON T6.message_id=T4.id AND T6.user_id=T2.user_Id
				JOIN user_real T7 ON T7.id = T3.id
                WHERE T2.user_id=?
				ORDER BY T4.created_when DESC";
        }
        
        $conversations = $this->db->fetchAll($sql, $user->id, $user->id);
 
        foreach($conversations as $conv) {
            $mes = new Conversation($conv);
            $mes->user = new User($conv);
            $return[] = $mes;
        }
        return $return;
    }
      
    public function conversationExist($attendants)
    {
        $attendantsString = implode(',', $attendants);
        $exist = $this->db->fetch("SELECT T1.id, GROUP_CONCAT(T2.user_id ORDER BY T2.user_id ASC SEPARATOR ',') as attendants
                FROM conversation T1 join converastion_attendant T2 on (T1.id=T2.conversation_id) 
                GROUP BY T1.id HAVING attendants=?", $attendantsString);
        return $exist;
    }
    
    
    public function setAllMessagesRead(User $user, $global = true)
    {
        if($global) {
            $this->db->query("UPDATE user_real SET has_new_private_message=0 WHERE id=?", $user->id);
        } else {
            $this->db->query("UPDATE converastion_attendant_read SET `read`=NOW() WHERE user_id=? AND `read` IS NULL", $user->id);
        }        
    }
    
    public function setConversationRead(User $user, $conversation)
    {
        foreach($conversation->messages as $mess) {
            $this->db->query("UPDATE converastion_attendant_read SET `read`=NOW() WHERE user_id=? AND message_id=? AND `read` IS NULL", $user->id, $mess->id);  
        }  
    }
}
