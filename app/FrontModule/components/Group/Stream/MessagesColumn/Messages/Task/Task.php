<?php

namespace App\FrontModule\Components\Stream\Messages;

use App\Model\Manager\MessageManager;
use App\Model\Manager\TaskManager;
use App\FrontModule\Components\Stream\ICommentForm;

class Task extends Base
{
    /** @var MessageManager */
    protected $messageManager;
    
    /** @var TaskManager */
    protected $taskManager;
    
    /** @var ICommentForm */
    protected $commentForm;
 
    protected $message = null;
    
    protected $comments = [];
      
    public function __construct(
        MessageManager $messageManager, 
        TaskManager $taskManager,
        ICommentForm $commentForm   
    )
    {
        $this->messageManager = $messageManager;
        $this->taskManager = $taskManager; 
        $this->commentForm = $commentForm; 
    }
    
    public function render() 
    {
        $task = $this->getMessage()->task;
        $task->message = $this->getMessage();
        $this->template->task = $task;
        parent::render();
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

    public function handleEditTaskCommit($idCommit)
    {
        $this->presenter['stream']['messagesColumn']['commitTaskForm']->setTask($this->getMessage()->task);
        $this->presenter['stream']['messagesColumn']->redrawControl('commitTaskForm');
    }
    
}
