<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use Nette;
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
        $messages = $this->database->query("SELECT 
                    A2.TEXT, A2.ID_PRIVATE_MESSAGE, A3.NAME, A3.SURNAME, A2.CREATED, A3.URL_ID,
               A3.PROFILE_IMAGE, A3.SEX, A2.IS_READ
            FROM 
            (
            SELECT T1.USER_FROM, MAX(T1.ID_PRIVATE_MESSAGE) AS ID_MESSAGE FROM (
                    SELECT ID_USER_TO AS USER_FROM, ID_PRIVATE_MESSAGE FROM private_message WHERE ID_USER_FROM=?
                            UNION 
                    SELECT ID_USER_FROM AS USER_FROM, ID_PRIVATE_MESSAGE FROM private_message WHERE ID_USER_TO=?
            ) T1
            GROUP BY T1.USER_FROM) A1
            JOIN private_message A2 ON A1.ID_MESSAGE = A2.ID_PRIVATE_MESSAGE
            JOIN user A3 ON A1.USER_FROM = A3.ID_USER
            ORDER BY A2.CREATED DESC", $user->id, $user->id)->fetchAll();
        foreach($messages as $message) {
            $mess = new PrivateMessage();
            $user = new User();
            $user->surname = $message->SURNAME;
            $user->name = $message->NAME;
            $user->urlId = $message->URL_ID;
            $user->profileImage = User::createProfilePath($message->PROFILE_IMAGE, $message->SEX);            
            $mess->text = $message->TEXT;
            $mess->id = $message->ID_PRIVATE_MESSAGE;
            $mess->created = $message->CREATED;
            $mess->read = $message->IS_READ;
            $mess->user = $user;
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
        return $this->database->query("SELECT COUNT(ID_PRIVATE_MESSAGE) FROM private_message WHERE ID_USER_TO=? AND IS_READ IS NULL", $user->id)->fetchField();
    }
    
    public function setMessagesRead($idUserFrom, $idUserTo)
    {
        $this->database->query("UPDATE private_message SET IS_READ=NOW() WHERE ID_USER_FROM=? AND ID_USER_TO=? AND IS_READ IS NULL", $idUserFrom, $idUserTo);
    }
    
    public function insertMessage(PrivateMessage $message)
    {
        $this->database->table('private_message')->insert(array(
                    'TEXT' => $message->text,
                    'ID_USER_FROM' => $message->idUserFrom,
                    'ID_USER_TO' => $message->idUserTo,
                    'CREATED_BY' => $this->user->id
            ));
    }
      
    
}
