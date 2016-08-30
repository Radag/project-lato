<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream\MessageForm\TaskForm;

use \Nette\Application\UI\Form;
use App\FrontModule\Components\Stream\MessageForm\MessageForm;
use App\Model\Manager\MessageManager;
use App\Model\Manager\UserManager;
use App\Model\Manager\FileManager;


/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class TaskForm extends MessageForm
{

    
    /**
     * @var MessageManager $messageManager
     */
    protected $messageManager;
    
    /**
     * @var UserManager $userManager
     */
    protected $userManager;
    
    /**
     * @var \App\FrontModule\Components\Stream\Stream $stream 
     */
    protected $stream;
    
    /**
     * @var FileManager $fileManager
     */
    protected $fileManager;
    
     /**
     * @var \App\Model\Entities\User $activeUser
     */
    protected $activeUser;
    
    public function __construct(UserManager $userManager, MessageManager $messageManager, FileManager $fileManager)
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
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
        $template->setFile(__DIR__ . '/TaskForm.latte');
        $template->activeUser = $this->activeUser;
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $message = new \App\Model\Entities\Message;
        $message->setText($values['text']);
        $message->setUser($this->activeUser);
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
        $path = 'users/' . $this->activeUser->urlId . '/files';
        
        $uploadedFile = $this->fileManager->uploadFile($file['file'], $path);
        $this->getPresenter()->payload->file = $uploadedFile;
        $this->getPresenter()->sendPayload();
    }
    
    public function handleDeleteAttachment($idFile)
    {
        $this->fileManager->removeFile($idFile);
        $this->getPresenter()->payload->deleted = true;
        $this->getPresenter()->sendPayload();
    }
}
