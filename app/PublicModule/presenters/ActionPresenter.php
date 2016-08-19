<?php

namespace App\PublicModule\Presenters;

use App\Model\Manager\UserManager;
use App\Model\Manager\GroupManager;
use App\Model\Manager\PublicActionManager;


class ActionPresenter extends BasePresenter
{
    /**
     * @var UserManager $userManager
     */
    private $userManager;

    /**
     * @var GroupManager $groupManager
     */
    private $groupManager;
    
    /**
     * @var PublicActionManager $publicActionManager
     */
    private $publicActionManager;

    
    public function __construct(UserManager $userManager, GroupManager $groupManager, PublicActionManager $publicActionManager)
    {
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->publicActionManager = $publicActionManager;
    }
    
    public function actionDefault($id)
    {
        $action = $this->publicActionManager->getAction($id);
        if(!empty($action)) {
            if($this->user->isLoggedIn()) {
                if($this->groupManager->isUserInGroup($this->user->id, $action->ID_GROUP)) {
                    $this->template->isInGroup = true;
                } else {
                    $this->template->isInGroup = false;
                }
            } else {
                $redirectSection = $this->getSession()->getSection('redirect');
                $redirectSection->params = $this->getParameters();
                $redirectSection->action = $this->getAction();
                $redirectSection->link = $this->getRequest()->getPresenterName();
                $this->redirect('Homepage:default');
            }      
        }
        
        $this->template->action = $action;
    }    
    
    public function handleAddToGroup($id)
    {
        $action = $this->publicActionManager->getAction($id);
        if(!empty($action) && $action->ACTION_TYPE === 1) {
            if($this->user->isLoggedIn()) {
                $this->groupManager->addUserToGroup($action->ID_GROUP, $this->user->id, 1);
                $this->redirect(':Front:Stream:default', array('id'=>$action->URL_ID));
            } else {
                $redirectSection = $this->getSession()->getSection('redirect');
                $redirectSection->params = $this->getParameters();
                $redirectSection->action = $this->getAction();
                $redirectSection->link = $this->getRequest()->getPresenterName();
                $this->redirect('Homepage:default');
            }
        } else {
           
        }
    }
}
