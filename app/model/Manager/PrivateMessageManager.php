<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use App\Model\Entities\PrivateMessage;
use App\Model\Entities\User;

/**
 * Description of MessageManager
 *
 * @author Radaq
 */
class PrivateMessageManager extends BaseManager
{    
    public function getMessages($user)
    {
        $return = array();
        $messages = $this->db->fetchAll("SELECT 
          		A2.text, A2.id AS mess_id, A3.name, A3.surname, A2.created_when, A3.slug,
               A3.profile_image, A3.sex, A2.is_read
            FROM 
            (
            SELECT T1.user_from, MAX(T1.id) AS message_id FROM (
                    SELECT user_to_id AS user_from, id FROM private_message WHERE user_from_id=?
                            UNION 
                    SELECT user_from_id AS user_from, id FROM private_message WHERE user_to_id=?
            ) T1
            GROUP BY T1.user_from) A1
            JOIN private_message A2 ON A1.message_id = A2.id
            JOIN user A3 ON A1.user_from = A3.id
            ORDER BY A2.created_when DESC", $user->id, $user->id);
        foreach($messages as $message) {
            $mess = new PrivateMessage();
            $mess->text = $message->text;
            $mess->id = $message->mess_id;
            $mess->created = $message->created_when;
            $mess->read = $message->is_read;
            $mess->user = new User($message);  ;
            $return[] = $mess;
        }
        
        return $return;
    }
    
    public function getConversation($homeUser, $withUser)
    {
        $return = array();
        $messages = $this->database->query("SELECT T1.TEXT, T1.ID_PRIVATE_MESSAGE, T1.CREATED, T2.NAME, T2.SURNAME,  T2.URL_ID,
                T2.PROFILE_FILENAME,T2.PROFILE_PATH,T2.SEX,
                T3.NAME AS NAME_F, T3.SURNAME AS SURNAME_F, T3.URL_ID AS URL_ID_F,
                T3.PROFILE_FILENAME AS PROFILE_FILENAME_F,T3.PROFILE_PATH AS PROFILE_PATH_F ,T3.SEX AS SEX_F
                FROM private_message T1
                LEFT JOIN vw_user_detail T2 ON T1.ID_USER_FROM=T2.ID_USER 
                LEFT JOIN vw_user_detail T3 ON T1.ID_USER_TO=T3.ID_USER 
                WHERE (T1.ID_USER_TO=? AND T1.ID_USER_FROM=?) OR (T1.ID_USER_FROM=? AND T1.ID_USER_TO=?)
                ORDER BY CREATED ASC", $homeUser->id, $withUser->id, $homeUser->id, $withUser->id)->fetchAll();
        foreach($messages as $message) {
            $mess = new PrivateMessage();
            $user = new User();
            $mess->text = $message->TEXT;
            $mess->id = $message->ID_PRIVATE_MESSAGE;
            $mess->created = $message->CREATED;
            if($homeUser->urlId === $message->URL_ID) {
                $user->surname = $message->SURNAME;
                $user->name = $message->NAME;
                $user->urlId = $message->URL_ID;
                $user->profileImage = User::createProfilePath($message->PROFILE_PATH , $message->PROFILE_FILENAME, $message->SEX);
                $mess->fromMe = true;
            } else {
                $user->surname = $message->SURNAME;
                $user->name = $message->NAME;
                $user->urlId = $message->URL_ID;
                $user->profileImage = User::createProfilePath($message->PROFILE_PATH, $message->PROFILE_FILENAME, $message->SEX);
                $mess->fromMe = false;
            }
            $mess->user = $user;
            $return[] = $mess;
        }
        
        return $return;
    }
    
    
    public function getUnreadNumber($user)
    {
        return $this->db->fetchSingle("SELECT COUNT(id) FROM private_message WHERE user_to_id=? AND is_read IS NULL", $user->id);
    }
    
    public function setMessagesRead($idUserFrom, $idUserTo)
    {
        $this->db->query("UPDATE private_message SET is_read=NOW() WHERE user_from_id=? AND user_to_id=? AND is_read IS NULL", $idUserFrom, $idUserTo);
    }
    
    public function insertMessage(PrivateMessage $message)
    {
        $this->db->query("INSERT INTO private_message", [
            'text' => $message->text,
            'user_from_id' => $message->idUserFrom,
            'user_to_id' => $message->idUserTo,
            'created_by' => $this->user->id
        ]);
    }
      
    
}
