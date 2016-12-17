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
        $this->database->table('tasks')->insert(array(
                    'NAME' => $task->title,
                    'ID_MESSAGE' => $task->idMessage,
                    'DEADLINE' => $task->deadline,
                    'ONLINE' => $task->online,
            ));        
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
                            T2.ID_GROUP
                    FROM tasks T1 JOIN message T2 ON T1.ID_MESSAGE = T2.ID_MESSAGE
                    WHERE T2.ID_GROUP IN (" . implode(",", array_keys($groups)) . ") AND T1.DEADLINE>=NOW()
                    ORDER BY T1.DEADLINE ASC LIMIT 5")->fetchAll();
            foreach($tasks as $task) {
                $taskObject  = new Task();
                $taskObject->deadline = $task->DEADLINE;
                $taskObject->title = $task->NAME;
                $taskObject->idMessage = $task->ID_MESSAGE;
                $taskObject->group = $groups[$task->ID_GROUP];
                $taskObject->timeLeft = $now->diff($task->DEADLINE);
                $tasksArray[] = $taskObject;
            }
            return $tasksArray;
        } else {
            return array();
        }
    }
    
    public function createTaskCommit(TaskCommit $taskCommit, $attachments)
    {
        $this->database->beginTransaction();
        $this->database->table('task_commit')->insert(array(
                    'ID_TASK' => $taskCommit->idTask,
                    'ID_USER' => $taskCommit->user->id,
                    'CREATED_BY' => $this->user->id,
                    'COMMENT' => $taskCommit->comment
            ));
        
        $idCommit = $this->database->query("SELECT MAX(ID_COMMIT) FROM task_commit")->fetchField();
        foreach($attachments as $idAttach) {
            $this->addAttachment($idAttach, $idCommit);
        }
        $this->database->commit();
        return $idCommit;
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
}
