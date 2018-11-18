<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream;

use App\Model\Manager\MessageManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\TaskManager;
use App\FrontModule\Components\Stream\ICommentForm;
use App\FrontModule\Components\TaskHeader\ITaskHeader;
use App\FrontModule\Components\Stream\ICommitTaskForm;

class MessagesColumn extends \App\Components\BaseComponent
{
    
    /** @var  MessageManager @inject */
    protected $messageManager;
    
    /** @var  GroupManager @inject */
    protected $groupManager;    
    
    /** @var  ITaskHeader @inject */
    protected $taskHeaderFactory;

    /** @var  ICommentForm @inject */
    protected $commentForm;
    
    /** @var  TaskManager @inject */
    protected $taskManager;
    
    /** @var ICommitTaskForm */
    protected $commitTaskForm;  
      
    protected $filter = 'all';
    
    protected $singleMode = false;
    
    protected $messages = [];
    
    protected $comments = [];
    
    public function __construct(
        MessageManager $messageManager,
        GroupManager $groupManager,
        ICommentForm $commentForm,
        ITaskHeader $taskHeaderFactory,
        TaskManager $taskManager,            
        ICommitTaskForm $commitTaskForm
    )
    {
        $this->messageManager = $messageManager;
        $this->groupManager = $groupManager;
        $this->commentForm = $commentForm;
        $this->taskHeaderFactory = $taskHeaderFactory;
        $this->taskManager = $taskManager;
        $this->commitTaskForm = $commitTaskForm;
    }    
        
    public function render() {
        if($this->singleMode === false) {
            $this->template->singleMode = false;
            $data = $this->messageManager->getMessages($this->presenter->activeGroup, $this->presenter->activeUser, $this->filter);
            $this->messages = $data['messages'];
            $this->comments = $data['comments'];
        } else {
            $this->template->singleMode = true;
            $message = $this->messageManager->getMessage($this->singleMode, $this->presenter->activeUser, $this->presenter->activeGroup);
            $this->comments[$this->singleMode] = $this->messageManager->getMessageComments($this->singleMode);
            $this->messages = [$message->id => $message];
            $this->template->singleMessage = $message;
        }
        $this->template->singleMode = $this->singleMode;
        $this->template->messages = $this->messages;
        $this->template->groupPermission = $this->presenter->groupPermission;
        $this->template->activeGroup = $this->presenter->activeGroup;
        $this->template->isOwner = $this->presenter->activeGroup->relation === 'owner' ? true : false;
        parent::render();
    } 
    
    public function createComponentTaskHeader()
    {
        return new \Nette\Application\UI\Multiplier(function ($idTask) {
            $taskHeader = $this->taskHeaderFactory->create();
            if(!empty($this->messages)) {
                foreach($this->messages as $message) {
                    if(isset($message->task) && $message->task->id == $idTask) {
                        $task = $message->task;
                        $task->message = $message;
                    }
                }
            } else {
                $task = $this->taskManager->getTask($idTask, $this->presenter->activeUser);
            }
            
            $taskHeader->setTask($task, $this->singleMode);
            $taskHeader->setCommitTaskForm($this['commitTaskForm']);
            return $taskHeader;
        });
    }

    public function createComponentCommentForm()
    {
        return new \Nette\Application\UI\Multiplier(function ($idMessage) {
            $commentForm = $this->commentForm->create();
            if(isset($this->messages[$idMessage])) {
                $commentForm->setMessage($this->messages[$idMessage]);
            } else {
                $commentForm->setMessage($this->messageManager->getMessage($idMessage, $this->presenter->activeUser, $this->presenter->activeGroup));
            }
            if(isset($this->comments[$idMessage])) {
                $commentForm->setComments($this->comments[$idMessage]);
            }
            return $commentForm;
        });
    }
    
    protected function createComponentCommitTaskForm()
    {
        return $this->commitTaskForm->create();    
    }
    
    public function handleEditMessage($idMessage)
    {
        $message = $this->messageManager->getMessage($idMessage, $this->presenter->activeUser, $this->presenter->activeGroup);
        $this->parent['messageForm']->setDefaults($message);
        $this->parent->redrawControl('messageForm');
    }

    public function handleSetTaskClassification($idTask)
    {
        $task = $this->taskManager->getTask($idTask);
        if(empty($task->idClassificationGroup)) {
            $groupClassification = new ClassificationGroup();
            $groupClassification->name = $task->title;
            $groupClassification->group = $this->activeGroup;
            $groupClassification->task = $task;
            $idGroupClassification = $this->classificationManager->createGroupClassification($groupClassification);
        } else {
            $idGroupClassification = $task->idClassificationGroup;
        }
        $this->presenter->redirect(':Front:Group:users', array('do'=> 'usersList-classification' , 'usersList-idGroupClassification' => $idGroupClassification)); 
    }
    
    public function handleDeleteMessage($idMessage) 
    {   
        $this->messageManager->deleteMessage($idMessage, true);
        $this->presenter->flashMessage('Zpráva byla smazána.');
        $this->redrawControl();
    }

    public function handleRenewMessage($idMessage) 
    {   
        $this->messageManager->deleteMessage($idMessage, false);
        $this->presenter->flashMessage('Zpráva byla obnovena.');
        $this->redrawControl();
    }
    
    public function handleTopMessage($idMessage, $enable = true) 
    {
        $this->messageManager->topMessage($idMessage, $enable);
        if($enable) {
            $this->presenter->flashMessage('Zpráva byla posunuta nahoru.');
        } else {
            $this->presenter->flashMessage('Zrušeno topování zprávy.'); 
        }
        $this->redrawControl();
    }
    
    public function handleEditTaskCommit($idCommit)
    {
        $commit = $this->taskManager->getCommit($idCommit);
        $this['commitTaskForm']->setDefault($commit);
        $this->redrawControl('commitTaskForm');
    }
    
    public function setFilter($filter)
    {
        $this->filter = $filter;
        $this->redrawControl();
    }
    
    public function setSingleMode($idMessage)
    {
        $this->singleMode = $idMessage;
    }
}
