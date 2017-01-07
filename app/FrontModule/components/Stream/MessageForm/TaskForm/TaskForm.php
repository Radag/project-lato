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
use App\Model\Manager\TaskManager;
use App\Model\Entities\Task;

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
        
        $form->addHidden('attachments');
        $form->addHidden('messageType', self::TYPE_TASK);
        $form->addSubmit('send', 'Publikovat');
        
        if($this->defaultMessage !== null) {
            $form->setDefaults(array(
                'text' => $this->defaultMessage->text,
                'idMessage', $this->defaultMessage->id
            ));
        }

        return $form;
    }
    
    public function render()
    {
        parent::render();
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
        $message->idType = self::TYPE_TASK;
        $attachments = explode('_', $values['attachments']);

        if(!empty($values['idMessage'])) {
            //TODO - kontrola oprávnění
            $message->id = $values['idMessage'];
        }
        
        $idMessage = $this->messageManager->createMessage($message, $attachments);
        
        $task = new Task();
        $task->idMessage = $idMessage;
        $task->online = false;
        $task->title = $values->title;
        
        $deadline = $date = \DateTime::createFromFormat('d. m. Y H:i', $values->date . " " . $values->time);
        $task->deadline = $deadline;
        
        $this->taskManager->createTask($task);
      
    
        $form['text']->setValue("");
        $form['attachments']->setValue("");
        $this->presenter->payload->idMessage = $idMessage;
        $this->presenter->flashMessage('Připomenutí bylo vytvořeno.', 'success');
        $this->stream->redrawControl('messages');
        $this->redrawControl('messageForm');
        
    }
}
