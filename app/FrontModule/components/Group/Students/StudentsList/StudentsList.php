<?php

namespace App\FrontModule\Components\Group;

use App\Model\Manager\GroupManager;
use App\FrontModule\Components\NewClassificationForm\UserClassificationForm;
use App\Model\Manager\ClassificationManager;
use App\Model\Manager\TaskManager;
use App\Model\Manager\UserManager;
use App\Model\Manager\NotificationManager;
use App\FrontModule\Components\Group\IAddUserForm;


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
    
    /** @var IAddUserForm */
    private $addUserForm;
    
    
    public function __construct(
        GroupManager $groupManager,
        ClassificationManager $classificationManager,
        TaskManager $taskManager,
        UserManager $userManager,
        NotificationManager $notificationManager,
        IAddUserForm $addUserForm
    )
    {
        $this->groupManager = $groupManager;
        $this->classificationManager = $classificationManager;
        $this->taskManager = $taskManager;
        $this->userManager = $userManager;
        $this->notificationManager = $notificationManager;
        $this->addUserForm = $addUserForm;
    }

    public function render()
    {
        $members = $this->groupManager->getGroupUsers($this->presenter->activeGroup->id, [GroupManager::RELATION_STUDENT, GroupManager::RELATION_FIC_STUDENT]);
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
            $this->groupManager->addUserToGroup($this->activeGroup, $user->id, GroupManager::RELATION_STUDENT, $this->notificationManager);
            $this->presenter->flashMessage('Byl přidán do skupiny.');
            
            $notification = new \App\Model\Entities\Notification;
            $notification->idUser = $user->id;
            $notification->title = "Byl jste přidán do skupiny";
            $notification->text = "Byl jste přidán do skupiny " . $this->activeGroup->name;
            $notification->idGroup = $this->activeGroup->id;
            $notification->triggerUser = $this->presenter->activeUser->id;
            $this->notificationManager->addNotification($notification);
        }
        $this->presenter->redirect('this');
    }
    
    public function handleAddClassificationToUsers() 
    {
        $users = $this->presenter->getRequest()->getPost('users');
        if(is_array($users)) {
            $classificationUsers = $this->userManager->getMultiple($users, false, true);
        } else {
            $classificationUsers = null;
        }
        $this['userClassificationForm']->setUsers($classificationUsers);
        $this->redrawControl('userClassificationForm');

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
            $this->template->confirmDeleteUsers = $this->userManager->getMultiple($users, false, true);
            $this->redrawControl('removeUsersModal');
        } else {
            $usersArray = [];
            foreach($users as $idUser) {
                $user = $this->userManager->get($idUser, false, true);
                $this->groupManager->removeUserFromGroup($this->presenter->activeGroup, $user, $this->notificationManager);
                $this->notificationManager->addWasRemovedFromGroup($user, $this->presenter->activeGroup, $this->notificationManager);
                $usersArray[] = (object)['id' => $idUser];
            }
            $this->flashMessage('Uživatel byl odebrán ze skupiny.', 'success');
            $this->redirect('this');
        }
    }
    
    public function createComponentUserClassificationForm()
    {
        
        $component = new UserClassificationForm($this->classificationManager, $this->groupManager, $this->presenter->activeGroup);
        return $component;
    }
        
    public function createComponentAddUserForm()
    {
        return $this->addUserForm->create();
    }
}
