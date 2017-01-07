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
use App\Model\Manager\NotificationManager;
use App\Model\Manager\GroupManager;

/**
 * Description of MessageManager
 *
 * @author Radaq
 */
class MessageManager extends BaseManager {
     
    /** @var NotificationManager @inject */
    private $notificationManager;
    
    /** @var GroupManager @inject */
    private $groupManager;
    
    
    public function __construct(Nette\Database\Context $database,
                    Nette\Security\User $user,
            NotificationManager $notificationManager,
            GroupManager $groupManager
    )
    {
            $this->database = $database;
            $this->user = $user;
            $this->notificationManager = $notificationManager;
            $this->groupManager = $groupManager;
    }
    
    public function cloneMessage(Message $message, \App\Model\Entities\Group $toGroup) 
    {
        $message->idGroup = $toGroup->id;
        $this->createMessage($message, array());
    }
    
    
    public function createMessage(Message $message, $attachments)
    {
        $this->database->beginTransaction();
        
        if(empty($message->id)) {
            $this->database->table('message')->insert(array(
                    'TEXT' => $message->getText(),
                    'ID_USER' => $message->user->id,
                    'ID_GROUP' => $message->idGroup,
                    'ID_TYPE' => $message->idType,
                    'CREATED_BY' => $this->user->id,
            ));
            $idMessage = $this->database->query("SELECT MAX(ID_MESSAGE) FROM message")->fetchField(); 
        } else {
            $data = array(
                'TEXT' => $message->text,
                'LAST_CHANGE' => new \DateTime
            );
            $this->database->query("UPDATE message SET ? WHERE ID_MESSAGE=?", $data, $message->id);
            $idMessage = $message->id;
        }
        
        foreach($attachments as $idAttach) {
            $this->addAttachment($idAttach, $idMessage);
        }

        $group = $this->groupManager->getGroup($message->idGroup);
        
        $notification = new \App\Model\Entities\Notification();
        $notification->title = "Nový přispěvek";
        $notification->participant = $message->getUser();
        $notification->text = $message->text;
        $notification->idGroup = $message->idGroup;
        $notification->idType = NotificationManager::TYPE_ADD_GROUP_MSG;
        $users = $this->groupManager->getGroupUsers($message->idGroup);
        if(!empty($users)) {
            $allowedUsers = $this->notificationManager->getUserAllowedNotification($users, NotificationManager::TYPE_ADD_GROUP_MSG);
            foreach($allowedUsers['notification'] as $user) {
                if($user->id != $message->getUser()->id) {
                    $notification->idUser = $user->id;
                    $this->notificationManager->addNotification($notification);          
                }
            } 
        }
        
        
        
        
        $this->database->commit();
        
        return $idMessage;
    }
    
    public function createComment(Comment $comment)
    {
        $this->database->table('comment')->insert(array(
                'TEXT' => $comment->text,
                'ID_USER' => $comment->user->id,
                'ID_MESSAGE' => $comment->idMessage,
                'CREATED_BY' => $this->user->id
            ));
    }
    
    public function getMessages($group, \App\Model\Entities\User $user, $deleted=false)
    {
        $return = array();
        if($deleted) {
            $delete = array(0,1);
        } else {
            $delete = array(0);
        }
        $messages = $this->database->query("SELECT T1.TEXT, T1.ID_TYPE, T1.ID_MESSAGE, T2.ID_USER, T2.SEX, T2.URL_ID, T2.NAME, T2.SURNAME, T1.CREATED_WHEN,
                        T3.PATH,
                        T3.FILENAME,
                        T1.PRIORITY,
                        T4.ACTIVE AS IS_FOLLOWED,
                        T1.DELETED,
                        T5.ID_TASK,
                        T5.DEADLINE,
                        T5.NAME AS TASK_NAME,
                        T6.ID_COMMIT,
                        T6.CREATED_WHEN AS COMMIT_CREATED,
                        T6.UPDATED_WHEN AS COMMIT_UPDATED,
                        T7.COMMIT_COUNT
                FROM message T1 
                LEFT JOIN user T2 ON T1.ID_USER=T2.ID_USER 
                LEFT JOIN file_list T3 ON T3.ID_FILE=T2.PROFILE_IMAGE
                LEFT JOIN message_following T4 ON (T1.ID_MESSAGE = T4.ID_MESSAGE AND T4.ID_USER=? AND T4.ACTIVE=1)
                LEFT JOIN tasks T5 ON T1.ID_MESSAGE = T5.ID_MESSAGE
                LEFT JOIN task_commit T6 ON (T6.ID_TASK=T5.ID_TASK AND T6.ID_USER=?)
                LEFT JOIN (SELECT COUNT(ID_COMMIT) AS COMMIT_COUNT, ID_TASK FROM task_commit GROUP BY ID_TASK) T7 ON T7.ID_TASK=T5.ID_TASK
                WHERE T1.ID_GROUP=? AND T1.DELETED IN (?)
                ORDER BY PRIORITY DESC, CREATED_WHEN DESC LIMIT 10", $user->id, $user->id, $group->id, $delete)->fetchAll();
        $now = new \DateTime();
        foreach($messages as $message) {
            $mess = new Message();
            $user = new User();
            $user->surname = $message->SURNAME;
            $user->name = $message->NAME;
            $user->id = $message->ID_USER;
            $user->urlId = $message->URL_ID;
            $user->profileImage = User::createProfilePath($message->PATH, $message->FILENAME, $message->SEX);
            
            $mess->text = $message->TEXT;
            $mess->id = $message->ID_MESSAGE;
            $mess->created = $message->CREATED_WHEN;
            $mess->user = $user;
            $mess->followed = $message->IS_FOLLOWED;
            $mess->priority = $message->PRIORITY;
            $mess->deleted = $message->DELETED;
            $mess->idType = $message->ID_TYPE;
            $mess->attachments = $this->getAttachments($message->ID_MESSAGE);
            if($message->ID_TYPE == Message::TYPE_HOMEWORK) {
                $mess->task = new \App\Model\Entities\Task();
                if(!empty($message->ID_TASK)) {
                    $mess->task->idTask = $message->ID_TASK;
                    $mess->task->title = $message->TASK_NAME;
                    $mess->task->deadline = $message->DEADLINE;
                    $mess->task->timeLeft = $now->diff($message->DEADLINE);
                    $mess->task->commitCount = $message->COMMIT_COUNT;
                    if(!empty($message->ID_COMMIT)) {
                        $commit = new \App\Model\Entities\TaskCommit();
                        $commit->idCommit = $message->ID_COMMIT;
                        $commit->created = $message->COMMIT_CREATED;
                        $commit->updated = $message->COMMIT_UPDATED;
                        $mess->task->commit = $commit;
                    }
                }
            }
            
            $return[] = $mess;
        }
        
        return $return;
    }
        
    public function getMessage($idMessage)
    {
        $message = $this->database->query("SELECT T1.TEXT, T1.ID_MESSAGE, T2.ID_USER, T2.NAME, T2.SURNAME, T1.CREATED_WHEN,
                        T3.PATH,
                        T3.FILENAME,
                        T2.URL_ID,
                        T2.SEX,
                        T1.ID_TYPE,
                        T4.ID_TASK,
                        T4.DEADLINE,
                        T4.ONLINE,
                        T4.NAME AS TASK_NAME
                FROM message T1 
                LEFT JOIN user T2 ON T1.ID_USER=T2.ID_USER 
                LEFT JOIN file_list T3 ON T3.ID_FILE=T2.PROFILE_IMAGE
                LEFT JOIN tasks T4 ON T1.ID_MESSAGE = T4.ID_MESSAGE
                WHERE T1.ID_MESSAGE=? AND T1.DELETED=0", $idMessage)->fetch();
        
        $mess = new Message();
        $user = new User();
        $user->surname = $message->SURNAME;
        $user->name = $message->NAME;
        $user->id = $message->ID_USER;
        $user->urlId = $message->URL_ID;
        $user->profileImage = User::createProfilePath($message->PATH, $message->FILENAME, $message->SEX);

        $mess->text = $message->TEXT;
        $mess->id = $message->ID_MESSAGE;
        $mess->created = $message->CREATED_WHEN;
        $mess->user = $user;
        $mess->idType = $message->ID_TYPE;
        $mess->attachments = $this->getAttachments($message->ID_MESSAGE);
        $now = new \DateTime();
        if($message->ID_TYPE == Message::TYPE_HOMEWORK) {
            $mess->task = new \App\Model\Entities\Task();
            $mess->task->idTask = $message->ID_TASK;
            $mess->task->title = $message->TASK_NAME;
            $mess->task->deadline = $message->DEADLINE;
            $mess->task->online = $message->ONLINE;
            $mess->task->timeLeft = $now->diff($message->DEADLINE);
        }
        return $mess;
    }
    
    public function getAttachments($idMessage) {
        $return = array('media' => array(), 'files' => array());
        $attachments = $this->database->query("SELECT T2.ID_FILE, T2.ID_TYPE, T2.PATH, T2.FILENAME FROM message_attachment T1
            LEFT JOIN file_list T2 ON T1.ID_FILE=T2.ID_FILE WHERE T1.ID_MESSAGE=?", $idMessage)->fetchAll();    
        foreach($attachments as $attach) {
            if($attach->ID_TYPE == 1) {
                $return['media'][$attach->ID_FILE]['type'] = $attach->ID_TYPE;
                $return['media'][$attach->ID_FILE]['path'] = $attach->PATH;
                $return['media'][$attach->ID_FILE]['filename'] = $attach->FILENAME;   
                $return['media'][$attach->ID_FILE]['id_file'] = $attach->ID_FILE;   
            } else {
                $return['files'][$attach->ID_FILE]['type'] = $attach->ID_TYPE;
                $return['files'][$attach->ID_FILE]['path'] = $attach->PATH;
                $return['files'][$attach->ID_FILE]['filename'] = $attach->FILENAME; 
                $return['files'][$attach->ID_FILE]['id_file'] = $attach->ID_FILE;  
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
        $messages = $this->database->query("SELECT T1.ID_COMMENT, T1.TEXT, T1.CREATED_WHEN, T2.NAME AS USER_NAME, 
                    T3.PATH,
                    T2.ID_USER,
                    T3.FILENAME,
                    T2.SEX,
                    T2.URL_ID,
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
            $user->urlId = $comment->URL_ID;
            $user->profileImage = User::createProfilePath($comment->PATH, $comment->FILENAME, $comment->SEX);
            
            $comm->text = $comment->TEXT;
            $comm->id = $comment->ID_COMMENT;
            $comm->created = $comment->CREATED_WHEN;
            $now = new \DateTime();
            
            $comm->sinceStart = $comment->CREATED_WHEN->diff($now);
            $comm->user = $user;
            $return[] = $comm;
        }
        
        return $return;
    }
    
    public function newMessages($date)
    {
        $count = $this->database->query("SELECT COUNT(*) FROM message WHERE CREATED_WHEN>=?", $date)->fetch();
        return current($count);
    }
    
    public function deleteMessage(Message $message)
    {
        $data = array('DELETED' => 1);
        $this->database->query("UPDATE message SET ? WHERE ID_MESSAGE=?", $data, $message->id);
    }
    
    public function topMessage(Message $message, $enable = true)
    {
        if($enable) {
            $data = array('PRIORITY' => 1);
        } else {
            $data = array('PRIORITY' => 0);
        }
        $this->database->query("UPDATE message SET ? WHERE ID_MESSAGE=?", $data, $message->id);
    }
    
    public function followMessage(Message $message,User $user, $enable = true)
    {
        if($enable) {
            $data = array('ACTIVE' => 1);
        } else {
            $data = array('ACTIVE' => 0);
        }
        
        $followed = $this->database->query("SELECT ID FROM message_following WHERE ID_MESSAGE=? AND ID_USER=?", $message->id, $user->id)->fetchField();
        
        if(!empty($followed)) {
            $this->database->query("UPDATE message_following SET ? WHERE ID=?", $data, $followed);
        } else {
            $this->database->table('message_following')->insert(array(
                'ID_MESSAGE' => $message->id,
                'ID_USER' => $user->id
            ));
        }
        
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
