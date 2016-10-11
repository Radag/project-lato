<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\NewNoticeForm;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\Entities\Notice;
use App\Model\Manager\NoticeManager;



/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class NewNoticeForm extends Control
{
        
    protected $noticeManager;
    protected $activeUser;
    
    public function __construct(NoticeManager $noticeManager,
                \App\Model\Entities\User $activeUser)
    {
        $this->noticeManager = $noticeManager;
        $this->activeUser = $activeUser;
    }
    
    protected function createComponentForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->addTextArea('text', 'Obsah')
             ->setAttribute('placeholder', 'poznámka ...')
             ->setRequired('Prosím napiště poznámku');
        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/NewNoticeForm.latte');
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $notice = new Notice;
        $notice->text = $values->text;
        $notice->user = $this->activeUser->id;
        $this->noticeManager->insertNotice($notice);
        $this->presenter->flashMessage('Poznámka vložena', 'success');
        
    }
}
