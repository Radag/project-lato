<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model;

use Nette;
use App\Model\Entities\Message;
use App\Model\Entities\Comment;

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
    
    public function createComment(Comment $comment)
    {
        $this->database->table('comment')->insert(array(
                'TEXT' => $comment->text,
                'ID_USER' => $comment->user->id,
                'ID_MESSAGE' => $comment->idMessage
            ));
    }
    
    public function getMessages()
    {
        $return = array();
        $messages = $this->database->query("SELECT T1.TEXT, T1.ID_MESSAGE, T2.NAME, T2.SURNAME, T1.CREATED FROM message T1 LEFT JOIN user T2 ON T1.ID_USER=T2.ID_USER ORDER BY CREATED DESC LIMIT 10")->fetchAll();
        foreach($messages as $message) {
            $mess = new Message();
            $user = new Entities\User();
            $user->surname = $message->SURNAME;
            $user->name = $message->NAME;
            $mess->text = $message->TEXT;
            $mess->id = $message->ID_MESSAGE;
            $mess->created = $message->CREATED;
            $mess->user = $user;
            $return[] = $mess;
        }
        
        return $return;
    }
    
    
    public function getComments($idMessage)
    {
        $return = array();
        $messages = $this->database->query("SELECT * FROM comment WHERE ID_MESSAGE=?", $idMessage)->fetchAll();
        foreach($messages as $comment) {
            $comm = new Comment();
//            $user = new Entities\User();
//            $user->surname = $message->SURNAME;
//            $user->name = $message->NAME;
            $comm->text = $comment->TEXT;
            $comm->id = $comment->ID_COMMENT;
            $comm->created = $comment->CREATED;
            //$mess->user = $user;
            $return[] = $comm;
        }
        
        return $return;
    }
    
    public function newMessages($date)
    {
        $count = $this->database->query("SELECT COUNT(*) FROM message WHERE CREATED>=?", $date)->fetch();
        return current($count);
    }
      
    
}