<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\PublicModule\Components\Authetication\SignInForm;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\Manager\UserManager;



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
        $template->setFile(__DIR__ . '/SignInForm.latte');
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        try {
            $this->getPresenter()->user->setAuthenticator($this->userManager);
            $this->getPresenter()->user->login($values->username, $values->password);
            $this->flashMessage('přihlášen', 'success');
        } catch (\Exception $ex) {
            $this->flashMessage($ex->getMessage(), 'error');
            $this->presenter->redirect(':Public:Homepage:default');  
        }
        
        if($this->presenter->session->hasSection('redirect')) {    
            $redirect = $this->presenter->session->getSection('redirect');
            $link = ':' . $redirect->link . ':' . $redirect->action;
            $params = $redirect->params;
            $redirect->remove();
            $this->presenter->redirect($link, $params);
        } else {
            $this->presenter->redirect(':Front:Stream:groups');  
        }
    }
}
