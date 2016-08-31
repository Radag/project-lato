<?php

namespace App\FrontModule\Presenters;

use App\Model\Manager\UserManager;
use App\FrontModule\Components\TopPanel\TopPanel;
use App\FrontModule\Components\Stream\Stream;
use App\Model\Manager\MessageManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\PrivateMessageManager;
use App\Model\Manager\NotificationManager;
use App\Model\Manager\FileManager;
use App\FrontModule\Components\Stream\IStreamFactory;

class StreamPresenter extends BasePresenter
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
    
    protected function createComponentTopPanel()
    {
        return new TopPanel($this->userManager, $this->groupManager, $this->activeGroup, $this->privateMessageManager, $this->notificationManager, $this->activeUser);
    }
    
    protected function createComponentStream()
    {
        $stream = $this->streamFactory->create();
        $stream->setGroup($this->activeGroup);
        $stream->setUser($this->activeUser);
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
    
    public function actionGroups()
    {
        $this->template->groups = $this->groupManager->getGroups($this->activeUser);
    }
        
    
    public function actionDefault($id)
    {
        $group = $this->groupManager->getGroup($id);
        
        if(!$this->groupManager->isUserInGroup($this->activeUser->id, $group->id)){
            $this->redirect(':Front:Stream:groups');
        }
        $this->groupManager->setGroupVisited($this->activeUser, $group->id);
        $this->activeGroup = $group;
        $this->template->activeGroup = $this->activeGroup;
        $this->template->activeUser = $this->activeUser;
        $this->template->groupMembers = $this->groupManager->getGroupUsers($group->id);  
    }
    
    public function actionSettings($id)
    {
    }
    
    public function actionUsers($id)
    {
        $group = $this->groupManager->getGroup($id);
        $this->activeGroup = $group;
        $this->template->activeGroup = $this->activeGroup;
        $this->template->activeUser = $this->activeUser;
        $this->template->groupMembers = $this->groupManager->getGroupUsers($group->id);
    }
    
    public function handleLeaveGroup($idGroup)
    {
           $this->groupManager->removeUserFromGroup($idGroup, $this->activeUser->id);
           $this->flashMessage("Opustil jste skupinu");
           $this->redirect(':Front:Stream:groups');
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
}
