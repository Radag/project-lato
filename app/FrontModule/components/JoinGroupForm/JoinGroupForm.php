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
 * Description of JoinGroupForm
 *
 * @author Radaq
 */
class JoinGroupForm extends Control
{
        
    private $groupManager;
    
    public function __construct(GroupManager $groupManager)
    {
        $this->groupManager = $groupManager;
        
    }
    
    
    protected function createComponentForm()
    {
       
        $form = new \Nette\Application\UI\Form;
        $form->addText('code', 'Kód skupiny')
             ->setAttribute('placeholder', 'Kód skupiny')
             ->setRequired('Vložte kód skupiny.');        
        
        $form->addSubmit('send', 'Zapsat se');

        $form->onSuccess[] = [$this, 'processForm'];
        
        $form->onValidate[] = [$this, 'validateCode'];
        
        $form->onError[] = function(Form $form) {
            $this->presenter->payload->invalidForm = true;
            foreach($form->getErrors() as $error) {
                $this->presenter->flashMessage($error, 'error');
            }            
        };
        return $form;
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/JoinGroupForm.latte');
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $idGroup = $this->groupManager->getGroupByCode($values->code);
        $this->groupManager->addUserToGroup($idGroup, $this->presenter->activeUser->id, GroupManager::RELATION_STUDENT);
        $this->presenter->flashMessage('Byl jste přiřazen do skupiny.', 'success');
    }
    
    public function validateCode(Form $form, $values)
    {
        $idGroup = $this->groupManager->getGroupByCode($values->code);
        if($idGroup) {
            return true;
        } else {
            $form->addError("Špatný kód");
            return false;
        }
        
    }
}
