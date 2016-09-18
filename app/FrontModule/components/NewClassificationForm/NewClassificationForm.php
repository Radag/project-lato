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
class NewClassificationForm extends Control
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
        $form->addText('name', 'Hodnocení')
             ->setAttribute('placeholder', 'název hodnocení')
             ->setRequired('Prosím napiště téma hodnocení.');
        $form->addCheckbox('forAll', 'Pro všechny studenty');
        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/NewClassificationForm.latte');
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $members = $this->groupManager->getGroupUsers($this->activeGroup->id);
        foreach($members as $member) {
            $classification = new \App\Model\Entities\Classification();
            $classification->group = $this->activeGroup;
            $classification->user = $member;
            $classification->name = $form->getValues()->name;
            $this->classificationManager->createClassification($classification);
        }

        $this->presenter->flashMessage('Hodnocení vloženo', 'success');
        $this->presenter->redrawControl('memberList');
        
    }
}
