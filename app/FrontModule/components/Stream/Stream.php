<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream;

use \Nette\Application\UI\Control;
use App\Model\Manager\UserManager;
use App\Model\Manager\MessageManager;
use App\Model\Manager\FileManager;
use App\Model\Manager\MaterialManager;
use App\FrontModule\Components\Stream\MessageForm\MessageForm;
use App\FrontModule\Components\Stream\CommentForm\CommentForm;
use App\FrontModule\Components\Stream\MessageForm\NoticeForm\INoticeFormFactory;
use App\FrontModule\Components\Stream\MessageForm\TaskForm\ITaskFormFactory;
use App\FrontModule\Components\Stream\MessageForm\HomeworkForm\IHomeworkFormFactory;
use App\FrontModule\Components\Stream\MessageForm\MaterialsForm\IMaterialsFormFactory;


/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class Stream extends Control
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
    
    
    protected $streamPermission = array();
    
    public function __construct(
            UserManager $userManager, 
            MessageManager $messageManager, 
            FileManager $fileManager,
            MaterialManager $materialManager,
            INoticeFormFactory $noticeFormFactory,
            ITaskFormFactory $taskFormFactory,
            IHomeworkFormFactory $homeworkFormFactory,
            IMaterialsFormFactory $materialsFormFactory
            )
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->fileManager = $fileManager;
        $this->noticeFormFactory = $noticeFormFactory;
        $this->taskFormFactory = $taskFormFactory;
        $this->homeworkFormFactory = $homeworkFormFactory;
        $this->materialsFormFactory = $materialsFormFactory;
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
    
    
    public function render()
    {
        $template = $this->template;
        $messages = $this->messageManager->getMessages($this->activeGroup, $this->activeUser);
        $template->activeUser = $this->activeUser;  
        $template->isOwner = ($this->activeUser->id === $this->activeGroup->owner->id) ? true : false;
        $template->messages = $messages;
        $template->streamPermission = $this->streamPermission;
        $template->setFile(__DIR__ . '/Stream.latte');
        $template->render();
    }
    
 
    public function handleSetMessageType($messageType)
    {
        $this->messageType = $messageType;
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
            $this->presenter->redirect('this');
        }
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
            $this->presenter->redirect('this');
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
        $this->presenter->redirect('this');
    }
}
