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
}
