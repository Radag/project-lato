<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\NewGroupForm;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\Manager\GroupManager;



/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class NewGroupForm extends Control
{
        
    private $groupManager;
    
    public function __construct(GroupManager $groupManager)
    {
        $this->groupManager = $groupManager;
        
    }

    public function setMessage($id)
    {
        $this->idMessage = $id;
    }
    
    
    protected function createComponentForm()
    {
        $colors = array(
            1 => 'green-bg',
            4 => 'yellow-bg',
            2 => 'blue-bg',
            3 => 'purple-bg',
            5 => 'orange-bg'
        );
        $form = new \Nette\Application\UI\Form;
        $form->addText('name', 'Název skupiny')
             ->setAttribute('placeholder', 'Název skupiny')
             ->setRequired('Prosím vyplňte název skupiny.');
        $form->addRadioList('color','Barevné schéma', $colors);
        
        
        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/NewGroupForm.latte');
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $teacher = new \App\Model\Entities\User();
        $teacher->id = $this->getPresenter()->getUser()->id;
        
        $group = new \App\Model\Entities\Group();
        $group->name = trim($values['name']);
        $group->teacher = $teacher;
        $group->groupType = 2;
        $group->shortcut = strtoupper(substr($values['name'], 0, 3));
        $group->mainColor = $values['color'];        
        $this->groupManager->createGroup($group);
        $this->presenter->redirect('Stream:groups');
    }
}
