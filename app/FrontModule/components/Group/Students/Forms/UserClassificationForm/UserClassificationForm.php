<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\NewClassificationForm;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\Entities\Classification;
use App\Model\Manager\ClassificationManager;
use App\Model\Manager\GroupManager;



/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class UserClassificationForm extends \App\Components\BaseComponent
{
        
    protected $classificationManager;
    protected $groupManager;
    protected $activeGroup;

    protected $grades = ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '—' => '—', 'N' => 'N'];

    public function __construct(ClassificationManager $classificationManager,
            GroupManager $groupManager,
            \App\Model\Entities\Group $activeGroup)
    {
        $this->groupManager = $groupManager;
        $this->classificationManager = $classificationManager;
        $this->activeGroup = $activeGroup;
        
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm();
        $form->addText('name', 'Název hodnocení')
             ->setRequired('Prosím napiště téma hodnocení.');
        $form->addText('date', 'Datum')
             ->setAttribute('type', 'date')
             ->setValue(date("Y-m-d"));
        $form->addHidden('idClassification');
        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }
        
    public function setUsers($users) 
    {
        $classificationUsers = array();
        if(is_array($users)) {
            foreach($users as $user) {
                $classificationUsers[] = $user;
            }
        }
        $this->template->classificationUsers = $classificationUsers;
    }
    
    public function processForm(Form $form, $values) 
    {
        $classificationGroup = new \App\Model\Entities\ClassificationGroup();
        $classificationGroup->name = $values->name;
        $classificationGroup->idPeriod = $this->presenter->activeGroup->activePeriodId;
        $classificationGroup->classificationDate = \DateTime::createFromFormat('Y-m-d', $values->date);
        $users = $this->presenter->getRequest()->getPost('users');
        foreach($users as $idUser) {
            $classification = new Classification;
            $classification->user = new \App\Model\Entities\User();
            $classification->user->id = $idUser;
            $classificationGroup->classifications[] = $classification;
        }
        $this->parent->parent->showClassification('new', $classificationGroup);
        
    }
}
