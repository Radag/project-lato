<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Account\SharingSettings;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;



/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class SharingSettings extends Control
{
        
    protected $activeUser;
    
    
    protected function createComponentForm()
    {
        $form = new \Nette\Application\UI\Form;

     
        $form->addSubmit('submit', 'Odeslat');
        $form->onSuccess[] = function($form, $values) {
            $this->userManager->updateUser($values, $this->activeUser );
            $this->flashMessage('Nastavení uživatele uloženo');
            $this->redirect('this');
        };
        return $form;        
    }
    
    public function __construct( \App\Model\Entities\User $activeUser)
    {
        $this->activeUser = $activeUser;  
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/SharingSettings.latte');
        $template->render();
    }
   
}
