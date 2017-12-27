<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Group;

use App\Model\Manager\ClassificationManager;
use App\Model\Manager\UserManager;
use App\Model\Manager\GroupManager;


class Classification extends \App\Components\BaseComponent
{
       
    /** @var UserManager */
    private $userManager;
    
    /** @var ClassificationManager */
    private $classificationManager;
    
    /** @var GroupManager */
    private $groupManager;
    
    
    private $classificationGroupId = null;
    
    protected $grades = ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '—' => '—', 'N' => 'N'];
    
    public function __construct(
        ClassificationManager $classificationManager,
        UserManager $userManager,
        GroupManager $groupManager
    )
    {
        $this->classificationManager = $classificationManager;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
    } 
    
    public function render()
    {
        $this->template->permission = $this->presenter->groupPermission;
        $this->template->activeUser = $this->presenter->activeUser;
        $classificationGroup = $this->classificationManager->getGroupClassification($this->classificationGroupId);
        $members = $this->groupManager->getGroupUsers($this->presenter->activeGroup->id, \App\Model\Entities\Group::RELATION_STUDENT);
        if(!empty($classificationGroup->task)) {
            foreach($members as $member) {
                $classificationGroup->task->commitArray[$member->id] = $this->taskManager->getCommitByUser($classificationGroup->task->idTask, $member->id);
            }
        }
        
        $this->template->classificationGroup = $classificationGroup;
        $this['form']->setDefaults(array(
            'idGroupClassification' => $this->classificationGroupId
        ));
        
        foreach($classificationGroup->classifications as $classification) {
            $this['form']->setDefaults(array(
                'grade' . $classification->user => $classification->grade,
                'notice' . $classification->user => $classification->notice
            )); 
        }
        
        $this['editClassGroupForm']->setDefaults(array(
            'name' => $classificationGroup->name,
            'date' => $classificationGroup->classificationDate,
            'id' => $classificationGroup->idClassificationGroup
        ));
        
        $this->template->members = $members;
        parent::render();
    }
    
    protected function createComponentForm()
    {
        $members = $this->groupManager->getGroupUsers($this->presenter->activeGroup->id);
        $form = $this->getForm();
        foreach($members as $member) {
            $form->addSelect('grade' . $member->id, 'Známka', $this->grades);
            $form->addTextArea('notice' . $member->id, 'Poznámka')
                 ->setAttribute('placeholder', 'Poznámka');
        }
        $form->addHidden('idGroupClassification');
        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = function(\Nette\Application\UI\Form $form) {
            $members = $this->groupManager->getGroupUsers($this->presenter->activeGroup->id);
            $values = $form->getValues(true);
            \Tracy\Debugger::barDump('ff');
            foreach($members as $member) {
                $classification = new \App\Model\Entities\Classification();
                $classification->grade = $values['grade' . $member->id];
                $classification->notice = $values['notice' . $member->id];
                $classification->idClassificationGroup = $values['idGroupClassification'];
                $classification->group = $this->presenter->activeGroup;
                $classification->user = $member;
                $classification->idPeriod = $this->presenter->activePeriod;
                \Tracy\Debugger::barDump($classification);
                $this->classificationManager->createClassification($classification);
            }
            //$this->redirect('this');
        };
        
        return $form;
    }
    
    
    public function handleAddClassificationToUsers($confirmed = false) 
    {
        $users = $this->presenter->getRequest()->getPost('users');
        if(!$confirmed) {
            $classificationUsers = array();
            foreach($users as $idUser) {
                $classificationUsers[] = $this->userManager->get($idUser);
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
       
        
    public function createComponentEditClassGroupForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->addText('name', 'Název')
             ->setRequired('Prosím napiště téma hodnocení.');
        $form->addText('date', 'Datum')
             ->setAttribute('placeholder', 'Datum (nepovinné)');
        $form->addHidden('id');
        $form->addSubmit('send', 'Potvrdit');

        $form->onSuccess[] = function(Form $form, $values) {
            $this->classificationManager->updateClassificationGroup($values);
            $this->presenter->redirect('this');
        };
        return $form;
    }
    
    public function setGroupId($groupId)
    {
        $this->classificationGroupId = $groupId;
    }
}
