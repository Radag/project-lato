<?php

namespace App\FrontModule\Components\TaskHeader;

use App\Model\Manager\UserManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\TaskManager;
use App\Model\Manager\ClassificationManager;
use App\FrontModule\Components\Stream\CommitTaskForm;

class TaskHeader extends \App\Components\BaseComponent
{
    /** @var UserManager */
    public $userManager;
    
    /** @var GroupManager  */
    public $groupManager;  
    
    /** @var TaskManager */
    public $taskManager;
    
    /** @var ClassificationManager */
    public $classificationManager; 
         
    /** @var \App\Model\Entities\Task */
    public $task;
    
     /** @var CommitTaskForm */
    public $commitTaskForm;
    
    public $mode = 'stream';
    
    public function __construct(
        UserManager $userManager,
        GroupManager $groupManager,
        TaskManager $taskManager,
        ClassificationManager $classificationManager
    )
    {
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->taskManager = $taskManager;
        $this->classificationManager = $classificationManager;
    }
    
    public function setTask(\App\Model\Entities\Task $task, $singleMode = false)
    {
        $this->task = $task;
        if($singleMode) {
            $this->mode = 'single';
        }
    }
    
    public function setCommitTaskForm($form)
    {
        $this->commitTaskForm = $form;
    }
    
    public function render()
    {
        $this->template->task = $this->task;
        if($this->mode === 'stream') {
            $this->setTemplateName('TaskHeaderStream');
        } else {
            $this->setTemplateName('TaskHeaderSingle');
        }
        parent::render();
    }
    
    public function handleSetTaskCommit($idTask)
    {
        $this->presenter['commitTaskForm']->setTaskId($idTask);
        $this->presenter->redrawControl('commitTaskForm');
    }
    
    /*
    public function handleSetTaskClassification($idTask)
    {
        $task = $this->taskManager->getTask($idTask);
        if(empty($task->idClassificationGroup)) {
            $groupClassification = new \App\Model\Entities\ClassificationGroup();
            $groupClassification->name = $task->title;
            $groupClassification->group = $task->message->group;
            $groupClassification->task = $task;
            $idGroupClassification = $this->classificationManager->createGroupClassification($groupClassification);
        } else {
            $idGroupClassification = $task->idClassificationGroup;
        }
        $this->presenter->redirect('Group:usersClassification', ['id' => $task->message->group->slug, 'classificationGroupId' => $idGroupClassification]); 
    }
     * 
     */
    
    public function handleEditTaskCommit()
    {
        $this->commitTaskForm->setTask($this->task);
        //\Tracy\Debugger::barDump($this->task);
        //$commit = $this->taskManager->getCommit($idCommit);
        //$this->presenter['commitTaskForm']->setDefault($commit);
        //$this->presenter->redrawControl('commitTaskForm');
    }
}
