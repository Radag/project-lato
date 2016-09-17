<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream\MessageForm\MaterialsForm;

use \Nette\Application\UI\Form;
use App\Model\Manager\MessageManager;
use App\Model\Manager\UserManager;
use App\Model\Manager\FileManager;
use App\Model\Manager\MaterialManager;
use App\FrontModule\Components\Stream\MessageForm\MessageForm;

/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class MaterialsForm extends MessageForm
{
    
    protected $materialManager;
    
    public function __construct(UserManager $userManager,
            MessageManager $messageManager, 
            FileManager $fileManager,
            MaterialManager $materialManager)
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->fileManager = $fileManager;
        $this->materialManager = $materialManager;
    }
    
    protected function createComponentMaterialForm()
    {
        $form = parent::getFormTemplate();
        $form->addText('title', 'Název')
             ->setAttribute('placeholder', 'Název (nepovinné)');

        $form->addHidden('messageType', self::TYPE_MATERIALS);
        $form->addHidden('attachments');
        $form->addSubmit('send', 'Publikovat');
        return $form;
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/MaterialsForm.latte');
        $template->activeUser = $this->activeUser;
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $message = new \App\Model\Entities\Message();
        $message->setText($values['text']);
        $message->setUser($this->activeUser);
        $message->idGroup = $this->stream->getActiveGroup()->id;
        $message->idType = self::TYPE_MATERIALS;
        
        $attachments = explode('_', $values['attachments']);    
        $idMessage = $this->messageManager->createMessage($message, $attachments);
        
        $material = new \App\Model\Entities\Material();
        $material->title = $values->title;
        $material->idMessage = $idMessage;
        
        $this->materialManager->createMaterial($material);
        $this->presenter->flashMessage('Materiál vložen', 'success');
        $this->presenter->payload->idMessage = $idMessage;
        $form['text']->setValue("");
        $form['attachments']->setValue("");
        $this->stream->redrawControl('messages');
        $this->redrawControl('messageForm');
        
    }
}
