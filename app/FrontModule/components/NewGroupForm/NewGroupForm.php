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
        $colors = $this->groupManager->getColorsSchemes();
        $form = new \Nette\Application\UI\Form;
        $form->addText('name', 'Název skupiny')
             ->setRequired('Prosím vyplňte název skupiny.');
        $form->addRadioList('color','Barevné schéma', $colors)
             ->setDefaultValue(1);
        
        
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
        $owner = new \App\Model\Entities\User;
        $owner->id = $this->getPresenter()->getUser()->id;
        
        $group = new \App\Model\Entities\Group;
        $group->name = trim($values['name']);
        $group->owner = $owner;
        $group->shortcut = strtoupper(substr($values['name'], 0, 3));
        $group->mainColor = $values['color'];        
        $idGroup = $this->groupManager->createGroup($group);
        $this->presenter->redirect('Group:default', ['id' => $idGroup]);
    }
}
