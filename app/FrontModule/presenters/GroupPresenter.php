<?php

namespace App\FrontModule\Presenters;

use App\Model\Manager\UserManager;
use App\Model\Manager\GroupManager;
use App\FrontModule\Components\Stream\IStreamFactory;
use App\FrontModule\Components\Group\About\IGroupSettingsFormFactory;
use App\FrontModule\Components\Group\About\IAboutGroupFactory;
use App\Model\Entities\Group;
use App\FrontModule\Components\Stream\ICommitTaskFormFactory;
use App\FrontModule\Components\Group\IStudentsFactory;
use App\FrontModule\Components\Group\IClassmatesFactory;

class GroupPresenter extends BasePresenter
{    
    /** @var UserManager @inject */
    public $userManager;
    
     /** @var GroupManager @inject */
    public $groupManager;
    
    /** @var IGroupSettingsFormFactory @inject */
    public $groupSettings = null;
    
    /** @var IStreamFactory @inject */
    public $streamFactory;
    
    /** @var IStudentsFactory @inject */
    public $studentsFactory;
    
    /** @var ICommitTaskFormFactory @inject */
    public $commitTaskFormFactory;
    
    /** @var IClassmatesFactory @inject */
    public $classmatesFactory;
    
    /** @var IAboutGroupFactory @inject */
    public $aboutGroup;
    
    /** @var Group */
    public $activeGroup = null;
    
    public $groupPermission = [
        'createMessages' => false
    ];
    
    /** @persistent */
    public $id;
    
    protected function startup()
    {
        parent::startup();
        $id = $this->getParameter('id');
        if(isset($id)) {
            $this->activeGroup = $this->groupManager->getUserGroup($id, $this->activeUser);
        }        
        if(empty($id) || empty($this->activeGroup)){
            $this->redirect(':Front:Homepage:noticeboard');
        }
        $this->setPermission();
        $this['topPanel']->setActiveGroup($this->activeGroup);
        $this['topPanel']->addToMenu((object)array('name' => 'stream', 'link' => $this->link('default'), 'active' => $this->isLinkCurrent('default')));
        if($this->activeGroup->relation === 'owner') {
            $this['topPanel']->addToMenu((object)array('name' => 'studenti', 'link' => $this->link('users'), 'active' => $this->isLinkCurrent('users')));
        } else {
            $this['topPanel']->addToMenu((object)array('name' => 'spolužáci', 'link' => $this->link('classmates'), 'active' => $this->isLinkCurrent('classmates')));
        }
        $this['topPanel']->addToMenu((object)array('name' => 'o skupině', 'link' => $this->link('about'), 'active' => ($this->isLinkCurrent('about') || $this->isLinkCurrent('settings'))));    
        $this->template->colorScheme = $this->activeGroup->colorScheme;
        $this->template->activeGroup = $this->activeGroup;
        $this->template->activeUser = $this->activeUser;
        $this->template->groupPermission = $this->groupPermission;
    }  
        
    protected function setPermission()
    {
        if($this->activeGroup->relation === 'owner') {
            $this->groupPermission['createMessages'] = true;
        } else {
            $this->groupPermission['createMessages'] = true;
        }
    }
    
    
    public function actionMessage($idMessage)
    {       
        $this['stream']->setSingleMode($idMessage);
        $this['topPanel']->activateBackArrow($this->link('Group:default', array('id'=>$this->id)));
    }
    
    public function actionSettings()
    {
        if(!$this->activeGroup->relation === 'owner') {
            $this->redirect(':Front:Homepage:noticeboard');
        }
        $this['topPanel']->setTitle('nastavení');
    }
    
    public function actionUsers()
    {
        if(!$this->activeGroup->relation === 'owner') {
            $this->redirect(':Front:Homepage:noticeboard');
        }
        $this['topPanel']->setTitle('uživatelé');
    }
    
    public function createComponentAboutGroup()
    {
        return $this->aboutGroup->create();
    }
    
    public function createComponentClassmates()
    {
        return $this->classmatesFactory->create();
    }
    
    protected function createComponentStream()
    {
        return $this->streamFactory->create();
    }
    
    public function createComponentGroupSettingsForm()
    {
        return $this->groupSettings->create();
    }

    public function createComponentStudents()
    {
        return $this->studentsFactory->create();
    }
}
