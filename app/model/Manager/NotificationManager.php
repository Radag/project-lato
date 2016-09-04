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
class NotificationManager extends Nette\Object{
 
    
    /** @var Nette\Database\Context */
    private $database;


    public function __construct(Nette\Database\Context $database)
    {
            $this->database = $database;
    }
    
   
    
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
                    T3.URL_ID AS GROUP_URL_ID
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
                'ID_PARTICIPANT' => $notification->participant ? $notification->participant->id : null,
                'ID_GROUP' => $notification->idGroup ? $notification->idGroup : null
        ));
    }
    
    public function setNotificationRead($idUser)
    {
        $data = array('IS_READ' => date('Y-m-d H:i:s'));
        $this->database->query("UPDATE notification SET ? WHERE ID_USER=? AND IS_READ IS NULL", $data, $idUser);
    }
   
      
    
}
