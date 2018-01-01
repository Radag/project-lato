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


class LostPasswordForm extends \App\Components\BaseComponent
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

        $form->addSubmit('send', 'Odeslat');
        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }  
    
    public function processForm(Form $form, $values) 
    {
        $email = $values->email;
        $this->mailManager->sendLostPasswordMail($email, $this->presenter);
        $this->presenter->flashMessage('Pokud byl email zaregistrován, byl na něj odeslán email.', 'success');
        $this->presenter->redirect(':Public:Homepage:default');  
    }
}
