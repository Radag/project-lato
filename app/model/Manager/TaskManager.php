<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use Nette;
use App\Model\Entities\Task;

/**
 * Description of TaskManager
 *
 * @author Radaq
 */
class TaskManager extends Nette\Object{
 
    
    /** @var Nette\Database\Context */
    private $database;


    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }
    
   
    public function createTask(Task $task)
    {
        $this->database->beginTransaction();
        $this->database->table('tasks')->insert(array(
                    'TITLE' => $task->title,
                    'ID_MESSAGE' => $task->idMessage
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
                    WHERE T2.ID_GROUP IN (" . implode(",", array_keys($groups)) . ") 
                    ORDER BY T1.DEADLINE ASC LIMIT 5")->fetchAll();
            foreach($tasks as $task) {
                $taskObject  = new Task();
                $taskObject->deadline = $task->DEADLINE;
                $taskObject->title = $task->NAME;
                $taskObject->group = $groups[$task->ID_GROUP];
                $taskObject->timeLeft = $now->diff($task->DEADLINE);
                $tasksArray[] = $taskObject;
            }
            return $tasksArray;
        } else {
            return array();
        }
    }
}