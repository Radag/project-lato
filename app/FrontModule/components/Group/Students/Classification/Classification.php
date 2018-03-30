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
use App\Model\Entities\ClassificationGroup;
use App\Model\Manager\TaskManager;

class Classification extends \App\Components\BaseComponent
{
       
    /** @var UserManager */
    private $userManager;
    
    /** @var ClassificationManager */
    private $classificationManager;
    
    /** @var GroupManager */
    private $groupManager;
    
    /** @var TaskManager */
    private $taskManager;
    
    protected $grades = ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '—' => '—', 'N' => 'N'];
    
    /** @var ClassificationGroup */
    protected $classificationGroup = null;
    
    public function __construct(
        ClassificationManager $classificationManager,
        UserManager $userManager,
        GroupManager $groupManager,
        TaskManager $taskManager
    )
    {
        $this->classificationManager = $classificationManager;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->taskManager = $taskManager;
    } 
    
    public function setClassification(ClassificationGroup $classificationGroup)
    {
        $this->classificationGroup = $classificationGroup;
    }
    
    public function render()
    {
        $this->template->permission = $this->presenter->groupPermission;
        $this->template->activeUser = $this->presenter->activeUser;
        $students = [];
        if($this->parent->classGroupId !== 'new') {
            $this->classificationGroup = $this->classificationManager->getGroupClassification($this->parent->classGroupId);
            foreach($this->classificationGroup->classifications as $cla) {
                $students[] = $cla->user->id;
            }
            $members = $this->groupManager->getGroupUsers($this->presenter->activeGroup->id, [GroupManager::RELATION_STUDENT, GroupManager::RELATION_FIC_STUDENT], $students);
            if(!empty($this->classificationGroup->task)) {
                foreach($members as $member) {
                    $commit = $this->taskManager->getCommitByUser($this->classificationGroup->task->id, $member->id);
                    if($commit) {
                        $commit->isLate = $this->classificationGroup->task->deadline > $commit->created;
                    }
                    $this->classificationGroup->task->commitArray[$member->id] = $commit;
                }
            } 
            $this['form']->setDefaults(array(
                'id' => $this->parent->classGroupId
            ));
        } else {
            if(empty($this->classificationGroup)) {
                $this->presenter->flashMessage('Není zadaná classicication group');
                $this->parent->showClassification(null);
            }
            $students = [];
            foreach($this->classificationGroup->classifications as $cla) {
                $students[] = $cla->user->id;
            }
          
            $members = $this->groupManager->getGroupUsers($this->presenter->activeGroup->id, [GroupManager::RELATION_STUDENT, GroupManager::RELATION_FIC_STUDENT], $students);
        }
        
        foreach($this->classificationGroup->classifications as $classification) {
            $this['form']->setValues([
                'grade' . $classification->user->id => $classification->grade,
                'notice' . $classification->user->id => $classification->notice,
                'members' => implode(',', $students)
            ]); 
        }
        
        $this['form']->setDefaults(array(
            'name' => $this->classificationGroup->name,
            'id' => $this->classificationGroup->id,
            'date' => $this->classificationGroup->classificationDate ? $this->classificationGroup->classificationDate->format('Y-m-d') : null
        ));
        
        $this['editClassGroupForm']->setDefaults(array(
            'name' => $this->classificationGroup->name,
            'date' => $this->classificationGroup->classificationDate ? $this->classificationGroup->classificationDate->format('Y-m-d') : null,
            'id' => $this->classificationGroup->id
        ));
        
        $this->template->classificationGroup = $this->classificationGroup;        
        $this->template->members = $members;
        parent::render();
    }
    
    protected function createComponentForm()
    {   
        $students = [];
        if($this->classificationGroup) {
            foreach($this->classificationGroup->classifications as $cla) {
                $students[] = $cla->user->id;
            } 
        } else {
            if(empty($this->presenter->getHttpRequest()->getPost('members'))) {
                $students = null;
            } else {
                $students = explode(',', $this->presenter->getHttpRequest()->getPost('members'));
            }
        }
        
        $form = $this->getForm();
        $members = $this->groupManager->getGroupUsers($this->presenter->activeGroup->id, [GroupManager::RELATION_STUDENT, GroupManager::RELATION_FIC_STUDENT], $students);   
        foreach($members as $member) {
            $form->addSelect('grade' . $member->id, 'Známka', $this->grades)
                 ->setDefaultValue('—');
            $form->addTextArea('notice' . $member->id, 'Poznámka')
                 ->setAttribute('placeholder', 'Vložit poznámku k hodnocení ...');
        }
        $form->addHidden('date');
        $form->addHidden('name');
        $form->addHidden('id');
        $form->addHidden('members');
        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = function(\Nette\Application\UI\Form $form, $values) {
            if(empty($values->id)) {
                $classifiationGroup = new ClassificationGroup();
                $classifiationGroup->name = $values->name;
                $classifiationGroup->date = $values->date;
                $classifiationGroup->group = $this->presenter->activeGroup;                
                $classifiationGroup->idPerion = $this->presenter->activeGroup->activePeriodId;
                $classGroupId = $this->classificationManager->createGroupClassification($classifiationGroup);
            } else {
                $classGroupId = $values->id;
            }
            
            $vals = [];
            foreach($values as $key=>$val) {
                if(strpos($key, 'grade') === 0) {
                    $vals[substr($key, 5)]['grade'] = $val;
                }
                
                if(strpos($key, 'notice') === 0) {
                    $vals[substr($key, 6)]['notice'] = $val;
                }
            }
            foreach($vals as $idUser=>$val) {
                $classification = new \App\Model\Entities\Classification();
                $classification->grade = $val['grade'];
                $classification->notice = $val['notice'];
                $classification->idClassificationGroup = $classGroupId;
                $classification->group = $this->presenter->activeGroup;
                $classification->idUser = $idUser;
                $classification->idPeriod = $this->presenter->activeGroup->activePeriodId;
                $this->classificationManager->createClassification($classification);
            }
            
            $this->presenter->flashMessage('Uloženo', 'success');
            $this->parent->showClassification(null, null, false);
        };
        
        return $form;
    }
    
    public function handleBack()
    {
        $this->parent->showClassification(null);
    }
        
    public function createComponentEditClassGroupForm()
    {
        $form = $this->getForm();
        $form->addText('name', 'Název')
             ->setRequired('Prosím napiště téma hodnocení.');
        $form->addText('date', 'Datum')
             ->setAttribute('type', 'date')
             ->setAttribute('placeholder', 'Datum (nepovinné)');
        $form->addHidden('id');
        $form->addSubmit('send', 'Potvrdit');

        $form->onSuccess[] = function($form, $values) {
            if(!empty($values->date)) {
                $values->date = \DateTime::createFromFormat('Y-m-d', $values->date);
            } else {
                $values->date = null;
            }            
            $this->classificationManager->updateClassificationGroup($values);
            $this->presenter->redirect('this');
        };
        return $form;
    }
}
