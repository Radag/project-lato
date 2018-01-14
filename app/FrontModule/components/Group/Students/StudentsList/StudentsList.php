<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Group;

use App\Model\Manager\GroupManager;
use App\FrontModule\Components\NewClassificationForm\NewClassificationForm;
use App\FrontModule\Components\NewClassificationForm\UserClassificationForm;
use App\Model\Manager\ClassificationManager;
use App\Model\Manager\TaskManager;
use App\Model\Manager\UserManager;
use App\Model\Manager\NotificationManager;
use App\Model\Manager\PrivateMessageManager;
use App\FrontModule\Components\Group\AddUserForm;


class StudentsList extends \App\Components\BaseComponent
{
       
     /** @var GroupManager */
    private $groupManager;
    
    /** @var UserManager */
    private $userManager;
    
    /** @var TaskManager */
    private $taskManager;
    
    /** @var NotificationManager */
    private $notificationManager;
    
    /** @var ClassificationManager */
    private $classificationManager;
    
    /** @var PrivateMessageManager */
    private $privateMessageManager;
    
    
    
    public function __construct(
        GroupManager $groupManager,
        ClassificationManager $classificationManager,
        TaskManager $taskManager,
        UserManager $userManager,
        NotificationManager $notificationManager,
        PrivateMessageManager $privateMessageManager
    )
    {
        $this->groupManager = $groupManager;
        $this->classificationManager = $classificationManager;
        $this->taskManager = $taskManager;
        $this->userManager = $userManager;
        $this->notificationManager = $notificationManager;
        $this->privateMessageManager = $privateMessageManager;
    }

    public function render()
    {
        $members = $this->groupManager->getGroupUsers($this->presenter->activeGroup->id, GroupManager::RELATION_STUDENT);
        $classifications = $this->classificationManager->getGroupPeriodClassification($this->presenter->activeGroup);
        foreach($members as $member) {
            $member->getClassification()->items = isset($classifications[$member->id]) ? $classifications[$member->id] : [];
            $averageGrade = 0;
            $items = 0;
            foreach($member->getClassification()->items as $class) {
                if($class->grade !== null && $class->grade !== '—') {
                    $averageGrade = $averageGrade + ($class->grade === "N" ? 5 : $class->grade);
                    $items++;
                }
                if($member->getClassification()->lastDate === null || $member->getClassification()->lastDate < $class->classificationDate) {
                    $member->getClassification()->lastDate = $class->classificationDate;
                }
            }
            if($items>0) {
                $member->getClassification()->averageGrade = round($averageGrade/$items, 2);     
            } else {
                $member->getClassification()->averageGrade = null;    
            }
        }
        $this->template->groupMembers = $members;
        $this->template->permission = $this->presenter->groupPermission;
        $this->template->activeUser = $this->presenter->activeUser;
        parent::render();
    }
    
    
    public function handleAddToGroup()
    {
        $userEmail = $this->presenter->getRequest()->getPost('userName');
        $user = $this->userManager->getUserByMail($userEmail);
   
        if (empty($user)) {
            $this->presenter->flashMessage('Tento uživatel nepoužívá lato.');
        } elseif ($this->groupManager->isUserInGroup($user->id, $this->activeGroup->id)) {
            $this->presenter->flashMessage('Již je ve skupině.');
        } else {
            $this->groupManager->addUserToGroup($this->activeGroup, $user->id, GroupManager::RELATION_STUDENT);
            $this->presenter->flashMessage('Byl přidán do skupiny.');
            
            $notification = new \App\Model\Entities\Notification;
            $notification->idUser = $user->id;
            $notification->title = "Byl jste přidán do skupiny";
            $notification->text = "Byl jste přidán do skupiny " . $this->activeGroup->name;
            $notification->idGroup = $this->activeGroup->id;
            $this->notificationManager->addNotification($notification);
        }
        $this->presenter->redirect('this');
    }
    
    public function handleAddClassificationToUsers($confirmed = false) 
    {
        $users = $this->presenter->getRequest()->getPost('users');
        if(!$confirmed) {
            if(is_array($users)) {
               $classificationUsers = array();
                foreach($users as $idUser) {
                    $classificationUsers[] = $this->userManager->get($idUser);
                }
            } else {
                $classificationUsers = null;
            }
            $this['userClassificationForm']->setUsers($classificationUsers);
            $this->redrawControl('userClassificationForm');
        } else {
            foreach($users as $idUser) {
                $message = new \App\Model\Entities\PrivateMessage();
                $message->text = $this->presenter->getRequest()->getPost('text');
                $message->idUserFrom = $this->presenter->activeUser->id;
                $message->idUserTo = $idUser;
                $this->privateMessageManager->insertMessage($message);
            }
            $this->flashMessage('Zpáva byla odeslána.', 'success');
            $this->redirect('this');
        }
    }
    
    public function handleSendMessageToUsers($confirmed = false) 
    {
        $users = $this->presenter->getRequest()->getPost('users');
        if(!$confirmed) {
            $confirmMessageUsers = array();
            foreach($users as $idUser) {
                $confirmMessageUsers[] = $this->userManager->get($idUser);
            }
            $this->template->confirmMessageUsers = $confirmMessageUsers;
            $this->redrawControl('sendMessageModal');
        } else {
            foreach($users as $idUser) {
                $message = new \App\Model\Entities\PrivateMessage();
                $message->text = $this->presenter->getRequest()->getPost('text');
                $message->idUserFrom = $this->presenter->activeUser->id;
                $message->idUserTo = $idUser;
                $this->privateMessageManager->insertMessage($message);
            }
            $this->flashMessage('Zpáva byla odeslána.', 'success');
            $this->redirect('this');
        }
    }
   
    public function handleDeleteUsers($confirmed = false) 
    {
        $users = $this->presenter->getRequest()->getPost('users');
        if(!$confirmed) {
            $confirmDeleteUsers = array();
            foreach($users as $idUser) {
                $confirmDeleteUsers[] = $this->userManager->get($idUser);
            }
            $this->template->confirmDeleteUsers = $confirmDeleteUsers;
            $this->redrawControl('removeUsersModal');
        } else {
            $usersArray = array();
            foreach($users as $idUser) {
                $this->groupManager->removeUserFromGroup($this->activeGroup->id, $idUser);
                $usersArray[] = (object)array('id' => $idUser);
            }
            $data['users'] = $usersArray;
            $data['group'] = $this->groupManager->getGroup($this->activeGroup->id);
            $this->notificationManager->addNotificationType(NotificationManager::TYPE_REMOVE_FROM_GROUP, $data);
            $this->flashMessage('Uživatel byl odebrán ze skupiny.', 'success');
            $this->redirect('this');
        }
    }
    
    
    public function handleEditUserClassificationForm($idClassification) 
    {
       
        $classification = $this->classificationManager->getClassification($idClassification);
        $this['userClassificationForm']['form']->setDefaults(array(
            'name' => $classification->name,
            'grade' => $classification->grade,
            'notice' => $classification->notice,
            'idClassification' => $classification->id
        ));
        
        $this['userClassificationForm']->setUsers(array($classification->user));
        $this->redrawControl('userClassificationForm');
    }

    
    public function createComponentAddClassificationForm()
    {
        
        $component = new NewClassificationForm($this->classificationManager, $this->groupManager, $this->presenter->activeGroup);
        return $component;
    }
    
    public function createComponentUserClassificationForm()
    {
        
        $component = new UserClassificationForm($this->classificationManager, $this->groupManager, $this->presenter->activeGroup);
        return $component;
    }
    
    protected function createComponentClassificationForm()
    {
        $members = $this->groupManager->getGroupUsers($this->activeGroup->id);
        $form = new \Nette\Application\UI\Form;
        foreach($members as $member) {
            $form->addText('grade' . $member->id, 'Známka')
                 ->setAttribute('placeholder', 'Neuvedeno');
            $form->addTextArea('notice' . $member->id, 'Poznámka')
                 ->setAttribute('placeholder', 'Poznámka');
        }
        $form->addHidden('idGroupClassification');
        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = function(\Nette\Application\UI\Form $form) {
            $members = $this->groupManager->getGroupUsers($this->activeGroup->id);
            $values = $form->getValues(true);
            foreach($members as $member) {
                $classification = new \App\Model\Entities\Classification();
                $classification->grade = $values['grade' . $member->id];
                $classification->notice = $values['notice' . $member->id];
                $classification->idClassificationGroup = $values['idGroupClassification'];
                $classification->group = $this->activeGroup;
                $classification->user = $member;
                $classification->idPeriod = $this->presenter->activePeriod;
                $this->classificationManager->createClassification($classification);
            }
            $this->redirect('this');
        };
        
        return $form;
    }
    
    public function createComponentAddUserForm()
    {
        $component = new AddUserForm($this->userManager, $this->groupManager, $this->presenter->activeGroup);
        return $component;
    }
    
    public function handleEditGroupClassification($idGroupClassification)
    {
        $this->parent->showClassification($idGroupClassification);
    }
    
}
