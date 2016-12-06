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
        $form = new \Nette\Application\UI\Form;
        $form->addText('name', 'Název hodnocení')
             ->setAttribute('placeholder', 'název hodnocení')
             ->setRequired('Prosím napiště téma hodnocení.');
        $form->addText('grade', 'Hodnocení')
             ->setAttribute('placeholder', 'známka');
        $form->addTextArea('notice', 'Poznámka')
             ->setAttribute('placeholder', 'poznámka');
        $form->addHidden('idUser');
        $form->addHidden('idClassification');
        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }
    
    public function setUser($idUser) 
    {
        $this['form']['idUser']->setValue($idUser);
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/UserClassificationForm.latte');
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $user = new \App\Model\Entities\User();
        $user->id = $values->idUser;
        $classification = new \App\Model\Entities\Classification();
        $classification->group = $this->activeGroup;
        $classification->user = $user;
        $classification->name = $form->getValues()->name;
        $classification->grade = $form->getValues()->grade;
        $classification->notice = $form->getValues()->notice;
        $classification->idClassification = $form->getValues()->idClassification;
        $this->classificationManager->createClassification($classification);

        $this->presenter->flashMessage('Hodnocení vloženo', 'success');
        $this->presenter->redirect(':Front:Group:users');
        
    }
}
