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
    
   
    
    public function getMessages($user)
    {
        $return = array();
        $messages = $this->database->query("SELECT T1.TEXT, T1.TITLE, T1.ID_NOTIFICATION FROM notification T1 
                WHERE T1.ID_USER=?
                ORDER BY CREATED DESC LIMIT 10", $user->id)->fetchAll();
        foreach($messages as $message) {
            $mess = new Notification();
            $mess->title = $message->TITLE;
            $mess->text = $message->TEXT;
            $mess->id = $message->ID_NOTIFICATION;
            $return[] = $mess;
        }
        
        return $return;
    }
    
    public function addNotification(Notification $notification)
    {
        $this->database->table('notification')->insert(array(
                'TEXT' => $notification->text,
                'TITLE' => $notification->title,
                'ID_USER' => $notification->idUser
        ));
    }
    
    
   
      
    
}
