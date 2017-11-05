<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream\MessageForm\NoticeForm;

use \Nette\Application\UI\Form;
use App\Model\Entities\Task;
use App\FrontModule\Components\Stream\MessageForm\MessageForm;
use App\Model\Entities\Message;

/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class NoticeForm extends MessageForm
{  
    protected function createComponentForm()
    {
        $form = parent::getFormTemplate();
        $form->addText('title', 'Název')
             ->setAttribute('placeholder', 'Název (nepovinné)');
        $form->addHidden('messageType', Message::TYPE_NOTICE);
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
        $form->addCheckbox('online', "Odevzdat online");
        $form->addHidden('attachments');
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
        $template->setFile(__DIR__ . '/NoticeForm.latte');
        $template->activeUser = $this->activeUser;
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $message = new \App\Model\Entities\Message;
        $message->setText($values['text']);
        $message->setUser($this->activeUser);
        $message->idGroup = $this->stream->getActiveGroup()->id;
        $message->type = $values['messageType'];
        
        if(!empty($values['idMessage'])) {
            //TODO - kontrola oprávnění
            $message->id = $values['idMessage'];
        }
        
        $attachments = explode('_', $values['attachments']);

        $idMessage = $this->messageManager->createMessage($message, $attachments);
        
        if($values->messageType === Message::TYPE_TASK) {
            $task = new Task();
            $task->idMessage = $idMessage;
            $task->online = false;
            $task->title = $values->title;
            $deadline = $date = \DateTime::createFromFormat('Y-m-d H:i', $values->date . " " . $values->time);
            $task->deadline = $deadline;

            $this->taskManager->createTask($task);  
        }

        $this->presenter->flashMessage('Zpráva uložena', 'success');
        $this->presenter->payload->idMessage = $idMessage;
        $this->handleResetForm();
        $this->redrawControl('messageForm');
        
    }
    
    public function handleResetForm() {
        $this['form']->setValues([
            'messageType' => Message::TYPE_NOTICE,
            'time' => date("H:i"),
            'date' => date("Y-m-d")
         ], true);
        $this->stream->redrawControl('messages');
    }
}
