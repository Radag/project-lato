<?php

namespace App\FrontModule\Presenters;

use App\Model\Manager\UserManager;
use App\FrontModule\Components\Stream\Stream;
use App\Model\Manager\MessageManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\PrivateMessageManager;
use App\Model\Manager\NotificationManager;
use App\Model\Manager\FileManager;
use App\FrontModule\Components\Stream\IStreamFactory;
use App\FrontModule\Components\GroupSettingsForm\IGroupSettingsFormFactory;
use App\FrontModule\Components\Stream\CommentForm\CommentForm;
use App\Model\Manager\TaskManager;
use App\Model\Manager\ClassificationManager;
use App\FrontModule\Components\Group\IUsersListFactory;
use App\Model\Entities\Group;
use App\FrontModule\Components\Stream\ICommitTaskFormFactory;

class GroupPresenter extends BasePresenter
{    
    /**
     *
     * @var UserManager $userManager
     */
    protected $userManager;
    protected $messageManager;
    protected $groupManager;
    protected $privateMessageManager;
    protected $notificationManager;
    protected $fileManager;
    protected $taskManager;
    protected $classificationManager;
    
    /** @var \App\Model\Entities\Group */
    protected $activeGroup = null;
    
    protected $groupSettings = null;
    
    /** @var  IStreamFactory  */
    protected $streamFactory;
    
    /** @var  IUsersListFactory  */
    protected $usersListFactory;
    
    /** @var  ICommitTaskFormFactory */
    public $commitTaskFormFactory;
    
    protected $groupPermission = array(
        'archive' => false,
        'leave' => false,
        'settings' => false,
        'addMessages' => false,
        'addCommets' => false,
        'removeAllMessages' => false,
        'removeOwnMessages' => false,
        'editAllMessages' => false,
        'editOwnMessages' => false,
        'removeAllComments' => false,
        'removeOwnComments' => false,
        'topAllMessages' => false,
        'topOwnMessages' => false,
        'removeMembers' => false,
        'showStudentsList' => false,
        'editClassification' => false,
        'showDeleted' => false
    );
    
    /** @persistent */
    public $id;

    public $showDeleted = false;
    
    public function __construct(UserManager $userManager, 
            MessageManager $messageManager, 
            GroupManager $groupManager,
            PrivateMessageManager $privateMessageManager,
            NotificationManager $notificationManager,
            FileManager $fileManager,
            IStreamFactory $streamFactory,
            TaskManager $taskManager,
            IGroupSettingsFormFactory $groupSettings,
            ClassificationManager $classificationManager,
            IUsersListFactory $userListFactory,
            ICommitTaskFormFactory $commitTaskFormFactory
            )
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->groupManager = $groupManager;
        $this->privateMessageManager = $privateMessageManager;
        $this->notificationManager = $notificationManager;
        $this->fileManager = $fileManager;
        $this->taskManager = $taskManager;
        $this->streamFactory = $streamFactory;
        $this->groupSettings = $groupSettings;
        $this->classificationManager = $classificationManager;
        $this->usersListFactory = $userListFactory;
        $this->commitTaskFormFactory = $commitTaskFormFactory;
    }
    
    protected function startup()
    {
        parent::startup();
        $id = $this->getParameter('id');
        if(isset($id)) {
            $this->activeGroup = $this->groupManager->getGroup($id);
        } else {
            $this->redirect(':Front:Homepage:noticeboard');
        }
        if(!$this->groupManager->isUserInGroup($this->activeUser->id, $this->activeGroup->id)){
            $this->redirect(':Front:Homepage:noticeboard');
        }
        $this->setPermission();
        $this['topPanel']->setActiveGroup($this->activeGroup);
        $this['topPanel']->addToMenu((object)array('name' => 'stream', 'link' => $this->link('default'), 'active' => $this->isLinkCurrent('default')));
        if($this->groupPermission['showStudentsList']) {
            $this['topPanel']->addToMenu((object)array('name' => 'studenti', 'link' => $this->link('users'), 'active' => $this->isLinkCurrent('users')));
        } else {
            $this['topPanel']->addToMenu((object)array('name' => 'spolužáci', 'link' => $this->link('classmates'), 'active' => $this->isLinkCurrent('classmates')));
        }
        if($this->groupPermission['settings']) {
            $this['topPanel']->addToMenu((object)array('name' => 'nastavení', 'link' => $this->link('settings'), 'active' => $this->isLinkCurrent('settings')));
        }
        $this['topPanel']->addToMenu((object)array('name' => 'o skupině', 'link' => $this->link('about'), 'active' => $this->isLinkCurrent('about')));    
        
        $this->template->activeGroup = $this->activeGroup;
        $this->template->activeUser = $this->activeUser;
        $this->template->groupPermission = $this->groupPermission;
    }
    
    public function createComponentCommentForm($id)
    {
        $commentForm = new CommentForm($this->messageManager, $this->userManager, $this->activeUser);
        return $commentForm;
    }    
        
    protected function setPermission()
    {
        $privileges = $this->groupManager->getPrivileges($this->activeGroup->id);
        //zatím je oprávnění pouze pro učitele a studenty
        if($this->activeGroup->owner->id === $this->activeUser->id) {
            $this->groupPermission['archive'] = true;
            $this->groupPermission['settings'] = true;
            $this->groupPermission['removeAllMessages'] = true;
            $this->groupPermission['removeAllComments'] = true;
            $this->groupPermission['topAllMessages'] = true;
            $this->groupPermission['addMessages'] = true;
            $this->groupPermission['addCommets'] = true;
            $this->groupPermission['removeMembers'] = true;
            $this->groupPermission['showDeleted'] = true;
            $this->groupPermission['showStudentsList'] = true;
            $this->groupPermission['editClassification'] = true;
            $this->groupPermission['editAllMessages'] = true;
        } else {
            $this->groupPermission['leave'] = true;
            $this->groupPermission['editOwnMessages'] = true;
            //tohle jsou nastavení z db které se dají nastavit per groupu
            $this->groupPermission['addMessages'] = $privileges['PR_CREATE_MSG'];
            $this->groupPermission['addCommets'] = $privileges['PR_CREATE_MSG'];
            $this->groupPermission['removeOwnComments'] = $privileges['PR_DELETE_OWN_MSG'];
            $this->groupPermission['removeOwnMessages'] = $privileges['PR_DELETE_OWN_MSG'];
        }
    }
    
    protected function createComponentStream()
    {
        $stream = $this->streamFactory->create();
        $stream->setGroup($this->activeGroup);
        $stream->setUser($this->activeUser);
        $stream->setStreamPermission($this->groupPermission);
        $stream->showDeleted($this->showDeleted);
        return $stream;
    }
    
    public function createComponentGroupSettingsForm()
    {
        $component = $this->groupSettings->create();
        $component->setGroup($this->activeGroup);
        return $component;
    }
    
    
    public function createComponentUsersList()
    {
        $usersList = $this->usersListFactory->create();
        $usersList->setGroup($this->activeGroup);
        $usersList->setUser($this->activeUser);
        $usersList->setGroupPermission($this->groupPermission);
        return $usersList;
    }
    
    

    
    protected function createComponentSharingForm()
    {
        $form = new \Nette\Application\UI\Form;

        $form->addCheckbox('shareByCode','Zapnout sdílení kódem', array(1,0))
             ->setDefaultValue($this->activeGroup->shareByCode);
        $form->addCheckbox('shareByLink','Zapnout sdílení linkem', array(1,0))
             ->setDefaultValue($this->activeGroup->shareByLink);

        $form->onSuccess[] = function($form, $values) {
            $this->groupManager->switchSharing($this->activeGroup, $values['shareByLink'], $values['shareByCode']);
            $this->flashMessage('Sdílení nastaveno');
            $this->redirect('this');
        };
        return $form;        
    }
    
    protected function createComponentStreamSettingsForm()
    {
        $form = new \Nette\Application\UI\Form;

        $form->setMethod('get');
        $form->addCheckbox('showDeleted','Zobrazit smazané položky', array(true, false))
             ->setDefaultValue($this->showDeleted);
        return $form;        
    }
    
    public function redrawTasks() {
        $this['stream']->redrawControl('messages');
    }
    
    public function createComponentCommitTaskForm()
    {
        $form = $this->commitTaskFormFactory->create();                
        $form->setActiveUser($this->activeUser);
        return $form;
    }
    
    public function handleRedrawNews()
    {
        $this['stream']->redrawControl('messages');
    }
    
    public function handleCheckNews($idSession = null) 
    {
        $lastCheck = $this->session->getSection('lastChecks');
        $oldTimeStamp = null;
        if($idSession !== null) { 
            $oldTimeStamp = $lastCheck->lastTimes[$idSession];
            $lastCheck->lastTimes[$idSession] = (new \DateTime())->getTimestamp();
            $this->payload->idSession = $idSession;
        } else {
            $idSession = rand(10000, 90000);
            $this->payload->idSession = $idSession;
            $lastCheck->lastTimes[$idSession] = (new \DateTime())->getTimestamp();
        }
        $oldTime = new \DateTime();
        $oldTime->setTimestamp($oldTimeStamp);
        $this->payload->news = $this->messageManager->newMessages($oldTime);
        //check news from this time
        $this->sendPayload();
    }
        
    
    public function actionDefault($showDeleted)
    {       
        $this->template->actualTasks = $this->taskManager->getClosestTask(array($this->activeGroup->id => $this->activeGroup));
        $this->groupManager->setGroupVisited($this->activeUser, $this->activeGroup->id);
        $this->template->groupMembers = $this->groupManager->getGroupUsers($this->activeGroup->id, Group::RELATION_STUDENT);
        //pokud se mají ukázat smazané příspěvky
        if($this->groupPermission['showDeleted']) {
            $this->showDeleted = $showDeleted;
        }
    }
    
    public function actionClassmates()
    {       
        $members = $this->groupManager->getGroupUsers($this->activeGroup->id, \App\Model\Entities\Group::RELATION_STUDENT);
        $this->template->groupMembers = $members;
    }
    
    public function actionMessage($idMessage)
    {       
        $this['stream']->setSingleMode($idMessage);
    }
    
    public function actionSettings()
    {
        if(!$this->groupPermission['settings']) {
            $this->redirect(':Front:Homepage:noticeboard');
        }
        
        $this['topPanel']->setTitle('nastavení');
    }
    
    public function actionUsers()
    {
        if(!$this->groupPermission['showStudentsList']) {
            $this->redirect(':Front:Homepage:noticeboard');
        }
        
        $this['topPanel']->setTitle('uživatelé');
    }
    
    public function handleLeaveGroup($idGroup)
    {
           $this->groupManager->removeUserFromGroup($idGroup, $this->activeUser->id);
           $this->flashMessage("Opustil jste skupinu");
           $this->redirect(':Front:Homepage:noticeboard');
    }
    
    public function handleArchiveGroup($idGroup)
    {
           $this->groupManager->archiveGroup($idGroup);
           $this->flashMessage("Skupina archivována");
           $this->redirect(':Front:Homepage:noticeboard');
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

    public function handleDeleteMessage($idMessage) 
    {   
        $message = $this->messageManager->getMessage($idMessage);
        if($message->user->id === $this->activeUser->id || $this->activeUser->id === $this->activeGroup->owner->id) {
            $this->messageManager->deleteMessage($message);
            $this->presenter->flashMessage('Zpráva byla smazána.');
            $this->presenter->redirect('this');
        }
    }
}
