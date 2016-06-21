<?php

namespace App\Presenters;

use App\Model\UserManager;
use App\Components\Authetication\TopPanel\TopPanel;
use App\Components\Stream\Stream\Stream;
use App\Model\MessageManager;
use App\Model\GroupManager;
use App\Model\PrivateMessageManager;
use App\Model\NotificationManager;


class StreamPresenter extends BasePresenter
{

    public function startup() {
        parent::startup();
        if(!$this->getUser()->isLoggedIn()) {
            $this->redirect('Homepage:default');
        }
    }
    
    
    /**
     *
     * @var UserManager $userManager
     */
    private $userManager;
    private $messageManager;
    private $groupManager;
    private $privateMessageManager;
    private $notificationManager;
    private $activeGroup = null;
    
    public function __construct(UserManager $userManager, 
            MessageManager $messageManager, 
            GroupManager $groupManager,
            PrivateMessageManager $privateMessageManager,
            NotificationManager $notificationManager)
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->groupManager = $groupManager;
        $this->privateMessageManager = $privateMessageManager;
        $this->notificationManager = $notificationManager;
        
    }
    
    protected function createComponentTopPanel()
    {
        return new TopPanel($this->userManager, $this->groupManager, $this->activeGroup, $this->privateMessageManager, $this->notificationManager);
    }
    
    protected function createComponentStream()
    {
        return new Stream($this->userManager, $this->messageManager, $this->activeGroup);
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
        $this->template->groups = $this->groupManager->getGroups($this->user);
    }
    
    public function actionProfile($idUser = null)
    {
        if($idUser === null) {          
            $this->template->activeUser = $this->userManager->get($this->user->id);
        } else {
            $this->template->activeUser = $this->userManager->get($idUser);        
        }
    }
    
    public function actionDefault($id)
    {
        $group = $this->groupManager->getGroup($id);
        $this->groupManager->setGroupVisited($this->user, $id);
        $this->activeGroup = $group;
        
    }
}
