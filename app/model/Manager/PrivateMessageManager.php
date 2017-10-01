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
        $messages = $this->database->query("SELECT T1.TEXT, T1.ID_PRIVATE_MESSAGE, T2.NAME, T2.SURNAME, T1.CREATED, T2.URL_ID,
                T2.PROFILE_FILENAME,T2.PROFILE_PATH,T2.SEX
                FROM private_message T1
                LEFT JOIN vw_user_detail T2 ON T1.ID_USER_FROM=T2.ID_USER 
                WHERE T1.ID_USER_TO=?
                ORDER BY CREATED DESC LIMIT 20", $user->id)->fetchAll();
        foreach($messages as $message) {
            $mess = new PrivateMessage();
            $user = new User();
            $user->surname = $message->SURNAME;
            $user->name = $message->NAME;
            $user->urlId = $message->URL_ID;
            if($message->PROFILE_FILENAME) {
                $user->profileImage = "https://cdn.lato.cz/" . $message->PROFILE_PATH . "/" . $message->PROFILE_FILENAME;
            } else {
                if($message->SEX == 'M') {
                    $user->profileImage = '/images/default-avatar_man.png';
                } else {
                    $user->profileImage = '/images/default-avatar_woman.png';
                }
            }
            
            $mess->text = $message->TEXT;
            $mess->id = $message->ID_PRIVATE_MESSAGE;
            $mess->created = $message->CREATED;
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
                $user->surname = $message->SURNAME_F;
                $user->name = $message->NAME_F;
                $user->urlId = $message->URL_ID_F;
                $user->profileImage = User::createProfilePath($message->PROFILE_PATH_F, $message->PROFILE_FILENAME_F, $message->SEX_F);
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
    
    public function setMessagesRead($idUser)
    {
        $data = array('IS_READ' => date('Y-m-d H:i:s'));
        $this->database->query("UPDATE private_message SET ? WHERE ID_USER_TO=? AND IS_READ IS NULL", $data, $idUser);
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
