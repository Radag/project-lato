<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Account\PersonalSettings;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\Manager\UserManager;
use App\Model\Entities\User;


/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class PersonalSettings extends Control
{
        
    /**
     * @var User $activeUser
     */
    protected $activeUser;
    
    /**
     * @var UserManager $userManager
     */
    protected $userManager;
    
    public function __construct(UserManager $userManager, User $activeUser)
    {
        $this->activeUser = $activeUser;
        $this->userManager = $userManager;  
    }

    protected function createComponentForm()
    {
        $form = new \Nette\Application\UI\Form;

        $form->addText('name','Jméno')
             ->setAttribute("placeholder","Jméno")
             ->setDefaultValue($this->activeUser->name);
        $form->addText('surname','Příjmení')
             ->setAttribute("placeholder","Příjmení")
             ->setDefaultValue($this->activeUser->surname);
        $form->addText('email','Emailová adresa')
             ->setAttribute("placeholder","Emailová adresa")
             ->setDefaultValue($this->activeUser->email);
        $form->addText('birthday','Datum narození')
             ->setAttribute("placeholder","Datum narození");

        $form->addSubmit('submit', 'Odeslat');
        $form->onSuccess[] = function($form, $values) {
            $this->userManager->updateUser($values, $this->activeUser );
            $this->flashMessage('Nastavení uživatele uloženo');
            $this->redirect('this');
        };
        return $form;        
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/PersonalSettings.latte');
        $template->render();
    }
}
