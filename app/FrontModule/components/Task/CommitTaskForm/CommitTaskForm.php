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
use App\Service\FileService;


class CommitTaskForm extends \App\Components\BaseComponent
{
     /** @var MessageManager */
    private $messageManager;
    
    /** @var UserManager */
    private $userManager;
    
    /** @var FileManager */
    private $fileManager;
    
    /** @var TaskManager */
    private $taskManager;
    
    /** @var FileService */
    private $fileService;
        
    /** @var Task */
    private $task;
      
    public function __construct(UserManager $userManager,
            MessageManager $messageManager, 
            FileManager $fileManager,
            TaskManager $taskManager,            
            FileService $fileService
            )
    {
        $this->userManager = $userManager;
        $this->messageManager = $messageManager;
        $this->fileManager = $fileManager;
        $this->taskManager = $taskManager;
        $this->fileService = $fileService;
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm();
        $form->addTextarea('comment', 'Komentář')
             ->setAttribute('placeholder', 'Komentář k úkolu');
        
        $form->addHidden('attachments');
        $form->addHidden('idTask');
        $form->addHidden('idCommit');
        $form->addSubmit('send', 'Odevzdat');
        
		
        $form->onValidate[] = [$this, 'validateForm'];  
        $form->onSuccess[] = [$this, 'processForm'];  
        return $form;
    }
    
    public function render()
    {
        $this->template->task = $this->task;
        parent::render();
    }
    
    public function setDefault(Task $task)
    {
        $commit = $this->taskManager->getCommitByUser($task->id, $this->presenter->activeUser->id); 
        if($commit) {
            $this['form']->setDefaults(array(
                'comment' => $commit->comment,
                'idCommit' => $commit->idCommit
            ));
            $this->template->attachments = $commit->files;
        } else {
            $this->flashMessage('Tento úkol neexistuje');
            $this->redirect('this');
        }        
    }
             
    public function setTask(Task $task)
    {
        $this->task = $task;
        $this['form']->setValues([
            'idTask' => $task->id
        ]);
        if($task->commit) {
            $this->setDefault($task);
        }
        $this->redrawControl('commitTaskForm');
    }
    
	public function validateForm(Form $form, $values) 
    {
		if(empty($values->comment) && empty($values->attachments)) {
			$form->addError('Musíte zadat buď komentář nebo vložit soubor');
		}		
	}
	
    public function processForm(Form $form, $values) 
    {
        if(!empty($values['idCommit']) && !$this->taskManager->isUserCommit($values['idCommit'], $this->presenter->activeUser)) {
            throw new \Exception('Není vlastník');
        }
        
        $taskCommit = new \App\Model\Entities\TaskCommit();
        $taskCommit->comment = $values->comment;
        $taskCommit->user = $this->presenter->activeUser;
        $taskCommit->idTask = $values->idTask;
        $taskCommit->idCommit = $values->idCommit;
        
        $attachments = explode('_', $values['attachments']);    
        $this->taskManager->createTaskCommit($taskCommit, $attachments);
        
        $this->presenter->flashMessage('Úkol odevzdán', 'success');
        $this->parent->redrawTasks();
    }
    
    public function handleUploadAttachment()
    {
        $file = $this->presenter->request->getFiles();
        try {
            if(empty($file['file'])) { 
                throw new \Lato\FileUploadException("Soubor se nepodařilo nahrát.");
            }            
            $this->presenter->payload->file = $this->fileService->uploadFile($file['file'], FileService::FILE_PURPOSE_COMMIT);
        } catch (\Lato\FileUploadException $ex) {
            $this->presenter->payload->error = $ex->getMessage();
        }
        $this->presenter->sendPayload();
    }
    
    public function handleDeleteAttachment($idFile)
    {
        $this->presenter->payload->ss = $idFile;
        if($this->fileManager->isFileOwner($idFile, $this->presenter->activeUser->id)) {
            $this->taskManager->removeAttachment($idFile);
            $this->fileService->removeFile($idFile);
            $this->presenter->payload->idFile = $idFile;
            $this->presenter->payload->deleted = true;            
        } else {            
            $this->presenter->payload->deleted = false;
        }
        $this->presenter->sendPayload();
    }
}
