<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Task;
use Nette\Application\UI\Control;


/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class TaskCard extends Control
{
    protected function getTemplateFilePath()
    {
        $reflection = $this->getReflection();
        $dir = dirname($reflection->getFileName());
        $filename = $reflection->getShortName() . '.latte';

        return $dir . \DIRECTORY_SEPARATOR . $filename;
    }

    public function render($task) {
        $this->template->activeUser = $this->presenter->activeUser;
        $this->template->task = $task;
        $this->template->setFile($this->getTemplateFilePath());
        $this->template->render();
    }
    
   
}
