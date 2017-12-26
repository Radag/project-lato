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
class NewClassificationForm extends \App\Components\BaseComponent
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
        $form = $this->getForm();
        $form->addText('name', 'Hodnocení')
             ->setAttribute('placeholder', 'název hodnocení')
             ->setRequired('Prosím napiště téma hodnocení.');
        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }
    
    public function processForm(Form $form, $values) 
    {
        $classificationGroup = new \App\Model\Entities\ClassificationGroup();
        $classificationGroup->group = $this->activeGroup;
        $classificationGroup->name = $form->getValues()->name;
        $id = $this->classificationManager->createGroupClassification($classificationGroup);
        
        $this->presenter->flashMessage('Hodnocení vloženo', 'success');
        $this->presenter->redirect('this', array('do'=> 'usersList-classification' , 'usersList-idGroupClassification' => $id));
        
    }
}
