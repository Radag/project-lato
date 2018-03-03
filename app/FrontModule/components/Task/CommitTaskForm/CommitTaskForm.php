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


class CommitTaskForm extends \App\Components\BaseComponent
{
     /** @var MessageManager */
    protected $messageManager;
    
    /** @var UserManager */
    protected $userManager;
    
    /** @var FileManager */
    protected $fileManager;
    
    /** @var TaskManager */
    protected $taskManager;
      
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
        $form = $this->getForm();
        $form->addTextarea('comment', 'Komentář')
             ->setAttribute('placeholder', 'Komentář k úkolu');
        
        $form->addHidden('attachments');
        $form->addHidden('idTask');
        $form->addHidden('idCommit');
        $form->addSubmit('send', 'Odevzdat');
        
        $form->onSuccess[] = [$this, 'processForm'];  
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
             
    public function setTask($task)
    {
        $this['form']->setValues([
            'idTask' => $task->id
        ]);
        if($task->commit) {
            $this->setDefault($task->commit);
        }
        $this->redrawControl('commitTaskForm');
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
        $idTaskCommit = $this->taskManager->createTaskCommit($taskCommit, $attachments);
        
        $this->presenter->flashMessage('Úkol odevzdán', 'success');
        $this->presenter->redrawTasks();
    }
    
    public function handleUploadAttachment()
    {
        $file = $this->getPresenter()->request->getFiles();
        $path = 'users/' . $this->presenter->activeUser->slug . '/files';
        
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
