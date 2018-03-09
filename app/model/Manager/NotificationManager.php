<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use App\Model\Entities\Notification;

/**
 * Description of MessageManager
 *
 * @author Radaq
 */
class NotificationManager extends BaseManager
{   
    /*
    //přidání zprávy do skupiny
    const TYPE_ADD_GROUP_MSG = 'new_group_message';
    //přidání komentáře do skupiny
    const TYPE_ADD_COMMENT = 'new_group_comment';
    //nový člen ve skupině
    const TYPE_NEW_GROUP_MEMBER = 'new_group_member';
    //byl vyhozem ze skupiny
    const TYPE_REMOVE_FROM_GROUP = 'group_member_removed';
    //byl přidán do skupiny
    const TYPE_ADD_ADD_TO_GROUP = 'member_added_to_group';
    */
    
    
    //přidání zprávy do skupiny
    const TYPE_ADD_GROUP_MSG = 1;
    //přidání komentáře do skupiny
    const TYPE_ADD_COMMENT = 2;
    //nový člen ve skupině
    const TYPE_NEW_GROUP_MEMBER = 3;
    //byl vyhozem ze skupiny
    const TYPE_REMOVE_FROM_GROUP = 4;
    //byl přidán do skupiny
    const TYPE_ADD_ADD_TO_GROUP = 5;
    
    public function getNotifications($user)
    {
        $return = array();
        $messages = $this->db->fetchAll("SELECT 
                    T1.id AS not_id,
                    T1.text AS not_text,
                    T1.title AS not_title,
                    T2.id,
                    T2.name,
                    T2.surname,
                    T4.profile_image,
                    T2.sex,
                    T2.slug,
                    T3.slug AS group_slug,
                    T1.message_id
                FROM notification T1 
                LEFT JOIN user T2 ON T1.participant_id = T2.id
                LEFT JOIN user_real T4 ON T2.id=T4.id
                LEFT JOIN `group` T3 ON T1.group_id = T3.id
                WHERE T1.user_id=?
                ORDER BY T1.created_when DESC LIMIT 5", $user->id);
        foreach($messages as $message) {
            $participant = new \App\Model\Entities\User($message);
            $mess = new Notification();
            $mess->title = $message->not_title;
            $mess->text = $message->not_text;
            $mess->id = $message->not_id;
            $mess->participant = $participant;
            $mess->idGroup = $message->group_slug;
            $mess->idMessage = $message->message_id;
            $return[] = $mess;
        }
        
        return $return;
    }
    
    public function getUnreadNumber($user)
    {
        return $this->db->fetchSingle("SELECT COUNT(id) FROM notification WHERE user_id=? AND is_read IS NULL", $user->id);
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
    
    public function setSettings(\App\Model\Entities\User $user, $idType, $enable)
    {
        $type = $this->database->query("SELECT * FROM notification_settings WHERE ID_TYPE=? AND ID_USER=?", $idType, $user->id)->fetchAll();
        if($type) {
            $data = array('SHOW_NOTIFICATION' => $enable);
            $this->database->query("UPDATE notification_settings SET ? WHERE ID_USER=? AND ID_TYPE=?", $data, $user->id, $idType);
        } else {
            $this->database->table('notification_settings')->insert(array(
                'SHOW_NOTIFICATION' => $enable,
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
                $return['notification'][$all->ID_USER] = $userArray[$all->ID_USER];
            }
            
            if($all->SEND_BY_EMAIL == 1) {
                $return['mail'][$all->ID_USER] = $userArray[$all->ID_USER];
            }
        }
        
        return $return;
        
    }
    
    public function addNotificationType($type, $data)
    {
        switch($type) {
            case self::TYPE_ADD_GROUP_MSG : $this->addNotificationNewMessage($data['message'], $data['groupUsers']); break;
            case self::TYPE_NEW_GROUP_MEMBER : $this->addNotificationNewGroupMember($data['user'], $data['group']); break;
            case self::TYPE_REMOVE_FROM_GROUP : $this->addNotificationRemoveFromGroup($data['users'], $data['group']); break;
        }
    }
    
    
    public function addNotificationNewMessage(\App\Model\Entities\Message $message, $groupUsers) {
        $notification = new Notification();
        $notification->title = "Nový přispěvek";
        $notification->participant = $message->user;
        $notification->text = $message->text;
        $notification->idGroup = $message->idGroup;
        $notification->idType = self::TYPE_ADD_GROUP_MSG;
        $notification->idMessage = $message->id;

        if(!empty($groupUsers)) {
            $allowedUsers = $this->getUserAllowedNotification($groupUsers, self::TYPE_ADD_GROUP_MSG);
            foreach($allowedUsers['notification'] as $user) {
                if($user->id != $message->user->id) {
                    $notification->idUser = $user->id;
                    $this->addNotification($notification);          
                }
            } 
        }
    }
    
    public function addNotificationNewGroupMember(\App\Model\Entities\User $user, \App\Model\Entities\Group $group) 
    { 
        $notification = new Notification();
        $notification->participant = $user;
        $notification->title = "Nový člen";
        $notification->text = "Do vaší skupiny " . $group->name . " se přidal nový člen " . $user->username . ".";
        $notification->idUser = $group->owner->id;
        $notification->idType = self::TYPE_NEW_GROUP_MEMBER;
        
        $allowedUsers = $this->getUserAllowedNotification(array($group->owner), self::TYPE_NEW_GROUP_MEMBER);
        if(!empty($allowedUsers['notification'][$group->owner->id])) {
            $this->addNotification($notification);          
        }

    }
    
    public function addNotificationRemoveFromGroup($users, \App\Model\Entities\Group $group) 
    { 
        $notification = new Notification();
        $notification->title = "Byl jste vyhozen ze skupiny";
        $notification->text = "Byl jste vyhozen ze skupiny " . $group->name . ".";
        $notification->idGroup = $group->id;
        $notification->idType = self::TYPE_REMOVE_FROM_GROUP;
        
        $allowedUsers = $this->getUserAllowedNotification($users, self::TYPE_REMOVE_FROM_GROUP);
        
        foreach($allowedUsers['notification'] as $user) {
            $notification->idUser = $user->id;
            $this->addNotification($notification);          
        }
    }
    
}
