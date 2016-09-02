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
                    $this->flashMessage('Již jste členem této skupiny.');
                    $this->redirect(':Front:Group:default', array('id'=>$action->URL_ID));
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
                $this->flashMessage('Byl jste přidán do skupiny.');
                $this->redirect(':Front:Group:default', array('id'=>$action->URL_ID));
            } else {
                $redirectSection = $this->getSession()->getSection('redirect');
                $redirectSection->params = $this->getParameters();
                $redirectSection->action = $this->getAction();
                $redirectSection->link = $this->getRequest()->getPresenterName();
                $this->flashMessage('Musíte se první přihlásit nebo zaregistrovat.');
                $this->redirect('Homepage:default');
            }
        } else {
           
        }
    }
}
