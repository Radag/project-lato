<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\PublicModule\Components\Authetication\SignInForm;

use \Nette\Application\UI\Form;
use App\Model\Manager\UserManager;



/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class SignInForm extends \App\Components\BaseComponent
{
    
    /**
     *
     * @var UserManager $userManager
     */
    private $userManager;
    
    /** @persistent */
    public $userEmail = '';
    
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm();

        $form->addEmail('email', 'Email:')
             ->setRequired('Prosím vyplňte váš přihlašovací email')
             ->setDefaultValue($this->userEmail);

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Prosím vyplňte své heslo.');

        $form->addCheckbox('remember');

        $form->addSubmit('send', 'Přihlásit');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }  
    
    public function processForm(Form $form, $values) 
    {
        try {
            $this->getPresenter()->user->setAuthenticator($this->userManager);
            $this->getPresenter()->user->login($values->email, $values->password);
            $this->presenter->flashMessage('Přihlášen', 'success');
        } catch (\Exception $ex) {
            $this->userEmail = $values->email;
            $form->addError($ex->getMessage());
            $this->presenter->flashMessage($ex->getMessage(), 'error');
            $this->redrawControl('sign-in-form');
            return false; 
        }
        
        if($this->presenter->session->hasSection('redirect')) {    
            $redirect = $this->presenter->session->getSection('redirect');
            $link = ':' . $redirect->link . ':' . $redirect->action;
            $params = $redirect->params;
            $redirect->remove();
            $this->presenter->redirect($link, $params);
        } else {
            $this->presenter->redirect(':Front:Homepage:noticeboard');  
        }
    }
}
