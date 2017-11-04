<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream\MessageForm;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\Manager\MessageManager;
use App\Model\Manager\UserManager;
use App\Model\Manager\FileManager;
use App\Model\Entities\Message;
use App\Model\Manager\TaskManager;

/**
 * Description of SignInForm
 *
 * @author Radaq
 */
abstract class MessageForm extends Control
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
     * @var Message $message 
     */
    protected $defaultMessage = null;
    
    /**
     * @var TaskManager $taskManager
    */
    protected $taskManager;
    
     /**
     * @var \App\Model\Entities\User $activeUser
     */
    protected $activeUser;
    
    public function __construct(UserManager $userManager,
            MessageManager $messageManager, 
            FileManager $fileManager,
            TaskManager $taskManager
            )
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->fileManager = $fileManager;
        $this->taskManager = $taskManager;
    }
    
    public function setActiveUser(\App\Model\Entities\User $user)
    {
        $this->activeUser = $user;
    }
    
    public function setStream($stream)
    {
        $this->stream = $stream;
    }
  
    public function getFormTemplate()
    {   
        $form = new \Nette\Application\UI\Form;
        $form->getElementPrototype()->class('ajax');
        $form->addTextArea('text', 'Zpráva')
                ->setAttribute('placeholder', 'Sem napište Vaši zprávu ...')
            ->setRequired('Napište zprávu');
        $form->addHidden('idMessage');      

        $form->onSuccess[] = [$this, 'processForm'];
          
        $form->onValidate[] = function($form) {
            if(!in_array($form['messageType']->getValue(), [Message::TYPE_MATERIALS, Message::TYPE_NOTICE, Message::TYPE_TASK])) {
                $form->addError('Takový typ nelze zadat.');
            }
        };
        
        $form->onError[] = function(Form $form) {
            $this->presenter->payload->invalidForm = true;
            foreach($form->getErrors() as $error) {
                $this->presenter->flashMessage($error, 'error');
            }            
        };
        return $form;
    }    
    
    public function render()
    {
        if($this->defaultMessage !== null) {
            $this->template->attachments = array_merge($this->defaultMessage->attachments['files'], $this->defaultMessage->attachments['media']);
            $this->template->submitButtonName = 'Upravit';
        } else {
            $this->template->submitButtonName = 'Publikovat';
        }
    }
    
    public function setDefaults(Message $message)
    {
        $this->defaultMessage = $message;
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
