<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\TaskHeader;

use App\Model\Manager\UserManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\TaskManager;
use App\Model\Manager\ClassificationManager;
use App\FrontModule\Components\Stream\ICommitTaskFormFactory;

/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class TaskHeader extends \App\Components\BaseComponent
{
    
    /** @var UserManager */
    protected $userManager;
    
    /** @var GroupManager  */
    protected $groupManager;  
    
    /** @var TaskManager */
    protected $taskManager;
    
    /** @var ClassificationManager */
    protected $classificationManager; 
         
    /** @var \App\Model\Entities\Task */
    protected $task;
    
    protected $commitTaskForm;
    
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
    
    public function setTask($task)
    {
        $this->task = $task;
    }
    
    public function setCommitTaskForm($form)
    {
        $this->commitTaskForm = $form;
    }
    
    public function render()
    {
        if(isset($this->presenter->activeGroup)) {
             $this->template->isOwner = $this->presenter->activeGroup->relation === 'owner' ? true : false;
        } else {
            $this->template->isOwner = true;
        }
        
        $this->template->task = $this->task;
        parent::render();
    }
    
    public function handleSetTaskCommit($idTask)
    {
        $this->presenter['commitTaskForm']->setTaskId($idTask);
        $this->presenter->redrawControl('commitTaskForm');
    }
    
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
        $this->presenter->redirect(':Front:Group:users', array('do'=> 'usersList-classification' ,'id' => $task->message->group->urlId , 'usersList-idGroupClassification' => $idGroupClassification)); 
    }
    
    public function handleEditTaskCommit()
    {
        $this->commitTaskForm->setTask($this->task);
        //\Tracy\Debugger::barDump($this->task);
        //$commit = $this->taskManager->getCommit($idCommit);
        //$this->presenter['commitTaskForm']->setDefault($commit);
        //$this->presenter->redrawControl('commitTaskForm');
    }
}
