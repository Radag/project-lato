<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model;

use Nette;
use App\Model\Entities\Message;

/**
 * Description of MessageManager
 *
 * @author Radaq
 */
class MessageManager extends Nette\Object{
 
    
    /** @var Nette\Database\Context */
    private $database;


    public function __construct(Nette\Database\Context $database)
    {
            $this->database = $database;
    }
    
    public function createMessage(Message $message)
    {
        $this->database->table('message')->insert(array(
                    'TEXT' => $message->getText(),
                    'ID_USER' => $message->getUser()->id
            ));
    }
    
    public function getMessages()
    {
        $return = array();
        $messages = $this->database->query("SELECT T1.TEXT, T1.ID_MESSAGE, T2.NAME, T1.CREATED FROM message T1 LEFT JOIN user T2 ON T1.ID_USER=T2.ID_USER ORDER BY CREATED DESC")->fetchAll();
        foreach($messages as $message) {
            $mess = new Message();
            $user = new Entities\User();
            $user->setName($message->NAME);
            $mess->setText($message->TEXT);
            $mess->setId($message->ID_MESSAGE);
            $mess->setCreated($message->CREATED);
            $mess->setUser($user);
            $return[] = $mess;
        }
        
        return $return;
    }
      
    
}
