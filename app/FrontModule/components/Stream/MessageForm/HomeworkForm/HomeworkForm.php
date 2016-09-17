<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream\MessageForm\HomeworkForm;

use \Nette\Application\UI\Form;
use App\FrontModule\Components\Stream\MessageForm\MessageForm;
use App\Model\Manager\MessageManager;
use App\Model\Manager\UserManager;
use App\Model\Manager\FileManager;
use App\Model\Manager\TaskManager;
use App\Model\Entities\Task;


/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class HomeworkForm extends MessageForm
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
    
    
    protected function createComponentForm()
    {
        $form = parent::getFormTemplate();
        $form->addText('title', 'Název')
             ->setAttribute('placeholder', 'Název (nepovinné)');
        $form->addText('date', 'Datum', null, 12)
             ->setRequired('Vložte datum')
             ->addRule(Form::PATTERN, 'Datum musí být ve formátu 15. 10. 2011', '([0-9]{2})\. ([0-9]{2})\. ([0-9]{4})')
             ->setAttribute('type', 'date')
             ->setAttribute('placeholder', date('d. m. Y'));
        $form->addText('time', 'Čas', null, 5)
             ->setRequired('Vložte čas')
             ->addRule(Form::PATTERN, 'Čas musí být ve formátu 12:45', '([0-9]{2})\:([0-9]{2})')
             ->setAttribute('placeholder', date('H:i'));
        $form->addCheckbox('online', "Odevzdat online");
        
        $form->addHidden('attachments');
        $form->addHidden('messageType', self::TYPE_HOMEWORK);
        $form->addSubmit('send', 'Publikovat');

        return $form;
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/HomeworkForm.latte');
        $template->activeUser = $this->activeUser;
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $message = new \App\Model\Entities\Message;
        $message->setText($values['text']);
        $message->setUser($this->activeUser);
        $message->idGroup = $this->stream->getActiveGroup()->id;
        $message->idType = self::TYPE_HOMEWORK;
        $attachments = explode('_', $values['attachments']);
        $idMessage = $this->messageManager->createMessage($message, $attachments);
        
        $task = new Task();
        $task->idMessage = $idMessage;
        $task->online = $values->online;
        $task->title = $values->title;
        
        $deadline = $date = \DateTime::createFromFormat('d. m. Y H:i', $values->date . " " . $values->time);
        $task->deadline = $deadline;
        
        $this->taskManager->createTask($task);        
        $this->presenter->payload->idMessage = $idMessage;
        $this->presenter->flashMessage('Domácí úkol byl zadán.', 'success');
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
