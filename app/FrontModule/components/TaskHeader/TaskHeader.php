<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\TaskHeader;

use App\Components\PreparedControl;
use App\Model\Manager\UserManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\TaskManager;
use App\Model\Manager\ClassificationManager;

/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class TaskHeader extends PreparedControl
{
    
    /**
     * @var UserManager $userManager
     */
    protected $userManager;
    
    /**
     * @var GroupManager $groupManager
     */
    protected $groupManager;
     
    
    /**
     * @var TaskManager $taskManager
     */
    protected $taskManager;
    
    /**
     * @var ClassificationManager $classificationManager
     */
    protected $classificationManager; 
    
    /**
     * @var \App\Model\Entities\Group $activeGroup
     */
    protected $activeGroup = null;
    
    /**
     * @var \App\Model\Entities\User $activeUser
     */
    protected $activeUser;
    
    /**
     * @var \App\Model\Entities\Task $task
     */
    protected $task;
    
    public function __construct(UserManager $userManager,
            GroupManager $groupManager,
            TaskManager $taskManager,
            ClassificationManager $classificationManager)
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
    
  
    public function render()
    {
        $this->template->activeUser = $this->presenter->activeUser;
        $this->template->task = $this->task;
        $this->template->setFile(__DIR__ . '/TaskHeader.latte');
        $this->template->render();
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
            $groupClassification = new ClassificationGroup();
            $groupClassification->name = $task->title;
            $groupClassification->group = $this->presenter->activeGroup;
            $groupClassification->task = $task;
            $idGroupClassification = $this->classificationManager->createGroupClassification($groupClassification);
        } else {
            $idGroupClassification = $task->idClassificationGroup;
        }
        $this->presenter->redirect(':Front:Group:users', array('do'=> 'usersList-classification' ,'id' => $task->message->group->id , 'usersList-idGroupClassification' => $idGroupClassification)); 
    }
    
    
    public function handleEditTaskCommit($idCommit)
    {
        $commit = $this->taskManager->getCommit($idCommit);
        $this->presenter['commitTaskForm']->setDefault($commit);
        $this->presenter->redrawControl('commitTaskForm');
    }
}
