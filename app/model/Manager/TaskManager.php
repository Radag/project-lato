<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use App\Model\Entities\Task;
use App\Model\Entities\TaskCommit;

/**
 * Description of TaskManager
 *
 * @author Radaq
 */
class TaskManager extends BaseManager 
{     
    
    public function createTask(Task $task)
    {
        $this->db->begin();
        $idTask = $this->db->fetchSingle("SELECT id FROM task WHERE message_id=?", $task->idMessage); 
        if($idTask) {
            $this->db->query("UPDATE task SET ", [
                'name' => $task->title,
                'deadline' => $task->deadline,
                'online' => $task->online,
                'create_classification' => $task->create_classification
            ], "WHERE id=?", $idTask);    
        } else {
            $this->db->query("INSERT INTO task", [
                'name' => $task->title,
                'message_id' => $task->idMessage,
                'deadline' => $task->deadline,
                'online' => $task->online,
                'create_classification' => $task->create_classification
            ]);
            $idTask = $this->db->getInsertId();
        }      
        $this->db->commit();
        
        return $idTask;
    }
    
    public function getClosestTask($groups) 
    {
        if(!empty($groups)) {
            $now = new \DateTime();
            
            $tasksArray = array();
            $tasks = $this->database->query("SELECT 
                            T1.id,
                            T1.name,
                            T1.message_id,
                            T1.deadline,
                            T2.text,
                            T2.group_id,
                            T3.id AS user_id,
                            T3.name AS user_name,
                            T3.surname AS user_surname,
                            T3.profile_image,
                            T3.sex AS user_sex,
                            T4.commit_count,
                            T5.id AS commit_id,
                            T5.created_when AS commit_created,
                            T5.updated_when AS commit_updated,
                            T6.task_users,
                            T1.online
                    FROM task T1 
                    JOIN message T2 ON (T1.message_id = T2.id AND T2.deleted=0)
                    JOIN user T3 ON (T2.user_id=T3.id)
                    LEFT JOIN (SELECT COUNT(id) AS commit_count, task_id FROM task_commit GROUP BY task_id) T4 ON T4.task_id=T1.id
                    LEFT JOIN task_commit T5 ON T1.id=T5.task_id
                    LEFT JOIN ( SELECT COUNT(*) AS task_users, M.id FROM message M JOIN group_user G ON M.group_id=G.group_id GROUP BY M.id) T6 ON T6.id = T2.id
                    WHERE T2.group_id IN (" . implode(",", array_keys($groups)) . ") AND T1.deadline>=NOW()
                    ORDER BY T1.deadline ASC LIMIT 5")->fetchAll();
         
            foreach($tasks as $task) {
                $taskObject  = new Task();
                $taskObject->message = new \App\Model\Entities\Message();
                $taskObject->message->text = $task->text;
                $taskObject->message->user = new \App\Model\Entities\User();
                $taskObject->message->user->id = $task->user_id;
                $taskObject->message->user->name = $task->user_name;
                $taskObject->message->user->surname = $task->user_surname;
                $taskObject->message->user->profileImage = \App\Model\Entities\User::createProfilePath($task->profile_image, $task->user_sex);
        
                $taskObject->commitCount = $task->commit_count;
                $taskObject->deadline = $task->deadline;
                $taskObject->title = $task->name;
                $taskObject->idMessage = $task->message_id;
                $taskObject->group = $groups[$task->group_id];
                $taskObject->timeLeft = $now->diff($task->deadline);
                $taskObject->taskMembers = $task->task_users;
                $taskObject->online = $task->online;
                $taskObject->id = $task->id;
                
                if(!empty($task->commit_id)) {
                    $taskObject->commit = new \App\Model\Entities\TaskCommit();
                    $taskObject->commit->idCommit = $task->commit_id;
                    $taskObject->commit->created = $task->commit_created;
                    $taskObject->commit->updated = $task->commit_updated;
                }
                $tasksArray[$task->id] = $taskObject;
            }
            
            return $tasksArray;
        } else {
            return array();
        }
    }
    
    public function getTask($idTask, $user) 
    {
        $now = new \DateTime();
        $task = $this->db->fetch("SELECT 
                        T1.id,
                        T1.name,
                        T1.message_id,
                        T1.deadline,
                        T2.id AS class_group_id,
                        T3.group_id,
                        T4.slug,
                        T5.task_users,
                        T6.id AS commit_id,
                        T6.created_when AS commit_created,
                        T6.updated_when AS commit_updated,
                        T6.comment AS commit_comment,
                        T3.text
                FROM task T1 
                LEFT JOIN classification_group T2 ON T1.id = T2.task_id
                LEFT JOIN message T3 ON T3.id=T1.message_id
                LEFT JOIN `group` T4 ON T3.group_id = T4.id
                LEFT JOIN ( SELECT COUNT(*) AS task_users, M.id FROM message M JOIN group_user G ON M.id=G.group_id GROUP BY M.id) T5 ON T5.id = T3.id
                LEFT JOIN task_commit T6 ON (T1.id=T6.task_id AND T6.user_id=?)
                WHERE T1.id = ?", $user->id, $idTask);
        $taskObject  = new Task();
        $taskObject->id = $task->id;
        $taskObject->deadline = $task->deadline;
        $taskObject->title = $task->name;
        $taskObject->idMessage = $task->message_id;
        $taskObject->timeLeft = $now->diff($task->deadline);
        $taskObject->idClassificationGroup = $task->class_group_id;
        $taskObject->message = new \App\Model\Entities\Message();
        $taskObject->message->group = new \App\Model\Entities\Group();
        $taskObject->message->group->id = $task->group_id;
        $taskObject->message->group->urlId = $task->slug;
        $taskObject->message->text = $task->text;
        $taskObject->taskMembers = $task->task_users;
        if(!empty($task->commit_id)) {
            $commit = new \App\Model\Entities\TaskCommit();
            $commit->idCommit = $task->commit_id;
            $commit->created = $task->commit_created;
            $commit->updated = $task->commit_updated;
            $commit->comment = $task->commit_comment;
            $taskObject->commit = $commit;
        }
        return $taskObject;
    }
    
    public function getCommit($idCommit) 
    {        
        $return = new TaskCommit();
        $commit = $this->database->query("SELECT T1.ID_COMMIT, T1.COMMENT, T2.ID_FILE,
                            T3.PATH,
                            T3.FILENAME
                        FROM task_commit T1
                        LEFT JOIN task_commit_attachment T2 ON T1.ID_COMMIT=T2.ID_COMMIT
                        LEFT JOIN file_list T3 ON T2.ID_FILE=T3.ID_FILE
                        WHERE T1.ID_COMMIT = ?", $idCommit)->fetchAll();
        foreach($commit as $attach) {
            $return->idCommit = $attach->ID_COMMIT;
            $return->comment = $attach->COMMENT;
            if(!empty($attach->ID_FILE)) {
                $return->files[] = (object)array('idFile' => $attach->ID_FILE, 'path' => 'https://cdn.lato.cz/' . $attach->PATH . '/' . $attach->FILENAME, 'filename' => $attach->FILENAME);
            }
        }
        return $return;
    }
    
    
    public function getCommitByUser($idTask, $idUser) 
    {        
        $return = new TaskCommit();
        $commit = $this->database->query("SELECT T1.id, T1.comment, T2.file_id,
                            T3.path,
                            T3.filename,
                            T1.created_when,
                            T1.updated_when,
                            T3.mime,
                            T3.extension
                        FROM task_commit T1
                        LEFT JOIN task_commit_attachment T2 ON T1.id=T2.commit_id
                        LEFT JOIN file_list T3 ON T2.file_id=T3.id
                        WHERE T1.task_id = ? AND T1.user_id = ?", $idTask, $idUser)->fetchAll();
        if(!$commit) {
            return null;
        }
        
        foreach($commit as $attach) {
            $return->idCommit = $attach->id;
            $return->comment = $attach->comment;
            $return->created = $attach->created_when;
            $return->updated = $attach->updated_when;
            if(!empty($attach->file_id)) {
                $return->files[] = (object)['idFile' => $attach->file_id, 'mime' => $attach->mime,'extension' => $attach->extension, 'path' => 'https://cdn.lato.cz/' . $attach->path . '/' . $attach->filename, 'filename' => $attach->filename];
            }
        }
        return $return;
    }
    
    public function isUserCommit($idCommit, \App\Model\Entities\User $user)
    {
        $id = $this->db->fetchSingle("SELECT id FROM task_commit WHERE id=? AND id=?", $idCommit, $user->id);
        return $idCommit == $id;
    }
    
    public function createTaskCommit(TaskCommit $taskCommit, $attachments)
    {
        $this->db->begin();
        if(empty($taskCommit->idCommit)) {
            $this->db->query("INSERT INTO task_commit", [
                'task_id' => $taskCommit->idTask,
                'user_id' => $taskCommit->user->id,
                'created_by' => $this->user->id,
                'comment' => $taskCommit->comment
            ]);
            $taskCommit->idCommit = $this->db->getInsertId();
        } else {
            $this->db->query("UPDATE task_commit SET", [
                'comment' => $taskCommit->comment
            ], "WHERE id=?", $taskCommit->idCommit);
        }
        
        if(!empty($attachments)) {
            foreach($attachments as $idAttach) {
                $this->addAttachment($idAttach, $taskCommit->idCommit);
            }
        }
        
        $this->db->commit();
        return $taskCommit->idCommit;
    }
    
    
    public function addAttachment($idFile, $idCommit = null)
    {
        if(!empty($idFile)) {
            $this->db->query("INSERT INTO task_commit_attachment", [
                'commit_id' => $idCommit,
                'file_id' => $idFile
            ]);
        }
    }
    
    public function removeAttachment($idFile)
    {
        if(!empty($idFile)) {     
            $this->db->query("DELETE FROM task_commit_attachment WHERE file_id=?", $idFile);
        }
    }
}
