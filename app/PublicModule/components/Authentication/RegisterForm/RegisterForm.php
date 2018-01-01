<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\PublicModule\Components\Authetication;

use \Nette\Application\UI\Form;
use App\Model\Manager\UserManager;
use App\Mail\MailManager;


class RegisterForm extends \App\Components\BaseComponent
{
    
    /** @var UserManager */
    private $userManager;
    
    /** @var MailManager */
    private $mailManager;
    
    public function __construct(
        UserManager $userManager,
        MailManager $mailManager
    )
    {
        $this->userManager = $userManager;
        $this->mailManager = $mailManager;
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm();
        $form->addText('email', 'Váš email:')
             ->addRule(Form::EMAIL, 'Email nemá správný formát.')
             ->setRequired('Prosím vyplňte váš email.');

        $form->addText('name', 'Jméno:')
            ->setRequired('Prosím vyplňte své jméno.');
        
        $form->addText('surname', 'Příjmení:')
            ->setRequired('Prosím vyplňte své příjmení.');
        
        $form->addPassword('password1', 'Heslo:')
            ->setRequired('Prosím vyplňte své heslo.');
        
        $form->addPassword('password2', 'Heslo znovu:')
            ->setRequired('Prosím napište heslo znovu pro kontrolu.')
            ->addRule(Form::EQUAL, 'Hesla se neshodují', $form['password1']);


        $form->addSubmit('send', 'Registrovat');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }  
    
    public function processForm(Form $form, $values) 
    {
       try {
            $pass = $values->password1;
            $idUser = $this->userManager->add($values);
            
            $this->mailManager->sendRegistrationMail($values, $idUser, $this->presenter);
            
            $this->presenter->flashMessage('Byl jste zaregistrován. Vítejte !', 'success');
            
        } catch (\Exception $ex) {
            $this->presenter->flashMessage($ex->getMessage(), 'error');
            return false;
        }
        $this->presenter->user->login($values->email, $pass);
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
