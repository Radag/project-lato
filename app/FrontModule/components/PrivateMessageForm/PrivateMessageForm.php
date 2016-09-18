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



/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class PrivateMessageForm extends Control
{
        
    protected $privateMessageManager;
    protected $activeUser;
    
    public function __construct(PrivateMessageManager $privateMessageManager,
                \App\Model\Entities\User $activeUser)
    {
        $this->privateMessageManager = $privateMessageManager;
        $this->activeUser = $activeUser;
        
    }

    public function setIdUserTo($idUserTo) 
    {
        $this['form']['idUserTo']->setValue($idUserTo);
    }
    
    protected function createComponentForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->addTextArea('text', 'Obsah')
             ->setAttribute('placeholder', 'Text zprávy')
             ->setRequired('Prosím napiště text zprávy.');
        $form->addHidden('idUserTo');
        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = [$this, 'processForm'];
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
        $message->text = $values->text;
        $message->idUserFrom = $this->activeUser->id;
        $message->idUserTo = $values->idUserTo;
        $this->privateMessageManager->insertMessage($message);
        $this->presenter->flashMessage('Zpráva byla odeslána', 'success');
        
    }
}
