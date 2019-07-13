<?php

namespace App\PublicModule\Components;

use App\Mail\MailManager;
use \Nette\Application\UI\Form;

class ContactForm extends \App\Components\BaseComponent
{
    /** @var MailManager */
    public $mailManager;
    
    public function __construct(MailManager $mailManager)
    {
        $this->mailManager = $mailManager;
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
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => '6LcDhq0UAAAAALennnki0ipOeWTlaXACcu8rEHKn',
            'response' => $this->presenter->getRequest()->getPost('g-recaptcha-response')
        ];
        $context  = stream_context_create([
            'http' => [
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ]);
        $verify = file_get_contents($url, false, $context);
        $captcha_success = json_decode($verify);
        if ($captcha_success->success == false) {
            $this->presenter->flashMessage('Špatná captcha.');
        } else {
            $this->mailManager->sendContactMail($values);
            $this->presenter->flashMessage('Děkujeme ze zprávu.');
            $form->setValues([], true);
        }
        $this->redrawControl();
    }
}
