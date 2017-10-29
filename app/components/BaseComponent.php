<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Components;

use Nette\Application\UI\Control;

/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class BaseComponent extends Control
{
    
    protected function getForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->onError[] = function($form) {
            foreach($form->errors as $error) {
                $this->presenter->flashMessage($error, 'error');
            }
        };
        return $form;
    }
    
    protected function getTemplateFilePath()
    {
        $reflection = $this->getReflection();
        $dir = dirname($reflection->getFileName());
        $filename = $reflection->getShortName() . '.latte';

        return $dir . \DIRECTORY_SEPARATOR . $filename;
    }

    public function render()
    {
        $this->template->setFile($this->getTemplateFilePath());
        $this->template->render();
    }
}
