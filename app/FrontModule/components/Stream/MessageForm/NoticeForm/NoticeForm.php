<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream\MessageForm\NoticeForm;

use \Nette\Application\UI\Form;
use App\Model\Manager\MessageManager;
use App\Model\Manager\UserManager;
use App\Model\Manager\FileManager;
use App\FrontModule\Components\Stream\MessageForm\MessageForm;


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
        $form->addHidden('messageType', self::TYPE_NOTICE);
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
        $message->idType = self::TYPE_NOTICE;
        
        if(!empty($values['idMessage'])) {
            //TODO - kontrola oprávnění
            $message->id = $values['idMessage'];
        }
        
        $attachments = explode('_', $values['attachments']);

        $idMessage = $this->messageManager->createMessage($message, $attachments);
        $this->presenter->flashMessage('Zpráva uložena', 'success');
        $this->presenter->payload->idMessage = $idMessage;
        $form['text']->setValue("");
        $form['attachments']->setValue("");
        $this->stream->redrawControl('messages');
        $this->redrawControl('messageForm');
        
    }
}
