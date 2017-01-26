<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use Nette;
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
        $this->database->beginTransaction();
        $idTask = $this->database->query("SELECT ID_TASK FROM tasks WHERE ID_MESSAGE=?", $task->idMessage)->fetchField(); 
        if($idTask) {
            $data = array(
                'NAME' => $task->title,
                'DEADLINE' => $task->deadline,
                'ONLINE' => $task->online
            );
            $this->database->query("UPDATE tasks SET ? WHERE ID_TASK=?", $data, $idTask);    
        } else {
            $this->database->table('tasks')->insert(array(
                    'NAME' => $task->title,
                    'ID_MESSAGE' => $task->idMessage,
                    'DEADLINE' => $task->deadline,
                    'ONLINE' => $task->online,
            )); 
        }
        
               
        $this->database->commit();
    }
    
    
    public function getClosestTask($groups) 
    {
        if(!empty($groups)) {
            $now = new \DateTime();
            
            $tasksArray = array();
            $tasks = $this->database->query("SELECT 
                            T1.ID_TASK,
                            T1.NAME,
                            T1.ID_MESSAGE,
                            T1.DEADLINE,
                            T2.ID_GROUP,
                            T3.ID_USER,
                            T3.NAME AS USER_NAME,
                            T3.SURNAME AS USER_SURNAME,
                            T3.PROFILE_PATH,
                            T3.PROFILE_FILENAME,
                            T3.SEX,
                            T4.COMMIT_COUNT,
                            T5.ID_COMMIT,
                            T5.CREATED_WHEN AS COMMIT_CREATED,
                            T5.UPDATED_WHEN AS COMMIT_UPDATED
                    FROM tasks T1 
                    JOIN message T2 ON (T1.ID_MESSAGE = T2.ID_MESSAGE AND T2.DELETED=0)
                    JOIN vw_user_detail T3 ON (T2.ID_USER=T3.ID_USER)
                    LEFT JOIN (SELECT COUNT(ID_COMMIT) AS COMMIT_COUNT, ID_TASK FROM task_commit GROUP BY ID_TASK) T4 ON T4.ID_TASK=T1.ID_TASK
                    LEFT JOIN task_commit T5 ON T1.ID_TASK=T5.ID_TASK
                    WHERE T2.ID_GROUP IN (" . implode(",", array_keys($groups)) . ") AND T1.DEADLINE>=NOW()
                    ORDER BY T1.DEADLINE ASC LIMIT 5")->fetchAll();
            foreach($tasks as $task) {
                $taskObject  = new Task();
                $taskObject->message = new \App\Model\Entities\Message();
                $taskObject->message->user = new \App\Model\Entities\User();
                $taskObject->message->user->id = $task->ID_USER;
                $taskObject->message->user->name = $task->USER_NAME;
                $taskObject->message->user->surname = $task->USER_SURNAME;
                $taskObject->message->user->profileImage = \App\Model\Entities\User::createProfilePath($task->PROFILE_PATH, $task->PROFILE_FILENAME, $task->SEX);
        
                $taskObject->commitCount = $task->COMMIT_COUNT;
                $taskObject->deadline = $task->DEADLINE;
                $taskObject->title = $task->NAME;
                $taskObject->idMessage = $task->ID_MESSAGE;
                $taskObject->group = $groups[$task->ID_GROUP];
                $taskObject->timeLeft = $now->diff($task->DEADLINE);
                $taskObject->idTask = $task->ID_TASK;
                
                if(!empty($task->ID_COMMIT)) {
                    $taskObject->commit = new \App\Model\Entities\TaskCommit();
                    $taskObject->commit->idCommit = $task->ID_COMMIT;
                    $taskObject->commit->created = $task->COMMIT_CREATED;
                    $taskObject->commit->updated = $task->COMMIT_UPDATED;
                }
                $tasksArray[$task->ID_TASK] = $taskObject;
            }
            
            return $tasksArray;
        } else {
            return array();
        }
    }
    
    public function getTask($idTask) 
    {
        $now = new \DateTime();
        $task = $this->database->query("SELECT 
                        T1.ID_TASK,
                        T1.NAME,
                        T1.ID_MESSAGE,
                        T1.DEADLINE,
                        T2.ID_CLASSIFICATION_GROUP,
                        T3.ID_GROUP
                FROM tasks T1 
                LEFT JOIN classification_group T2 ON T1.ID_TASK = T2.ID_TASK
                LEFT JOIN message T3 ON T3.ID_MESSAGE=T1.ID_MESSAGE
                WHERE T1.ID_TASK = ?", $idTask)->fetch();
        $taskObject  = new Task();
        $taskObject->idTask = $task->ID_TASK;
        $taskObject->deadline = $task->DEADLINE;
        $taskObject->title = $task->NAME;
        $taskObject->idMessage = $task->ID_MESSAGE;
        $taskObject->timeLeft = $now->diff($task->DEADLINE);
        $taskObject->idClassificationGroup = $task->ID_CLASSIFICATION_GROUP;
        $taskObject->message = new \App\Model\Entities\Message();
        $taskObject->message->group = new \App\Model\Entities\Group();
        $taskObject->message->group->id = $task->ID_GROUP;
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
        $commit = $this->database->query("SELECT T1.ID_COMMIT, T1.COMMENT, T2.ID_FILE,
                            T3.PATH,
                            T3.FILENAME
                        FROM task_commit T1
                        LEFT JOIN task_commit_attachment T2 ON T1.ID_COMMIT=T2.ID_COMMIT
                        LEFT JOIN file_list T3 ON T2.ID_FILE=T3.ID_FILE
                        WHERE T1.ID_TASK = ? AND T1.ID_USER = ?", $idTask, $idUser)->fetchAll();
        if(!$commit) {
            return null;
        }
        
        foreach($commit as $attach) {
            $return->idCommit = $attach->ID_COMMIT;
            $return->comment = $attach->COMMENT;
            if(!empty($attach->ID_FILE)) {
                $return->files[] = (object)array('idFile' => $attach->ID_FILE, 'path' => 'https://cdn.lato.cz/' . $attach->PATH . '/' . $attach->FILENAME, 'filename' => $attach->FILENAME);
            }
        }
        return $return;
    }
    
    public function isUserCommit($idCommit, \App\Model\Entities\User $user)
    {
        $id = $this->database->query("SELECT ID_COMMIT FROM task_commit WHERE ID_COMMIT=? AND ID_USER=?", $idCommit, $user->id)->fetchField();
        return $idCommit == $id;
    }
    
    public function createTaskCommit(TaskCommit $taskCommit, $attachments)
    {
        $this->database->beginTransaction();
        if(empty($taskCommit->idCommit)) {
            $this->database->table('task_commit')->insert(array(
                    'ID_TASK' => $taskCommit->idTask,
                    'ID_USER' => $taskCommit->user->id,
                    'CREATED_BY' => $this->user->id,
                    'COMMENT' => $taskCommit->comment
            ));
            $taskCommit->idCommit = $this->database->query("SELECT MAX(ID_COMMIT) FROM task_commit")->fetchField();
        } else {
            $data = array('COMMENT' => $taskCommit->comment, 'UPDATED_WHEN' => new \DateTime());
            $this->database->query("UPDATE task_commit SET ? WHERE ID_COMMIT=?", $data, $taskCommit->idCommit);
        }
        
        if(!empty($attachments)) {
            foreach($attachments as $idAttach) {
                $this->addAttachment($idAttach, $taskCommit->idCommit);
            }
        }
        
        $this->database->commit();
        return $taskCommit->idCommit;
    }
    
    
    public function addAttachment($idFile, $idCommit = null)
    {
        if(!empty($idFile)) {
            $this->database->table('task_commit_attachment')->insert(array(
                'ID_COMMIT' => $idCommit,
                'ID_FILE' => $idFile
            ));
        }
    }
    
    public function removeAttachment($idFile)
    {
        if(!empty($idFile)) {     
            $this->database->query("DELETE FROM task_commit_attachment WHERE ID_FILE=?", $idFile);
        }
    }
}
