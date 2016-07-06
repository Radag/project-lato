<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use Nette;
use App\Model\Entities\Message;
use App\Model\Entities\Comment;
use App\Model\Entities\User;

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
    
    public function createMessage(Message $message, $attachments)
    {
        $this->database->beginTransaction();
        $this->database->table('message')->insert(array(
                    'TEXT' => $message->getText(),
                    'ID_USER' => $message->getUser()->id,
                    'ID_GROUP' => $message->idGroup
            ));
        
        $idMessage = $this->database->query("SELECT MAX(ID_MESSAGE) FROM message")->fetchField();
        foreach($attachments as $idAttach) {
            $this->addAttachment($idAttach, $idMessage);
        }
        
        $this->database->commit();
    }
    
    public function createComment(Comment $comment)
    {
        $this->database->table('comment')->insert(array(
                'TEXT' => $comment->text,
                'ID_USER' => $comment->user->id,
                'ID_MESSAGE' => $comment->idMessage
            ));
    }
    
    public function getMessages($group)
    {
        $return = array();
        $messages = $this->database->query("SELECT T1.TEXT, T1.ID_MESSAGE, T2.ID_USER, T2.NAME, T2.SURNAME, T1.CREATED,
                        T3.PATH,
                        T3.FILENAME
                FROM message T1 
                LEFT JOIN user T2 ON T1.ID_USER=T2.ID_USER 
                LEFT JOIN file_list T3 ON T3.ID_FILE=T2.PROFILE_IMAGE
                WHERE T1.ID_GROUP=?
                ORDER BY CREATED DESC LIMIT 10", $group->id)->fetchAll();
        foreach($messages as $message) {
            $mess = new Message();
            $user = new User();
            $user->surname = $message->SURNAME;
            $user->name = $message->NAME;
            $user->id = $message->ID_USER;
            $user->profileImage = "https://cdn.lato.cz/" . $message->PATH . "/" . $message->FILENAME;
            $mess->text = $message->TEXT;
            $mess->id = $message->ID_MESSAGE;
            $mess->created = $message->CREATED;
            $mess->user = $user;
            $mess->attachments = $this->getAttachments($message->ID_MESSAGE);
            $return[] = $mess;
        }
        
        return $return;
    }
    
    public function getAttachments($idMessage) {
        $return = array();
        $attachments = $this->database->query("SELECT T2.ID_FILE, T2.ID_TYPE, T2.PATH, T2.FILENAME FROM message_attachment T1
            LEFT JOIN file_list T2 ON T1.ID_FILE=T2.ID_FILE WHERE T1.ID_MESSAGE=?", $idMessage)->fetchAll();    
        foreach($attachments as $attach) {
            if($attach->ID_TYPE == 1) {
                $return['media'][$attach->ID_FILE]['type'] = $attach->ID_TYPE;
                $return['media'][$attach->ID_FILE]['path'] = $attach->PATH;
                $return['media'][$attach->ID_FILE]['filename'] = $attach->FILENAME;   
            } else {
                $return['files'][$attach->ID_FILE]['type'] = $attach->ID_TYPE;
                $return['files'][$attach->ID_FILE]['path'] = $attach->PATH;
                $return['files'][$attach->ID_FILE]['filename'] = $attach->FILENAME;   
            }
        }
        
        return $return;
    }
    
    public function getDicscutionMembers($idMessage) 
    {
        $membr = array();   
        $comm  = $this->getComments($idMessage); 
        foreach($comm as $c) {
            $id = (int)$c->user->id;
            if(!array_key_exists($id, $membr)) {
                $membr[$id] = $c->user;
            }
        }
        return $membr;
    }
    
    
    public function getComments($idMessage)
    {
        $return = array();
        $messages = $this->database->query("SELECT T1.ID_COMMENT, T1.TEXT, T1.CREATED, T2.NAME AS USER_NAME, 
                    T3.PATH,
                    T2.ID_USER,
                    T3.FILENAME,
                    T2.SURNAME AS USER_SURNAME FROM comment T1
                    LEFT JOIN user T2 ON T1.ID_USER=T2.ID_USER
                    LEFT JOIN file_list T3 ON T3.ID_FILE=T2.PROFILE_IMAGE
                    WHERE ID_MESSAGE=?", $idMessage)->fetchAll();
        foreach($messages as $comment) {
            $comm = new Comment();
            $user = new User();
            $user->surname = $comment->USER_SURNAME;
            $user->name = $comment->USER_NAME;  
            $user->id = $comment->ID_USER;
            $user->profileImage = "https://cdn.lato.cz/" . $comment->PATH . "/" . $comment->FILENAME;
            $comm->text = $comment->TEXT;
            $comm->id = $comment->ID_COMMENT;
            $comm->created = $comment->CREATED;
            $now = new \DateTime();
            
            $comm->sinceStart = $comment->CREATED->diff($now);
            $comm->user = $user;
            $return[] = $comm;
        }
        
        return $return;
    }
    
    public function newMessages($date)
    {
        $count = $this->database->query("SELECT COUNT(*) FROM message WHERE CREATED>=?", $date)->fetch();
        return current($count);
    }
    
    public function addAttachment($idFile, $idMessage = null)
    {
        if(!empty($idFile)) {
            if($idMessage === null) {
    //            $message = new Message;
    //            $message->text = "";
    //            $message->
    //            $this->createMessage($message);
            } else {

                $this->database->table('message_attachment')->insert(array(
                    'ID_MESSAGE' => $idMessage,
                    'ID_FILE' => $idFile
                ));
            }
        }
    }
}
