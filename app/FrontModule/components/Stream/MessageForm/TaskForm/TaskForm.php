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
        $form = new \Nette\Application\UI\Form;
        $form->getElementPrototype()->class('ajax');
        $form->addTextArea('text', 'Zpráva')
                ->setAttribute('placeholder', 'Sem napište Vaši zprávu ...')
            ->setRequired('Napište zprávu');

        $form->addText('title', 'Název')
             ->setAttribute('placeholder', 'Název (nepovinné)');
        $form->addText('date', 'Datum', null, 12)
             ->setAttribute('type', 'date')
             ->setAttribute('placeholder', '2. 9. 2016');
        $form->addText('time', 'Čas', null, 5)
             ->setAttribute('placeholder', '23:50');
        
        
        $form->addHidden('attachments');
        $form->addHidden('messageType', self::TYPE_TASK);
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

        $idMessage = $this->messageManager->createMessage($message, $attachments);
        
        $task = new Task();
        $task->idMessage = $idMessage;
        $task->online = false;
        $task->title = $values->title;
        $task->deadline = $values->date;
        
        $this->taskManager->createTask($task);
      
    
        $form['text']->setValue("");
        $form['attachments']->setValue("");
        $this->stream->redrawControl('messages');
        $this->redrawControl('messageForm');
        
    }
}