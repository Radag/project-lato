<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use Nette;
use App\Model\Entities\Notification;

/**
 * Description of MessageManager
 *
 * @author Radaq
 */
class NotificationManager extends BaseManager
{   
    
    const TYPE_ADD_GROUP_MSG = 1;
    const TYPE_ADD_COMMENT = 2;
    const TYPE_NEW_GROUP_MEMBER = 3;
    const TYPE_REMOVE_FROM_GROUP = 4;
    const TYPE_ADD_ADD_TO_GROUP = 5;
    
    
    public function getNotifications($user)
    {
        $return = array();
        $messages = $this->database->query("SELECT T1.TEXT, T1.TITLE, T1.ID_NOTIFICATION,
                    T2.NAME AS PART_NAME,
                    T2.SURNAME AS PART_SURNAME,
                    T2.PROFILE_PATH AS PART_PROFILE_PATH,
                    T2.PROFILE_FILENAME AS PART_PROFILE_FILENAME,
                    T2.USERNAME AS PART_USERNAME,
                    T2.URL_ID AS PART_URL_ID,
                    T3.URL_ID AS GROUP_URL_ID,
                    T1.ID_MESSAGE
                FROM notification T1 
                LEFT JOIN vw_user_detail T2 ON T1.ID_PARTICIPANT = T2.ID_USER
                LEFT JOIN groups T3 ON T1.ID_GROUP = T3.ID_GROUP
                WHERE T1.ID_USER=?
                ORDER BY CREATED DESC LIMIT 5", $user->id)->fetchAll();
        foreach($messages as $message) {
            $participant = new \App\Model\Entities\User;
            $participant->name = $message->PART_NAME;
            $participant->surname = $message->PART_SURNAME;
            $participant->username = $message->PART_NAME;
            $participant->profileImage = $message->PART_USERNAME;
            $participant->urlId = $message->PART_URL_ID;
            
            if($message->PART_PROFILE_PATH) {
                $participant->profileImage = "https://cdn.lato.cz/" . $message->PART_PROFILE_PATH . "/" . $message->PART_PROFILE_FILENAME;
             }
            
            $mess = new Notification();
            $mess->title = $message->TITLE;
            $mess->text = $message->TEXT;
            $mess->id = $message->ID_NOTIFICATION;
            $mess->participant = $participant;
            $mess->idGroup = $message->GROUP_URL_ID;
            $mess->idMessage = $message->ID_MESSAGE;
            $return[] = $mess;
        }
        
        return $return;
    }
    
    public function getUnreadNumber($user)
    {
        return $this->database->query("SELECT COUNT(ID_NOTIFICATION) FROM notification WHERE ID_USER=? AND IS_READ IS NULL", $user->id)->fetchField();
    }
    
    public function addNotification(Notification $notification)
    {
        $this->database->table('notification')->insert(array(
                'TEXT' => $notification->text,
                'TITLE' => $notification->title,
                'ID_USER' => $notification->idUser,
                'ID_TYPE' => $notification->idType,
                'ID_PARTICIPANT' => $notification->participant ? $notification->participant->id : null,
                'ID_GROUP' => $notification->idGroup ? $notification->idGroup : null,
                'ID_MESSAGE' => $notification->idMessage ? $notification->idMessage : null
        ));
    }
    
    public function setNotificationRead($idUser)
    {
        $data = array('IS_READ' => date('Y-m-d H:i:s'));
        $this->database->query("UPDATE notification SET ? WHERE ID_USER=? AND IS_READ IS NULL", $data, $idUser);
    }
   
    public function getNotificationTypes()
    {
        return $this->database->query("SELECT * FROM notification_type")->fetchAll();
    }
    
    public function getNotificationSettings(\App\Model\Entities\User $user)
    {
        return $this->database->query("SELECT T1.ID_TYPE, T1.NAME, T2.SEND_BY_EMAIL, T2.SHOW_NOTIFICATION FROM notification_type T1 LEFT JOIN notification_settings T2 ON T1.ID_TYPE=T2.ID_TYPE WHERE T2.ID_USER=?", $user->id)->fetchAll();
    }
    
    public function setSettings(\App\Model\Entities\User $user, $idType, $mail, $notification)
    {
        $type = $this->database->query("SELECT * FROM notification_settings WHERE ID_TYPE=? AND ID_USER=?", $idType, $user->id)->fetchAll();
        if($type) {
            $data = array('SHOW_NOTIFICATION' => $notification, 'SEND_BY_EMAIL' => $mail);
            $this->database->query("UPDATE notification_settings SET ? WHERE ID_USER=? AND ID_TYPE=?", $data, $user->id, $idType);
        } else {
            $this->database->table('notification_settings')->insert(array(
                'SHOW_NOTIFICATION' => $notification,
                'SEND_BY_EMAIL' => $mail,
                'ID_USER' => $user->id,
                'ID_TYPE' => $idType
            ));
        }
    }
    
    public function getUserAllowedNotification(array $users, $idType)
    {
        $return = array('notification' => array(), 'mail' => array());
        $userArray = array();
        foreach($users as $user) {
            $userArray[$user->id] = $user;
        }
        
        $allowed = $this->database->query("SELECT * FROM notification_settings WHERE ID_TYPE=? AND ID_USER IN (" . implode(',', array_keys($userArray)) . ")", $idType)->fetchAll();
        
        foreach($allowed as $all) {
            if($all->SHOW_NOTIFICATION == 1) {
                $return['notification'][] = $userArray[$all->ID_USER];
            }
            
            if($all->SEND_BY_EMAIL == 1) {
                $return['mail'][] = $userArray[$all->ID_USER];
            }
        }
        
        return $return;
        
    }
    
    public function sendToGroup($notification, $idGroup)
    {  
        $users = $this->groupManager->getNotificationUsers($idGroup, $notification);
        if(!empty($users)) {
            $allowedUsers = $this->notificationManager->getUserAllowedNotification($users, NotificationManager::TYPE_ADD_GROUP_MSG);
            foreach($allowedUsers['notification'] as $user) {
                $this->notificationManager->addNotification($notification);              
            } 
        }
        
    }
    
}
