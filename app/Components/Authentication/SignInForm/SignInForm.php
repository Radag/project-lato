<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Components\Authetication\SignInForm;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\UserManager;



/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class SignInForm extends Control
{
    
    /**
     *
     * @var UserManager $userManager
     */
    private $userManager;
    
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }
    
    protected function createComponentForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->addText('username', 'Uživatelské jméno:')
                ->setAttribute('placeholder', 'Uživatelské jméno')
            ->setRequired('Prosím vyplňte své uživatelské jméno.');

        $form->addPassword('password', 'Heslo:')
              ->setAttribute('placeholder', 'Heslo')
            ->setRequired('Prosím vyplňte své heslo.');

        $form->addCheckbox('remember');

        $form->addSubmit('send', 'Přihlásit');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/signInForm.latte');
        // vložíme do šablony nějaké parametry
        //$template->form = $this->form;
        // a vykreslíme ji
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {

        try {
            $this->user->setAuthenticator($this->userManager);
            $this->user->login($values->username, $values->password);
            $this->flashMessage('přihlášen', 'succes');
        } catch (\Exception $ex) {
            $this->flashMessage($ex->getMessage(), 'error');
        }
        $this->presenter->redirect('Wall:default');
        
    }
}
