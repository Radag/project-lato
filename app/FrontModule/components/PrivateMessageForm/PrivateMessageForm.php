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

class PrivateMessageForm extends \App\Components\BaseComponent
{
    /** @var PrivateMessageManager */
    protected $privateMessageManager;
    
    /** @var UserManager */
    protected $userManager;
    
    //protected $showMessageForm = false;
    
    public function __construct(
        PrivateMessageManager $privateMessageManager,
        UserManager $userManager)
    {
        $this->privateMessageManager = $privateMessageManager;
        $this->userManager = $userManager;
    }

    /*
    public function setIdUserTo($idUserTo) 
    {
        $user = $this->userManager->get($idUserTo);
        if($user) {
            $this['form']['idUserTo']->setValue($idUserTo);
            $this['form']['emailTo']->setValue($user->email);
        }
    }
     * 
     */
     
    protected function createComponentUsersForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->addText('user', 'Jméno nebo e-mail uživatele');
        $form->addHidden('users');
        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = function(Form $form, $values) {
            $attenders = $this->userManager->getMultiple(explode(',', $values->users), false);
            $ids = [];
            foreach($attenders as $att) {
                $ids[] = $att->slug;
            }
            $this->presenter->redirect(':Front:Conversation:default', ['users' => implode(',', $ids)]);
        };
        return $form;
    }
    
    public function handleSearchUsers()
    {
        $term = $this->presenter->getParameter('term');
        $userList = $this->userManager->searchGroupUser($term, [$this->presenter->activeUser->id]);
        if(empty($userList)) {
            $userList = [];
        }
        $this->template->userList = $userList;
        $this->redrawControl('users-list');
    }
    
    /*
    protected function createComponentForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->addTextArea('text', 'Obsah')
             ->setAttribute('placeholder', 'Napište zprávu ..')
             ->setRequired('Prosím napiště text zprávy.');
        $form->addText('emailTo', 'Email uživatele')
             ->setAttribute('placeholder', 'Email uživatele');
        $form->addHidden('users');
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
     * 
     */
    
  
    
    /*
    public function render()
    {
        $this->template->showMessageForm = $this->showMessageForm;
        parent::render();
    }
     * 
     */
    
    
    
    
    /*
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
    *
    */
}
