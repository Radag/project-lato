<?php

namespace App\FrontModule\Presenters;

use App\Model\Manager\UserManager;
use App\Model\Manager\GroupManager;
use App\Model\Entities\Group;
use App\FrontModule\Components\Stream\IStream;
use App\FrontModule\Components\Group\About\IGroupSettingsForm;
use App\FrontModule\Components\Group\About\IAboutGroup;
use App\FrontModule\Components\Stream\ICommitTaskForm;
use App\FrontModule\Components\Group\IClassification;
use App\FrontModule\Components\Group\IStudentsList;
use App\FrontModule\Components\Group\IClassmates;

class GroupPresenter extends BasePresenter
{    
    /** @var UserManager @inject */
    public $userManager;
    
     /** @var GroupManager @inject */
    public $groupManager;
    
    /** @var IGroupSettingsForm @inject */
    public $groupSettings = null;
    
    /** @var IStream @inject */
    public $streamFactory;
    
    /** @var IClassification @inject */
    public $studentsClassification;
    
    /** @var IStudentsList @inject */
    public $studentsList;
    
    /** @var ICommitTaskForm @inject */
    public $commitTaskFormFactory;
    
    /** @var IClassmates @inject */
    public $classmatesFactory;
    
    /** @var IAboutGroup @inject */
    public $aboutGroup;
    
    /** @var Group */
    public $activeGroup = null;
    
    /** @persistent */
    public $id;
    
    protected function startup()
    {
        parent::startup();
        $id = $this->getParameter('id');
        if(isset($id)) {
            $this->activeGroup = $this->groupManager->getUserGroup($id, $this->activeUser);
        }
        if(empty($id) || empty($this->activeGroup)) {
            $this->presenter->flashMessage('Skupina neexistuje nebo do ní nemáte přístup.');
            $this->redirect(':Front:Homepage:noticeboard');
        }
        if($this->activeGroup->archived && $this->activeGroup->relation !== GroupManager::RELATION_OWNER) {
            $this->presenter->flashMessage('Skupina je archivovaná a nemáte do ní přístup.');
            $this->redirect(':Front:Homepage:noticeboard');
        }
        
        $this['topPanel']->setActiveGroup($this->activeGroup);
        $this['topPanel']->addToMenu((object)['name' => 'stream', 'link' => $this->link('default'), 'active' => $this->isLinkCurrent('default')]);
        if($this->activeGroup->relation === 'owner') {
            $this['topPanel']->addToMenu((object)['name' => 'studenti', 'link' => $this->link('usersList'), 'active' => $this->isLinkCurrent('usersList')]);
        } else {
            $this['topPanel']->addToMenu((object)['name' => 'spolužáci', 'link' => $this->link('classmates'), 'active' => $this->isLinkCurrent('classmates')]);
        }
        $this['topPanel']->addToMenu((object)['name' => 'o skupině', 'link' => $this->link('about'), 'active' => ($this->isLinkCurrent('about') || $this->isLinkCurrent('settings'))]);    
        $this->template->colorScheme = $this->activeGroup->colorScheme;
        $this->template->activeGroup = $this->activeGroup;
        $this->template->activeUser = $this->activeUser;
    }  
    
    public function actionMessage($idMessage)
    {       
        $this['stream']->setSingleMode($idMessage);
        $this['topPanel']->activateBackArrow($this->link('Group:default', array('id'=>$this->id)));
    }
    
    public function actionSettings()
    {
        if($this->activeGroup->relation !== 'owner') {
            $this->redirect('Homepage:noticeboard');
        }
        $this['topPanel']->setTitle('nastavení');
    }
    
    public function actionUsersClassification($classificationGroupId)
    {
        if($this->activeGroup->relation !== 'owner' || empty($classificationGroupId)) {
            $this->redirect('Group:default');
        }
        $this['topPanel']->setTitle('uživatelé');
        $this['studentsClassification']->setGroupClassification($classificationGroupId);
    }
    
    public function actionUsersList()
    {
        
        if($this->activeGroup->relation !== 'owner') {
            $this->redirect('Homepage:noticeboard');
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

    public function createComponentStudentsList()
    {
        return $this->studentsList->create();
    }
    
    public function createComponentStudentsClassification()
    {
        return $this->studentsClassification->create();
    }
}
