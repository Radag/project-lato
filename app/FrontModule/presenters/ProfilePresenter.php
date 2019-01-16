<?php

namespace App\FrontModule\Presenters;

use App\Model\Manager\ClassroomManager;
use App\Model\Manager\GroupManager;

class ProfilePresenter extends BasePresenter
{
    /** @var ClassroomManager @inject */
    public $classroomManager;
    
    /** @var GroupManager @inject */
    public $groupManager;
    
    /** @persistent */
    public $messagesFilter = null;
        
    public function renderDefault($id = null)
    {
        $this['topPanel']->setTitle('Profil');
        $this->template->activeUser = $this->activeUser;
        $myClasses = $this->classroomManager->getClasses($this->activeUser);
        if($id === null || $this->activeUser->slug === $id) {  
            $this->template->profileUser = $this->activeUser;
            $this->template->isMe = true;
            $this->template->schools = $myClasses;
            $profileId = $this->activeUser->id;
        } else {
            $profileUser = $this->userManager->get($id, true);
            if(!$profileUser) {
                $this->flashMessage("Tento uživatel neexistuje");
                $this->redirect('Homepage:noticeboard');
            }
            $profileId = $profileUser->id;
            $this->template->activeUser = $profileUser; 
            $this->template->isMe = false;
            $this->template->schools = $this->classroomManager->getClasses($profileUser);
            //$this->template->relation = $this->classroomManager->getRelation($profileUser, $myClasses);
            //$this->template->isFriend = $this->userManager->isFriend($this->activeUser->id, $profileUser->id);
        }
        $this->template->groups = $this->groupManager->getProfileUserGroups($profileId, $this->activeUser->id);
    }
    
    public function renderMessages()
    {
        $this['topPanel']->setTitle('Konverzace');
        $months = ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'];
        $return = [];
        $messages = $this->privateMessageManager->getMessages($this->activeUser);
        
        foreach($messages as $message) {
            $diff = (new \DateTime())->diff($message->created);
            $diffDays = (integer)$diff->format( "%R%a" );
            $message->diffDays = $diffDays;
            if($diffDays === 0) {
                if(!isset($return['today'])) {
                    $return['today'] = (object)array('messages' => array(), 'name' => 'Dnes'); 
                }
                $return['today']->messages[] = $message;
            } elseif($diffDays === -1) {
                if(!isset($return['yesterday'])) {
                    $return['yesterday'] = (object)array('messages' => array(), 'name' => 'Včera'); 
                }
                $return['yesterday']->messages[] = $message;
            } else {
                if(!isset($return[$message->created->format("n")])) {
                    $return[$message->created->format("n")] = (object)array('messages' => array(), 'name' => $months[$message->created->format("n") + 1]); 
                }
                $return[$message->created->format("n")]->messages[] = $message;
            }
        }
        $this->template->filter = $this->messagesFilter;
        $this->template->messages = $return;
    }
        
    
    public function renderConversation($user)
    {
        $userTo = $this->userManager->get($user, true);
        $this['topPanel']->setTitle('Konverzace s ' . $userTo->name . " " . " " . $userTo->surname);
        $this['privateMessageForm']->setIdUserTo($userTo->id);
        $this->privateMessageManager->setMessagesRead($userTo->id, $this->activeUser->id);
        $this->template->messages = $this->privateMessageManager->getConversation($this->activeUser, $userTo);
    }
    
    public function handleAddFriend($idUser)
    {
        $this->userManager->switchUserRelation($this->activeUser->id, $idUser, true);
        $this->flashMessage('Uživatel byl přidán mezi přátele');   
        $this->redrawControl('profileMenu');
    }
    
    public function handleRemoveFriend($idUser)
    {
        $this->userManager->switchUserRelation($this->activeUser->id, $idUser, false);
        $this->flashMessage('Uživatel byl odebrán z přátel');
        $this->redrawControl('profileMenu');
    }
    
    public function handleFilterMessages($filter = null)
    {
        $this->messagesFilter = $filter;
        $this->redrawControl("messagesList");
    }
}
