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
        $this->mailManager->sendContactMail($values);
        $this->presenter->flashMessage('Děkujeme ze zprávu.');
        $form->setValues([], true);
        $this->redrawControl();
    }
}
