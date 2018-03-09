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
    
    /** @var \Dibi\Connection  */
    protected $db;
    
    public function __construct(Nette\Database\Context $database,
                    Nette\Security\User $user,
            NotificationManager $notificationManager,
            GroupManager $groupManager,
             \Dibi\Connection $db
    )
    {
            $this->database = $database;
            $this->user = $user;
            $this->notificationManager = $notificationManager;
            $this->groupManager = $groupManager;
            $this->db = $db;
    }
    
    public function cloneMessage(Message $message, \App\Model\Entities\Group $toGroup) 
    {
        $message->idGroup = $toGroup->id;
        $this->createMessage($message, array());
    }
    
    
    public function createMessage(Message $message, $attachments)
    {
        $this->db->begin();
        if(empty($message->id)) {
            $this->db->query("INSERT INTO message", [
                'text' => $message->text,
                'user_id' => $message->user->id,
                'group_id' => $message->idGroup,
                'type' => $message->type,
                'created_by' => $this->user->id,
            ]);
            $message->id = $this->db->getInsertId();
        } else {
            $this->db->query("UPDATE message SET ",[
                'text' => $message->text,
                'type' => $message->type
            ] ,"WHERE id=?", $message->id);
        }
        
        foreach($attachments as $idAttach) {
            $this->addAttachment($idAttach, $message->id);
        }

        $this->db->commit();
        return $message->id;
    }
    
    public function createComment(Comment $comment)
    {
        $this->db->query("INSERT INTO comment", [
            'text' => $comment->text,
            'user_id' => $comment->user->id,
            'message_id' => $comment->idMessage,
            'created_by' => $this->user->id
        ]);
    }
    
    public function getMessages(\App\Model\Entities\Group $group, \App\Model\Entities\User $user, $filter = 'all')
    {
        $return = [];
        if($group->showDeleted) {
            $delete = [0,1];
        } else {
            $delete = [0];
        }
        if($filter !== 'all') {
            $filters = [$filter];
        } else {
            $filters = ['notice', 'material', 'homework', 'task'];
        }

        $messages = $this->db->fetchAll("SELECT T1.text, T1.type, T1.id, T2.id AS user_id, T2.sex, T2.slug, T2.name, T2.surname, T1.created_when,
                        T10.profile_image,
                        T1.top,
                        T1.deleted,
                        T5.id AS task_id,
                        T5.deadline,
                        T5.name AS task_name,
                        T5.online,
                        T6.id AS commit_id,
                        T6.created_when AS commit_created,
                        T6.updated_when AS commit_updated,
                        T7.commit_count,
                        T8.title,
                        T5.create_classification,
                        T9.id AS id_classification_group
                FROM message T1 
                LEFT JOIN user T2 ON T1.user_id=T2.id 
                JOIN user_real T10 ON T10.id=T2.id
                LEFT JOIN task T5 ON T1.id = T5.message_id
                LEFT JOIN task_commit T6 ON (T5.id=T6.task_id AND T6.user_id=?)
                LEFT JOIN (SELECT COUNT(id) AS commit_count, task_id FROM task_commit GROUP BY task_id) T7 ON T7.task_id=T5.id
                LEFT JOIN message_material T8 ON T1.id=T8.message_id
                LEFT JOIN classification_group T9 ON T9.task_id = T5.id
                WHERE T1.group_id=? AND T1.deleted IN (?) AND T1.type IN (?) 
                ORDER BY IFNULL(T1.top, T1.created_when) DESC", $user->id, $group->id, $delete, $filters);
        $now = new \DateTime();
        
        $attachmentsData = $this->db->fetchAll("SELECT 
                    T1.id, T3.id AS file_id, T3.extension, T3.mime, T3.type, T3.path, T3.filename
                FROM message T1 
                JOIN message_attachment T2 ON T1.id=T2.message_id
                JOIN file_list T3 ON T2.file_id=T3.id
                WHERE T1.group_id=? AND T1.deleted IN (?) AND T1.type IN (?)", $group->id, $delete, $filters);
        $attachments = $this->getAttachments($attachmentsData);;
       
        foreach($messages as $message) {
            
            $mess = new Message();
            $userObject = new User();
            $userObject->surname = $message->surname;
            $userObject->name = $message->name;
            $userObject->id = $message->user_id;
            $userObject->slug = $message->slug;
            $userObject->profileImage = User::createProfilePath($message->profile_image, $message->sex);
            
            $mess->text = $message->text;
            $mess->id = $message->id;
            $mess->created = $message->created_when;
            $mess->user = $userObject;
            $mess->top = $message->top;
            $mess->deleted = $message->deleted;
            $mess->type = $message->type;
            $mess->isCreator = $user->id == $userObject->id;
            if(isset($attachments[$mess->id])) {
                $mess->attachments = $attachments[$mess->id];
            } else {
                $mess->attachments = null;
            }
            
            if($message->type == Message::TYPE_TASK) {
                if(!empty($message->task_id)) {
                    $mess->task = new \App\Model\Entities\Task();
                    $mess->task->id = $message->task_id;
                    $mess->task->title = $message->task_name;
                    $mess->task->deadline = $message->deadline;
                    $mess->task->online = $message->online;
                    $mess->task->timeLeft = $now->diff($message->deadline);
                    $mess->task->commitCount = $message->commit_count;
                    $mess->task->createClassification = $message->create_classification;
                    $mess->task->isCreator = $user->id == $userObject->id;
                    $mess->task->idClassificationGroup = $message->id_classification_group;
                    if(!empty($message->commit_id)) {
                        $commit = new \App\Model\Entities\TaskCommit();
                        $commit->idCommit = $message->commit_id;
                        $commit->created = $message->commit_created;
                        $commit->updated = $message->commit_updated;
                        $mess->task->commit = $commit;
                    }
                }
            }
            if($message->type == Message::TYPE_MATERIALS) {
                $mess->title = $message->title;
            }
            $return[$mess->id] = $mess;
        }
        
        $comments = $this->getComments($group->id, $delete, $filters);
        return ['messages' => $return, 'comments' => $comments];
    }
    
    public function getAttachments($attachments) {
        $return = [];
        if($attachments) {
            foreach($attachments as $attach) {
                if(!isset($return[$attach->id])) {
                    $return[$attach->id] = ['media' => [], 'files' => []];  
                }
                if($attach->type == 'image') {
                    $return[$attach->id]['media'][$attach->file_id]['type'] = $attach->type;
                    $return[$attach->id]['media'][$attach->file_id]['extension'] = $attach->extension;
                    $return[$attach->id]['media'][$attach->file_id]['path'] = $attach->path;
                    $return[$attach->id]['media'][$attach->file_id]['mime'] = $attach->mime;
                    $return[$attach->id]['media'][$attach->file_id]['filename'] = $attach->filename;   
                    $return[$attach->id]['media'][$attach->file_id]['id_file'] = $attach->file_id;   
                } else {
                    $return[$attach->id]['files'][$attach->file_id]['type'] = $attach->type;
                    $return[$attach->id]['files'][$attach->file_id]['extension'] = $attach->extension;;
                    $return[$attach->id]['files'][$attach->file_id]['mime'] = $attach->mime;
                    $return[$attach->id]['files'][$attach->file_id]['path'] = $attach->path;
                    $return[$attach->id]['files'][$attach->file_id]['filename'] = $attach->filename; 
                    $return[$attach->id]['files'][$attach->file_id]['id_file'] = $attach->file_id;  
                }
            }
        }
        return $return;
    }
    
    public function getComments($groupId, $delete, $filtres)
    {
        $return = [];
        $commentsData = $this->db->fetchAll("SELECT 
                    T1.id AS message_id,
                    T2.id,
                    T2.text, 
                    T2.created_when, 
                    T3.name AS user_name, 
                    T3.id AS user_id,
                    T4.profile_image,
                    T3.sex,
                    T3.slug,
                    T3.surname AS user_surname
                FROM message T1 
                JOIN comment T2 ON T1.id=T2.message_id
                JOIN user T3 ON T2.user_id=T3.id
                JOIN user_real T4 ON T4.id=T3.id
                WHERE T1.group_id=? AND T1.deleted IN (?) AND T1.type IN (?) ORDER BY T2.created_when ASC", $groupId, $delete, $filtres);
        
        if($commentsData) {
            foreach($commentsData as $comment) {
                $comm = new Comment();
                $user = new User();
                $user->surname = $comment->user_surname;
                $user->name = $comment->user_name;  
                $user->id = $comment->user_id;
                $user->slug = $comment->slug;
                $user->profileImage = User::createProfilePath($comment->profile_image, $comment->sex);

                $comm->text = $comment->text;
                $comm->id = $comment->id;
                $comm->created = $comment->created_when;
                $comm->sinceStart = $comment->created_when->diff(new \DateTime());
                $comm->user = $user;
                $return[$comment->message_id][] = $comm;
            }
        }

        return $return;
    }
        
    
    public function getMessageComments($messageId)
    {
        $commentsData = $this->db->fetchAll("SELECT 
                    T2.id,
                    T2.text, 
                    T2.created_when, 
                    T3.name AS user_name, 
                    T3.id AS user_id,
                    T4.profile_image,
                    T3.sex,
                    T3.slug,
                    T3.surname AS user_surname
                FROM comment T2
                JOIN user T3 ON T2.user_id=T3.id
                JOIN user_real T4 ON T3.id=T4.id
                WHERE T2.message_id=? ORDER BY T2.created_when ASC", $messageId);
        
        $return = [];
        if($commentsData) {
            foreach($commentsData as $comment) {
                $comm = new Comment();
                $user = new User();
                $user->surname = $comment->user_surname;
                $user->name = $comment->user_name;  
                $user->id = $comment->user_id;
                $user->slug = $comment->slug;
                $user->profileImage = User::createProfilePath($comment->profile_image, $comment->sex);

                $comm->text = $comment->text;
                $comm->id = $comment->id;
                $comm->created = $comment->created_when;
                $comm->sinceStart = $comment->created_when->diff(new \DateTime());
                $comm->user = $user;
                $return[] = $comm;
            }
        }

        return $return;
    }
    
    public function getMessage($idMessage, $user)
    {
          $message = $this->db->fetch("SELECT T1.text, T1.type, T1.id, T2.id AS user_id, T2.sex, T2.slug, T2.name, T2.surname, T1.created_when,
                        T9.profile_image,
                        T1.top,
                        T1.deleted,
                        T5.id AS task_id,
                        T5.deadline,
                        T5.name AS task_name,
                        T6.id AS commit_id,
                        T6.created_when AS commit_created,
                        T6.updated_when AS commit_updated,
                        T7.commit_count,
                        T8.title,
                        T5.online,
                        T5.create_classification
                FROM message T1 
                LEFT JOIN user T2 ON T1.user_id=T2.id 
                JOIN user_real T9 ON T9.id=T2.id
                LEFT JOIN task T5 ON T1.id = T5.message_id
                LEFT JOIN task_commit T6 ON (T5.id=T6.task_id AND T6.user_id=?)
                LEFT JOIN (SELECT COUNT(id) AS commit_count, task_id FROM task_commit GROUP BY task_id) T7 ON T7.task_id=T5.id
                LEFT JOIN message_material T8 ON T1.id=T8.message_id
                WHERE T1.id=?", $user->id, $idMessage);
        $now = new \DateTime();
        
        $attachmentsData = $this->db->fetchAll("SELECT 
                    T1.id, T3.id AS file_id, T3.extension, T3.mime, T3.type, T3.path, T3.filename
                FROM message T1 
                JOIN message_attachment T2 ON T1.id=T2.message_id
                JOIN file_list T3 ON T2.file_id=T3.id
                WHERE T1.id=?", $idMessage);
        $attachments = $this->getAttachments($attachmentsData);;
       
        $mess = new Message();
        $user = new User();
        $user->surname = $message->surname;
        $user->name = $message->name;
        $user->id = $message->user_id;
        $user->slug = $message->slug;
        $user->profileImage = User::createProfilePath($message->profile_image, $message->sex);

        $mess->text = $message->text;
        $mess->id = $message->id;
        $mess->created = $message->created_when;
        $mess->user = $user;
        $mess->top = $message->top;
        $mess->deleted = $message->deleted;
        $mess->type = $message->type;
        if(isset($attachments[$mess->id])) {
            $mess->attachments = $attachments[$mess->id];
        } else {
            $mess->attachments = null;
        }

        if($message->type == Message::TYPE_TASK) {
            if(!empty($message->task_id)) {
                $mess->task = new \App\Model\Entities\Task();
                $mess->task->id = $message->task_id;
                $mess->task->title = $message->task_name;
                $mess->task->deadline = $message->deadline;
                $mess->task->online = $message->online;
                $mess->task->timeLeft = $now->diff($message->deadline);
                $mess->task->commitCount = $message->commit_count;
                $mess->task->create_classification = $message->create_classification;                
                if(!empty($message->commit_id)) {
                    $commit = new \App\Model\Entities\TaskCommit();
                    $commit->idCommit = $message->commit_id;
                    $commit->created = $message->commit_created;
                    $commit->updated = $message->commit_updated;
                    $mess->task->commit = $commit;
                }
            }
        }
        if($message->type == Message::TYPE_MATERIALS) {
            $mess->title = $message->title;
        }
        return $mess;

    }    
        
    public function newMessages($date)
    {
        $count = $this->database->query("SELECT COUNT(*) FROM message WHERE CREATED_WHEN>=?", $date)->fetch();
        return current($count);
    }
    
    public function deleteMessage($idMessage, $state = true)
    {
        $state ? $deleted = 1 : $deleted = 0;
        $this->db->query("UPDATE message SET deleted=? WHERE id=?", $deleted, $idMessage);
    }
    
    public function topMessage($idMessage, $enable = true)
    {
        if($enable) {
            $this->db->query("UPDATE message SET top=NOW() WHERE id=?", $idMessage);
        } else {
            $this->db->query("UPDATE message SET top=NULL WHERE id=?", $idMessage);
        }
        
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
                $this->db->query("INSERT INTO message_attachment", [
                    'message_id' => $idMessage,
                    'file_id' => $idFile
                ]);
            }
        }
    }
    
    public function createMaterial(Message $message)
    {
        $material = $this->db->fetch("SELECT * FROM message_material WHERE message_id=?", $message->id);
        if($material) {
            $this->db->query("UPDATE message_material SET", [
                'title' => $message->title,
                'message_id' => $message->id
            ], "WHERE id=?", $material->id);
        } else {
            $this->db->query("INSERT INTO message_material", [
                'title' => $message->title,
                'message_id' => $message->id
            ]);
        }
               
    }
}
