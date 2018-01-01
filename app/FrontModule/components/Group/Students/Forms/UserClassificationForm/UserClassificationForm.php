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
             ->setAttribute('placeholder', 'Název hodnocení')
             ->setRequired('Prosím napiště téma hodnocení.');
        $form->addText('date', 'Datum')
             ->setAttribute('placeholder', 'Datum')
             ->setAttribute('type', 'date')
             ->setAttribute('placeholder', date('d. m. Y'))
             ->setValue(date("Y-m-d"));
//        $form->addSelect('grade', 'Známka', $this->grades)
//             ->setRequired();
        /*
        $form->addTextArea('notice', 'Poznámka')
             ->setAttribute('placeholder', 'poznámka');
         * 
         */
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
        $classification = new \App\Model\Entities\Classification();
        $classification->user = new \App\Model\Entities\User();
        $classification->group = $this->activeGroup;
        $classification->name = $values->name;
        //$classification->grade = $values->grade;
        //$classification->notice = $values->notice;
        $classification->idClassification = empty($values->idClassification) ? null : $values->idClassification;
        $classification->idPeriod = $this->presenter->activeGroup->activePeriodId;
        $classification->date = \DateTime::createFromFormat('Y-m-d', $values->date);
        $users = $this->presenter->getRequest()->getPost('users');
        foreach($users as $idUser) {
            $classification->user->id = $idUser;
            //$this->classificationManager->createClassification($classification);
        }

        //$this->presenter->flashMessage('Hodnocení vloženo', 'success');
        $this->parent->redirect('editGroupClassification!', ['idGroupClassification'=> true]);
        
    }
}
