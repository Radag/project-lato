<?php
namespace App\FrontModule\Components;

use App\Model\Manager\FileManager;

class Storage extends \App\Components\BaseComponent
{
    /** @var FileManager $fileManager */
    public $fileManager;
    

    
    public function __construct(
        FileManager $fileManager
    )
    {
        $this->fileManager = $fileManager;
    }
    
    public function render()
    { 
        $storage = $this->fileManager->getUserFiles($this->presenter->activeUser->id);
        $this->template->total = $storage->total;
        $this->template->files = $storage->files;
        parent::render();
    }
    
    public function handleDelete($idFile)
    {
        if($this->fileManager->isFileOwner($idFile, $this->presenter->activeUser->id)) {
            $this->fileManager->removeFile($idFile);
        } else {
            $this->presenter->flashMessage('Soubor neexituje');
        }
        $this->redirect('this');
    }        
    
}
