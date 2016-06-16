<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model;

use Nette;
use App\Model\Entities\PrivateMessage;

/**
 * Description of MessageManager
 *
 * @author Radaq
 */
class PrivateMessageManager extends Nette\Object{
 
    
    /** @var Nette\Database\Context */
    private $database;


    public function __construct(Nette\Database\Context $database)
    {
            $this->database = $database;
    }
    
   
    
    public function getMessages($user)
    {
        $return = array();
        $messages = $this->database->query("SELECT T1.TEXT, T1.ID_PRIVATE_MESSAGE, T2.NAME, T2.SURNAME, T1.CREATED FROM private_message T1 
                LEFT JOIN user T2 ON T1.ID_USER_FROM=T2.ID_USER 
                WHERE T1.ID_USER_TO=?
                ORDER BY CREATED DESC LIMIT 10", $user->id)->fetchAll();
        foreach($messages as $message) {
            $mess = new PrivateMessage();
            $user = new Entities\User();
            $user->surname = $message->SURNAME;
            $user->name = $message->NAME;
            $mess->text = $message->TEXT;
            $mess->id = $message->ID_PRIVATE_MESSAGE;
            $mess->created = $message->CREATED;
            $mess->user = $user;
            $return[] = $mess;
        }
        
        return $return;
    }
    
    
   
      
    
}
