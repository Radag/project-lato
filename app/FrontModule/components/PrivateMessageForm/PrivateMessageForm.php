<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\PrivateMessageForm;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\Entities\PrivateMessage;
use App\Model\Manager\PrivateMessageManager;
use App\Model\Manager\UserManager;

class PrivateMessageForm extends Control
{
    /** @var PrivateMessageManager */
    protected $privateMessageManager;
    
    /** @var UserManager */
    protected $userManager;
    
    public function __construct(
        PrivateMessageManager $privateMessageManager,
        UserManager $userManager)
    {
        $this->privateMessageManager = $privateMessageManager;
        $this->userManager = $userManager;
    }

    public function setIdUserTo($idUserTo) 
    {
        $user = $this->userManager->get($idUserTo);
        if($user) {
            $this['form']['idUserTo']->setValue($idUserTo);
            $this['form']['emailTo']->setValue($user->email);
        }
    }
     
    protected function createComponentForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->addTextArea('text', 'Obsah')
             ->setAttribute('placeholder', 'Napište zprávu ..')
             ->setRequired('Prosím napiště text zprávy.');
        $form->addText('emailTo', 'Email uživatele')
             ->setAttribute('placeholder', 'Email uživatele');
        $form->addHidden('idUserTo');
        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = [$this, 'processForm'];
        $form->onValidate[] = function($form) {
            $user = $this->userManager->getUserByMail($form['emailTo']->getValue());
            if(empty($user)) {
                $form->addError('Uživatel s tím emailem neexituje');
            }
        };

        return $form;
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/PrivateMessageForm.latte');
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $message = new PrivateMessage;
        $userTo = $this->userManager->getUserByMail($values->emailTo);        
        $message->text = $values->text;
        $message->idUserFrom = $this->presenter->activeUser->id;
        $message->idUserTo = $userTo->id;
        $this['form']['text']->setValue("");      
        $this->privateMessageManager->insertMessage($message);
        $this->presenter->flashMessage('Zpráva byla odeslána', 'success');
        if($this->presenter->isLinkCurrent('Profile:messages')) {
            $this->presenter->redrawControl('messagesList');
        }
        $this->presenter->redrawControl('right-conversation-list');
    }
}
