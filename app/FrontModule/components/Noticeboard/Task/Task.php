<?php

namespace App\FrontModule\Components\Noticeboard;

use App\Model\Manager\TaskManager;

class Task extends \App\Components\BaseComponent
{
    /** @var TaskManager */
    protected $taskManager;
 
    protected $task = null;
      
    public function __construct(
        TaskManager $taskManager
    )
    {
        $this->taskManager = $taskManager; 
    }
    
    public function render() 
    {
        $this->template->task = $this->task;
        parent::render();
    }
	
	public function setTask($task)
    {
        $this->task = $task;
    }
	
    public function handleEditTaskCommit()
    {
        $this->parent['commitTaskForm']->setTask($this->task);
        $this->parent['commitTaskForm']->redrawControl();
    }
    
}
