<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Components\Stream\MessageForm;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\Manager\MessageManager;
use App\Model\Manager\UserManager;
use App\Model\Manager\FileManager;


/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class MessageForm extends Control
{
    
    /**
     *
     * @var MessageManager $messageManager
     */
    private $messageManager;
    private $userManager;
    private $stream;
    private $fileManager;
    
    public function __construct(UserManager $userManager, MessageManager $messageManager, $stream, FileManager $fileManager)
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->stream = $stream;
        $this->fileManager = $fileManager;
    }
    
    protected function createComponentForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->getElementPrototype()->class('ajax');
        $form->addTextArea('text', 'Zpráva')
                ->setAttribute('placeholder', 'Sem napište Vaši zprávu ...')
            ->setRequired('Napište zprávu');

        $form->addHidden('attachments');
        $form->addSubmit('send', 'Publikovat');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/MessageForm.latte');
        // vložíme do šablony nějaké parametry
        //$template->form = $this->form;
        //
        $template->activeUser = $this->userManager->get($this->getPresenter()->user->id);
        // a vykreslíme ji
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $message = new \App\Model\Entities\Message;
        $message->setText($values['text']);
        $message->setUser($this->getPresenter()->getUser());
        $message->idGroup = $this->stream->getActiveGroup()->id;
        
        $attachments = explode('_', $values['attachments']);

    
        $this->messageManager->createMessage($message, $attachments);
        $form['text']->setValue("");
        $form['attachments']->setValue("");
        $this->stream->redrawControl('messages');
        $this->redrawControl('messageForm');
        
    }
    
    public function handleUploadAttachment()
    {
        $file = $this->getPresenter()->request->getFiles();
        $path = 'users/' . $this->getPresenter()->user->getIdentity()->data['URL_ID'] . '/files';
        
        $uploadedFile = $this->fileManager->uploadFile($file['file'], $path);
        $this->getPresenter()->payload->file = $uploadedFile;
        $this->getPresenter()->sendPayload();
    }
}
