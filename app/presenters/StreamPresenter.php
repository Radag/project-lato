<?php

namespace App\Presenters;

use App\Model\UserManager;
use App\Components\Authetication\TopPanel\TopPanel;
use App\Components\Stream\Stream\Stream;
use App\Model\MessageManager;

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
    
    public function __construct(UserManager $userManager, MessageManager $messageManager)
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
    }
    
    protected function createComponentTopPanel()
    {
        return new TopPanel($this->userManager);
    }
    
    protected function createComponentStream()
    {
        return new Stream($this->userManager, $this->messageManager);
    }
    
}
