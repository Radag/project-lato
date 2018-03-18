<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream\MessageForm;

use \Nette\Application\UI\Form;
use App\Model\Manager\MessageManager;
use App\Model\Manager\UserManager;
use App\Model\Manager\FileManager;
use App\Model\Entities\Message;
use App\Model\Manager\TaskManager;
use App\Model\Manager\ClassificationManager;
use App\Model\Manager\GroupManager;

/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class MessageForm extends \App\Components\BaseComponent
{
    
    /** @var MessageManager */
    protected $messageManager;
    
    /** @var UserManager */
    protected $userManager;
    
    /** @var FileManager $fileManager */
    protected $fileManager;
    
    /** @var TaskManager $taskManager*/
    protected $taskManager;
    
    /** @var ClassificationManager **/
    protected $classificationManager;
    
     /** @var GroupManager **/
    protected $groupManager;
    
    /** @var Message */
    protected $defaultMessage = null;
    
    public function __construct(UserManager $userManager,
        MessageManager $messageManager, 
        FileManager $fileManager,
        TaskManager $taskManager,
        ClassificationManager $classificationManager,
        GroupManager $groupManager
    )
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->fileManager = $fileManager;
        $this->taskManager = $taskManager;
        $this->classificationManager = $classificationManager;
        $this->groupManager = $groupManager;
    }
  
    public function createComponentForm()
    {   
        $form = $this->getForm();
        $form->getElementPrototype()->class('ajax');
        $form->addTextArea('text', 'Zpráva')
            ->setRequired('Napište zprávu');
        $form->addHidden('idMessage');
        $form->addHidden('messageType', Message::TYPE_NOTICE);
        $form->addText('title', 'Název')
             ->addConditionOn($form['messageType'], Form::EQUAL, Message::TYPE_TASK)
                 ->setRequired('Vložte název');
        $form->addText('date', 'Datum', null, 12)
             ->setAttribute('type', 'date')
             ->setAttribute('placeholder', date('d. m. Y'))
             ->setValue(date("Y-m-d"))
             ->addConditionOn($form['messageType'], Form::EQUAL, Message::TYPE_TASK)
                 ->setRequired('Vložte datum')
                 ->addRule(Form::PATTERN, 'Datum musí být ve formátu 2011-10-15', '([0-9]{4})\-([0-9]{2})\-([0-9]{2})');
        $form->addText('time', 'Čas', null, 5)
             ->setAttribute('placeholder', date('H:i'))
             ->setAttribute('type', 'time')
             ->setValue(date('H:i'))
             ->addConditionOn($form['messageType'], Form::EQUAL, Message::TYPE_TASK)
                 ->setRequired('Vložte čas')
                 ->addRule(Form::PATTERN, 'Čas musí být ve formátu 12:45', '([0-9]{2})\:([0-9]{2})');
        $form->addCheckbox('online', "Studenti odevzdají práci online");
        $form->addCheckbox('create_classification', "Povinnost bude známkována");
        $form->addHidden('attachments');
        $form->addSubmit('send', 'Publikovat');
        if($this->defaultMessage !== null) {
            $form->setDefaults(array(
                'text' => $this->defaultMessage->text,
                'idMessage' => $this->defaultMessage->id,
                'messageType' => $this->defaultMessage->type,
                'title' => $this->defaultMessage->title
            ));
            if($this->defaultMessage->task) {
                $form->setDefaults(array(
                    'title' => $this->defaultMessage->task->title,
                    'date' => $this->defaultMessage->task->deadline->format('Y-m-d'),
                    'time' => $this->defaultMessage->task->deadline->format('H:i'),
                    'online' => $this->defaultMessage->task->online,
                    'create_classification' => $this->defaultMessage->task->createClassification
                )); 
            }
        }

        $form->onSuccess[] = [$this, 'processForm'];
          
        $form->onValidate[] = function($form) {
            if(!in_array($form['messageType']->getValue(), [Message::TYPE_MATERIALS, Message::TYPE_NOTICE, Message::TYPE_TASK])) {
                $form->addError('Takový typ nelze zadat.');
            }
        };
        return $form;
    }    
    
    
     public function processForm(Form $form, $values) 
    {
        $message = new \App\Model\Entities\Message;
        $message->text = $values->text;
        $message->user = $this->presenter->activeUser;
        $message->idGroup = $this->presenter->activeGroup->id;
        $message->type = $values->messageType;
        
        if(!empty($values->idMessage)) {
            //TODO - kontrola oprávnění
            $message->id = $values->idMessage;
        }
        
        $attachments = explode('_', $values->attachments);

        $message->id = $this->messageManager->createMessage($message, $attachments, $this->presenter->activeGroup);
        
        if($values->messageType === Message::TYPE_TASK) {
            $task = new \App\Model\Entities\Task();
            $task->idMessage = $message->id;
            $task->online = $values->online ? 1 : 0;
            $task->title = $values->title;
            $deadline = $date = \DateTime::createFromFormat('Y-m-d H:i', $values->date . " " . $values->time);
            $task->deadline = $deadline;
            $task->create_classification = $values->create_classification;
            $task->id = $this->taskManager->createTask($task);
            $this->classificationManager->updateTaskClassification($task, $this->presenter->activeGroup, $this->groupManager);
        }
        
        if($values->messageType === Message::TYPE_MATERIALS) {
            $message->title = $values->title;
            $this->messageManager->createMaterial($message);
        }

        $this->presenter->flashMessage('Zpráva uložena', 'success');
        $this->presenter->payload->idMessage = $message->id;
        $this->handleResetForm();
        $this->redrawControl('messageForm');
        $this->parent->redrawControl('streamSection');
    }
    
    public function handleResetForm($type = Message::TYPE_NOTICE) {
        $this['form']->setValues([
            'messageType' => $type,
            'time' => date("H:i"),
            'date' => date("Y-m-d")
         ], true);
    }
    
    
    public function render()
    {
        if($this->defaultMessage !== null) {
            if($this->defaultMessage->attachments) {
                $this->template->attachments = array_merge($this->defaultMessage->attachments['files'], $this->defaultMessage->attachments['media']);
            }
            
            $this->template->submitButtonName = 'Upravit';
        } else {
            $this->template->submitButtonName = 'Publikovat';
        }
        
        $this->template->activeUser = $this->presenter->activeUser;
        $this->template->defaultMessage = $this->defaultMessage;
        parent::render();
    }
    
    public function setDefaults(Message $message)
    {
        $this->defaultMessage = $message;
    }
    
    public function handleUploadAttachment()
    {
        $file = $this->getPresenter()->request->getFiles();
        if($file['file']->getSize() < 3000000) {
            $path = 'users/' . $this->presenter->activeUser->slug . '/files';       
            $uploadedFile = $this->fileManager->uploadFile($file['file'], $path);
            $this->getPresenter()->payload->file = $uploadedFile;
        } else {
            $this->getPresenter()->payload->message = 'Soubor nesmí být větší než 3Mb.';
            $this->getPresenter()->payload->error = true;
        }
               
        $this->getPresenter()->sendPayload();
    }
    
    public function handleDeleteAttachment($idFile)
    {
        $this->fileManager->removeFile($idFile);
        $this->getPresenter()->payload->deleted = true;
        $this->getPresenter()->sendPayload();
    }
}
