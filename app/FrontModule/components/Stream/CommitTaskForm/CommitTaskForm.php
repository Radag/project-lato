<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream;

use App\Model\Manager\MessageManager;
use App\Model\Manager\UserManager;
use App\Model\Manager\FileManager;
use App\Model\Manager\TaskManager;
use App\Model\Entities\Task;
use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\Entities\TaskCommit;


/**
 * Description of CommitTaskForm
 *
 * @author Radaq
 */
class CommitTaskForm extends Control
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
        $form = new Form;
        $form->addTextarea('comment', 'Komentář')
             ->setAttribute('placeholder', 'Komentář k úkolu');
        
        $form->addHidden('attachments');
        $form->addHidden('idTask');
        $form->addHidden('idCommit');
        $form->addSubmit('send', 'Odevzdat');
        
        $form->onSuccess[] = [$this, 'processForm'];
        
        $form->onError[] = function(Form $form) {
            $this->presenter->payload->invalidForm = true;
            foreach($form->getErrors() as $error) {
                $this->presenter->flashMessage($error, 'error');
            }            
        };
        
        return $form;
    }
    
    public function setDefault(TaskCommit $commit)
    {
        $this['form']->setDefaults(array(
            'comment' => $commit->comment,
            'idCommit' => $commit->idCommit
        ));
        $this->template->attachments = $commit->files;
    }
            
    
    public function setTaskId($idTask)
    {
        $this['form']->setValues(array('idTask' => $idTask));
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/CommitTaskForm.latte');
        $template->render();
    }
    
    public function processForm(Form $form, $values) 
    {
        $taskCommit = new \App\Model\Entities\TaskCommit();
        $taskCommit->comment = $values['comment'];
        $taskCommit->user = $this->activeUser;
        $taskCommit->idTask = $values['idTask'];
        $taskCommit->idCommit = $values['idCommit'];
        
        $attachments = explode('_', $values['attachments']);    
        $idTaskCommit = $this->taskManager->createTaskCommit($taskCommit, $attachments);
        
        $this->presenter->flashMessage('Úkol odevzdán', 'success');
        
        $this->presenter->redrawTasks();
    }
    
    
    public function setActiveUser(\App\Model\Entities\User $user)
    {
        $this->activeUser = $user;
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
        $this->taskManager->removeAttachment($idFile);
        $this->fileManager->removeFile($idFile);
        $this->getPresenter()->payload->idFile = $idFile;
        $this->getPresenter()->payload->deleted = true;
        $this->getPresenter()->sendPayload();
    }
}
