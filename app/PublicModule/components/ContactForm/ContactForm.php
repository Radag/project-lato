<?php

namespace App\PublicModule\Components;

use App\Mail\MailManager;
use \Nette\Application\UI\Form;
use App\Service\ReCaptchaService;

class ContactForm extends \App\Components\BaseComponent
{
    /** @var MailManager */
    public $mailManager;
    
    /** @var ReCaptchaService */
    public $reCaptchaService;
    
    public function __construct(
        MailManager $mailManager,            
        ReCaptchaService $reCaptchaService
    )
    {
        $this->mailManager = $mailManager;
        $this->reCaptchaService = $reCaptchaService;
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm(false);

        $form->addText('name')
             ->setRequired('Zadejte vaše jméno.');
        
        $form->addEmail('email', 'Email:')
             ->setRequired('Prosím vyplňte váš email');

        $form->addTextArea('text');
        $form->addSubmit('send', 'Přihlásit');
        
        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }  
    
    public function processForm(Form $form, $values) 
    {
        $code = $this->presenter->getRequest()->getPost('g-recaptcha-response');
        if ($this->reCaptchaService->checkCode($code)) {
            $this->presenter->flashMessage('Špatná captcha');
        } else {
            $this->mailManager->sendContactMail($values);
            $this->presenter->flashMessage('Děkujeme ze zprávu');
            $form->setValues([], true);
        }
        $this->redrawControl();
    }
}
