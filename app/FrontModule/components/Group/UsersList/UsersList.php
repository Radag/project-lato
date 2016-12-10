<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Group;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\Manager\GroupManager;
use App\FrontModule\Components\NewClassificationForm\NewClassificationForm;
use App\FrontModule\Components\NewClassificationForm\UserClassificationForm;
use App\Model\Manager\ClassificationManager;


/**
 * Description of JoinGroupForm
 *
 * @author Radaq
 */
class UsersList extends Control
{
        
    /** @var GroupManager */
    private $groupManager;
    
    /** @var ClassificationManager */
    private $classificationManager;
    
    /** @var \App\Model\Entities\Group $activeGroup */
    protected $activeGroup;
    
    /** @var \App\Model\Entities\User $activeUser */
    protected $activeUser;
    
    
    protected $streamPermission = array();
    
    protected $isDefault = true;
    
    
    public function __construct(GroupManager $groupManager,
                                ClassificationManager $classificationManager
            )
    {
        $this->groupManager = $groupManager;
        $this->classificationManager = $classificationManager;
    }
    
    public function setUser(\App\Model\Entities\User $user)
    {
        $this->activeUser = $user;
    }
    
    public function setStreamPermission($permission)
    {
        $this->streamPermission = $permission;
    }
    
    public function setGroup(\App\Model\Entities\Group $group)
    {
        $this->activeGroup = $group;
    }
    
    public function setDefault() {
        $members = $this->groupManager->getGroupUsers($this->activeGroup->id, \App\Model\Entities\Group::RELATION_STUDENT);
        foreach($members as $member) {
            $member->getClassification()->items = $this->classificationManager->getUserClassification($member->id, $this->activeGroup->id);
            $averageGrade = 0;
            foreach($member->getClassification()->items as $class) {
                $averageGrade = $averageGrade + $class->grade;
                if($member->getClassification()->lastDate === null || $member->getClassification()->lastDate < $class->classificationDate) {
                    $member->getClassification()->lastDate = $class->classificationDate;
                }
            }
            $member->getClassification()->averageGrade = round($averageGrade/count($member->getClassification()->items), 2);
        }
        $this->template->groupMembers = $members;
        $this->template->setFile(__DIR__ . '/Students.latte');
    }
    
    public function render()
    {
        $this->template->activeUser = $this->activeUser;
        if($this->isDefault) {
            $this->setDefault();
        }
        $this->template->render();
    }
    
   
    
    public function handleShowUserClassificationForm($idUserTo) 
    {
        $this['userClassificationForm']->setUser($idUserTo);
        $this->redrawControl('userClassificationForm');
    }
    
    public function handleClassification($idGroupClassification) 
    {

        $classificationGroup = $this->classificationManager->getGroupClassification($idGroupClassification);
        $members = $this->groupManager->getGroupUsers($this->activeGroup->id, \App\Model\Entities\Group::RELATION_STUDENT);
        $this->template->classificationGroup = $classificationGroup;
        $this['classificationForm']->setDefaults(array(
            'idGroupClassification' => $idGroupClassification
        ));
        
        foreach($classificationGroup->classifications as $classification) {
            $this['classificationForm']->setDefaults(array(
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
        
        $this->isDefault = false;
        $this->template->setFile(__DIR__ . '/Classification.latte');
    }
    
    public function handleEditUserClassificationForm($idClassification) 
    {
        $classification = $this->classificationManager->getClassification($idClassification);
        $this['userClassificationForm']['form']->setDefaults(array(
            'name' => $classification->name,
            'grade' => $classification->grade,
            'notice' => $classification->notice,
            'idClassification' => $classification->idClassification
        ));
        $this->redrawControl('userClassificationForm');
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
    
    public function createComponentAddClassificationForm()
    {
        $component = new NewClassificationForm($this->classificationManager, $this->groupManager, $this->activeGroup);
        return $component;
    }
    
    public function createComponentUserClassificationForm()
    {
        $component = new UserClassificationForm($this->classificationManager, $this->groupManager, $this->activeGroup);
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
                $this->classificationManager->createClassification($classification);
            }
            $this->redirect('this');
        };
        
        return $form;
    }
}
