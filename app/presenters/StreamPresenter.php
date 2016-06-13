<?php

namespace App\Presenters;

use App\Model\UserManager;
use App\Components\Authetication\TopPanel\TopPanel;
use App\Components\Stream\Stream\Stream;
use App\Model\MessageManager;
use App\Model\GroupManager;

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
    
    public function __construct(UserManager $userManager, MessageManager $messageManager, GroupManager $groupManager)
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->groupManager = $groupManager;
    }
    
    protected function createComponentTopPanel()
    {
        return new TopPanel($this->userManager);
    }
    
    protected function createComponentStream()
    {
        return new Stream($this->userManager, $this->messageManager);
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
        $this->template->groups = $this->groupManager->getGroups();
    }
    
    public function actionDefault($idGroup)
    {

    }
}
