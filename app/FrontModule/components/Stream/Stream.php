<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream;

use App\Components\PreparedControl;
use App\Model\Manager\UserManager;
use App\Model\Manager\MessageManager;
use App\Model\Manager\FileManager;
use App\Model\Manager\MaterialManager;
use App\Model\Manager\TaskManager;
use App\FrontModule\Components\Stream\MessageForm\MessageForm;
use App\FrontModule\Components\Stream\CommentForm\CommentForm;
use App\FrontModule\Components\Stream\MessageForm\NoticeForm\INoticeFormFactory;
use App\FrontModule\Components\Stream\MessageForm\TaskForm\ITaskFormFactory;
use App\FrontModule\Components\Stream\MessageForm\HomeworkForm\IHomeworkFormFactory;
use App\FrontModule\Components\Stream\MessageForm\MaterialsForm\IMaterialsFormFactory;
use App\Model\Manager\ClassificationManager;
use App\Model\Manager\GroupManager;
use App\Model\Entities\ClassificationGroup;
use App\FrontModule\Components\TaskHeader\ITaskHeader;

/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class Stream extends PreparedControl
{
    
    /**
     * @var UserManager $userManager
     */
    protected $userManager;
    
    /**
     * @var MessageManager $messageManager
     */
    protected $messageManager;
    
    /**
     * @var MaterialManager $materialManager
     */
    protected $materialManager;
    
    /**
     * @var FileManager
     */
    protected $fileManager;
    
    /**
     * @var GroupManager
     */
    protected $groupManager;
    
    /**
     * @var TaskManager
     */
    protected $taskManager;    
    
    /**
     * @var ClassificationManager
     */
    protected $classificationManager;
    
    /**
     * @var CommentForm; 
     */
    protected $commentForm = null;
    
    /**
     * @var \App\Model\Entities\Group $activeGroup
     */
    protected $activeGroup;
    
    /**
     * @var \App\Model\Entities\User $activeUser
     */
    protected $activeUser;
    
    protected $messageType = 1;
    
    /** @var  INoticeFormFactory @inject */
    protected $noticeFormFactory;
    
    /** @var  ITaskFormFactory @inject */
    protected $taskFormFactory;
    
    /** @var  IHomeworkFormFactory @inject */
    protected $homeworkFormFactory;
    
    /** @var  IMaterialsFormFactory @inject */
    protected $materialsFormFactory;
    
    /** @var  ITaskHeader @inject */
    protected $taskHeaderFactory;
    
    protected $showDeleted = false;

    protected $streamPermission = array();
    
    protected $singleMode = null;
    
    protected $messages = array();
    
    protected $filter = 'all';

    public function __construct(
            UserManager $userManager, 
            MessageManager $messageManager, 
            FileManager $fileManager,
            MaterialManager $materialManager,
            ClassificationManager $classificationManager,
            INoticeFormFactory $noticeFormFactory,
            ITaskFormFactory $taskFormFactory,
            IHomeworkFormFactory $homeworkFormFactory,
            IMaterialsFormFactory $materialsFormFactory,
            GroupManager $groupManager,
            TaskManager $taskManager,
            ITaskHeader $taskHeaderFactory
            )
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->fileManager = $fileManager;
        $this->noticeFormFactory = $noticeFormFactory;
        $this->taskFormFactory = $taskFormFactory;
        $this->homeworkFormFactory = $homeworkFormFactory;
        $this->materialsFormFactory = $materialsFormFactory;
        $this->groupManager = $groupManager;
        $this->taskManager = $taskManager;
        $this->classificationManager = $classificationManager;
        $this->taskHeaderFactory = $taskHeaderFactory;
    }
    
    public function setUser(\App\Model\Entities\User $user)
    {
        $this->activeUser = $user;
    }
    
    public function setStreamPermission($permission)
    {
        $this->streamPermission = $permission;
    }
    
    public function setGroup(\App\Model\Entities\Group $group)
    {
        $this->activeGroup = $group;
    }
    
    public function getActiveGroup()
    {
        return $this->activeGroup;
    }
    
    public function showDeleted($deleted)
    {
        $this->showDeleted = $deleted;
    }
    
    public function render()
    {
        $template = $this->getTemplate();
        if($this->singleMode === null) {
            $this->template->singleMode = false;
            $this->messages = $this->messageManager->getMessages($this->activeGroup, $this->activeUser, $this->showDeleted, $this->filter);    
        } else {
            $this->template->singleMode = true;
            $message = $this->messageManager->getMessage($this->singleMode, $this->activeUser);
            $this->messages = array($message);
        }
        
        $template->activeUser = $this->activeUser;  
        $template->activeGroup = $this->activeGroup;  
        $template->isOwner = ($this->activeUser->id === $this->activeGroup->owner->id) ? true : false;
        $template->messages = $this->messages;
        $template->streamPermission = $this->streamPermission;
        $template->userGroups = $this->groupManager->getGroups($this->activeUser);
        $template->setFile(__DIR__ . '/Stream.latte');
        $template->render();
    }
    
    public function createComponentTaskHeader()
    {
        return new \Nette\Application\UI\Multiplier(function ($idTask) {
            $taskHeader = $this->taskHeaderFactory->create();
            if(!empty($this->messages)) {
                foreach($this->messages as $message) {
                    if(isset($message->task) && $message->task->idTask == $idTask) {
                        $task = $message->task;
                        $task->message = $message;
                    }
                }
            } else {
                $task = $this->taskManager->getTask($idTask);
            }
            
            $taskHeader->setTask($task);
            return $taskHeader;
        });
    }
 
    public function handleSetMessageType($messageType)
    {
        $this->messageType = $messageType;
        $this->redrawControl('messageForm');
    }
    
    public function handleEditMessage($idMessage)
    {
        $message = $this->messageManager->getMessage($idMessage, $this->activeUser);
        $this->messageType = $message->idType;
        $this['messageForm']->setDefaults($message);
        $this->redrawControl('messageForm');
    }
    
    public function createComponentMessageForm()
    {
        $type = $this->presenter->getRequest()->getPost('messageType');
        if(isset($type) && $type !== NULL) {
            $this->messageType = $this->presenter->getRequest()->getPost('messageType');
        }
       
        switch ($this->messageType) {
            case MessageForm::TYPE_NOTICE :
                $form = $this->noticeFormFactory->create();
                break;
            case MessageForm::TYPE_MATERIALS :
                $form = $this->materialsFormFactory->create();
                break;
            case MessageForm::TYPE_TASK :
                $form = $this->taskFormFactory->create();
                break;
            case MessageForm::TYPE_HOMEWORK :
                $form = $this->homeworkFormFactory->create();
                break;
        }
        $form->setActiveUser($this->activeUser);
        $form->setStream($this);
        
        return $form;
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
    
    
    public function handleEditTaskCommit($idCommit)
    {
        $commit = $this->taskManager->getCommit($idCommit);
        $this['commitTaskForm']->setDefault($commit);
        $this->redrawControl('commitTaskForm');
    }
    
    public function createComponentCommentForm()
    {
        return new \Nette\Application\UI\Multiplier(function ($idMessage) {
            $commentForm = new CommentForm($this->messageManager, $this->userManager, $this->activeUser);
            $commentForm->setMessage($idMessage);
            return $commentForm;
        });
    }
    
    public function handleDeleteMessage($idMessage) 
    {   
        $message = $this->messageManager->getMessage($idMessage);
        if($message->user->id === $this->activeUser->id || $this->activeUser->id === $this->activeGroup->owner->id) {
            $this->messageManager->deleteMessage($message);
            $this->presenter->flashMessage('Zpráva byla smazána.');
            $this->redrawControl();
        }
    }
    
    public function handleShareMessage($idMessage) 
    {   
        $message = $this->messageManager->getMessage($idMessage);
        $idGroup = $this->presenter->getRequest()->getPost('group');
        //\Tracy\Debugger::barDump($idGroup);
        $group = $this->groupManager->getGroup(22);
        $this->messageManager->cloneMessage($message, $group);
        $this->presenter->flashMessage('Zpráva byla sdílena.');
        $this->redirect('this');
    }
    
    public function handleTopMessage($idMessage, $enable = true) 
    {
        $message = $this->messageManager->getMessage($idMessage);
        if($this->activeGroup->owner->id === $this->activeUser->id) {
            $this->messageManager->topMessage($message, $enable);
            if($enable) {
                $this->presenter->flashMessage('Zpráva byla posunuta nahoru.');
            } else {
                $this->presenter->flashMessage('Zrušeno topování zprávy.'); 
            }
            $this->redrawControl();
        }
    }
    
    public function handleFollowMessage($idMessage, $enable = true) 
    {
        $message = $this->messageManager->getMessage($idMessage);
        $this->messageManager->followMessage($message, $this->activeUser, $enable);
        if($enable) {
            $this->presenter->flashMessage('Zpráva byla zařazena do sledovaných');
        } else {
            $this->presenter->flashMessage('Zpráva byla vyřazena ze sledovaných');
        }
        $this->redrawControl();
    }
    
    public function setSingleMode($idMessage)
    {
        $this->singleMode = $idMessage;
    }
    
    public function setFilter($filter)
    {
        $this->filter = $filter;
    }
}
