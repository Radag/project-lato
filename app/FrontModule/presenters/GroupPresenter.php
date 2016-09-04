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
    
    /** @var \App\Model\Entities\Group */
    protected $activeGroup = null;
    
    /** @var  IStreamFactory  */
    protected $streamFactory;
    
    protected $groupPermission = array(
        'archive' => false,
        'leave' => false,
        'settings' => false,
        'addMessages' => false,
        'addCommets' => false,
        'removeAllMessages' => false,
        'removeOwnMessages' => false,
        'removeAllComments' => false,
        'removeOwnComments' => false,
        'topAllMessages' => false,
        'topOwnMessages' => false,
        'removeMembers' => false
    );
    
    /** @persistent */
    public $id;

    
    public function __construct(UserManager $userManager, 
            MessageManager $messageManager, 
            GroupManager $groupManager,
            PrivateMessageManager $privateMessageManager,
            NotificationManager $notificationManager,
            FileManager $fileManager,
            IStreamFactory $streamFactory)
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->groupManager = $groupManager;
        $this->privateMessageManager = $privateMessageManager;
        $this->notificationManager = $notificationManager;
        $this->fileManager = $fileManager;
        $this->streamFactory = $streamFactory;
    }
    
    protected function startup()
    {
        parent::startup();
        $id = $this->getParameter('id');
        if(isset($id)) {
            $this->activeGroup = $this->groupManager->getGroup($id);
        } else {
            $this->redirect(':Front:Homepage:groups');
        }
        if(!$this->groupManager->isUserInGroup($this->activeUser->id, $this->activeGroup->id)){
            $this->redirect(':Front:Homepage:groups');
        }
        $this->setPermission();
        $this['topPanel']->setActiveGroup($this->activeGroup);
        $this->template->activeGroup = $this->activeGroup;
        $this->template->activeUser = $this->activeUser;
        $this->template->groupPermission = $this->groupPermission;
    }
    
    protected function setPermission()
    {
        if($this->activeGroup->owner->id === $this->activeUser->id) {
            $this->groupPermission['archive'] = true;
            $this->groupPermission['settings'] = true;
            $this->groupPermission['removeAllMessages'] = true;
            $this->groupPermission['removeAllComments'] = true;
            $this->groupPermission['topAllMessages'] = true;
            $this->groupPermission['addMessages'] = true;
            $this->groupPermission['addCommets'] = true;
            $this->groupPermission['removeMembers'] = true;
        } else {
            $this->groupPermission['leave'] = true;
            $this->groupPermission['addMessages'] = true;
            $this->groupPermission['addCommets'] = true;
            $this->groupPermission['removeOwnComments'] = true;
            $this->groupPermission['removeOwnMessages'] = true;
        }
    }
    
    protected function createComponentStream()
    {
        $stream = $this->streamFactory->create();
        $stream->setGroup($this->activeGroup);
        $stream->setUser($this->activeUser);
        $stream->setStreamPermission($this->groupPermission);
        return $stream;
    }
    
    protected function createComponentSharingForm()
    {
        $form = new \Nette\Application\UI\Form;

        $form->addCheckbox('shareByCode','Zapnout sdílení', array(1,0))
             ->setDefaultValue($this->activeGroup->sharingOn);

        $form->onSuccess[] = function($form, $values) {
            $this->groupManager->switchSharing($this->activeGroup, $values['shareByCode']);
            if($values['shareByCode']) {
                $this->flashMessage('Sdílení zapnuto');
            } else {
                $this->flashMessage('Sdílení vypnuto');
            }
            $this->redirect('this');
        };
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
        
    
    public function actionDefault()
    {       
        $this->groupManager->setGroupVisited($this->activeUser, $this->activeGroup->id);
        $this->template->groupMembers = $this->groupManager->getGroupUsers($this->activeGroup->id);  
    }
    
    public function actionSettings()
    {
        $this['topPanel']->setTitle('nastavení');
    }
    
    public function actionUsers()
    {
        $this['topPanel']->setTitle('uživatelé');
        $this->template->groupMembers = $this->groupManager->getGroupUsers($this->activeGroup->id);
    }
    
    public function handleLeaveGroup($idGroup)
    {
           $this->groupManager->removeUserFromGroup($idGroup, $this->activeUser->id);
           $this->flashMessage("Opustil jste skupinu");
           $this->redirect(':Front:Homepage:groups');
    }
    
    public function handleArchiveGroup($idGroup)
    {
           $this->groupManager->archiveGroup($idGroup);
           $this->flashMessage("Skupina archivována");
           $this->redirect(':Front:Homepage:groups');
    }
    
    
    public function handleRemoveFromGroup($idGroup, $idUser)
    {
           $this->groupManager->removeUserFromGroup($idGroup, $idUser);
           $this->flashMessage("Vyhodil jste uživatele");
           $notification = new \App\Model\Entities\Notification;
           $notification->text = "Byl jste vyhozen ze skupiny";
           $notification->idGroup = $idGroup;
           $notification->title = "Nepříjemnost";
           $notification->idUser = $idUser;
           $this->notificationManager->addNotification($notification);
           $this->redirect('this');
    }
    

    
    public function handleAddToGroup()
    {
        $userName = $this->getRequest()->getPost('userName');
        $userId = $this->userManager->getByName($userName);

        if(empty($userId) || $this->groupManager->isUserInGroup($userId, $this->activeGroup->id)) {
            $this->flashMessage('Již je ve skupině.');
        } else {
            $this->groupManager->addUserToGroup($this->activeGroup->id, $userId, GroupManager::RELATION_STUDENT);
            $this->flashMessage('Byl přidán do skupiny.');
            
            $notification = new \App\Model\Entities\Notification;
            $notification->idUser = $userId;
            $notification->title = "Byl jste přidán do skupiny";
            $notification->text = "Byl jste přidán do skupiny " . $this->activeGroup->name;
            $notification->idGroup = $this->activeGroup->id;
            $this->notificationManager->addNotification($notification);
        }
        $this->redirect('this');
   }
}
