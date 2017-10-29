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
class UserClassificationForm extends Control
{
        
    protected $classificationManager;
    protected $groupManager;
    protected $activeGroup;


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
        $form = new Form;
        $form->addText('name', 'Název hodnocení')
             ->setAttribute('placeholder', 'Název hodnocení')
             ->setRequired('Prosím napiště téma hodnocení.');
        $form->addText('grade', 'Hodnocení')
             ->setAttribute('placeholder', 'známka')
             ->addConditionOn($form['grade'], Form::FILLED)
                 ->addRule(Form::NUMERIC, 'Hodnocení musí být číslo větší než 0.')
                 ->addRule(Form::MIN, 'Hodnocení musí být číslo větší než 0.', 0);
        $form->addTextArea('notice', 'Poznámka')
             ->setAttribute('placeholder', 'poznámka');
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
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/UserClassificationForm.latte');
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $classification = new \App\Model\Entities\Classification();
        $classification->user = new \App\Model\Entities\User();
        $classification->group = $this->activeGroup;
        $classification->name = $values->name;
        $classification->grade = $values->grade;
        $classification->notice = $values->notice;
        $classification->idClassification = empty($values->idClassification) ? null : $values->idClassification;
        $classification->idPeriod = $this->presenter->activePeriod;
        
        $users = $this->presenter->getRequest()->getPost('users');
        foreach($users as $idUser) {
            $classification->user->id = $idUser;
            $this->classificationManager->createClassification($classification);
        }

        $this->presenter->flashMessage('Hodnocení vloženo', 'success');
        $this->presenter->redirect(':Front:Group:users');
        
    }
}
