<?php

namespace App\Components;

use Nette\Application\UI\Control;
use App\Helpers\HelpersList;

class BaseComponent extends Control
{
    
    private $templateName = null;
    
    

    public function render()
    {
        $this->template->addFilter('timeDifferceText', function($timeLeft) {
            return HelpersList::timeDifferceText($timeLeft);
        });
        $this->template->addFilter('attachTypeIco', function($type) {
            return HelpersList::attachTypeIco($type);
        });
        $this->template->addFilter('inputErrors', function($input) {
            return \App\Helpers\HelpersList::inputErrors($input);
        });
        if($this->templateName === null) {
            $this->template->setFile($this->getTemplateFilePath());
        } else {
            $this->template->setFile($this->getTemplateFilePath($this->templateName));
        }       
        $this->template->render();
    }
    
    public function setTemplateName($name)
    {
        $this->templateName = $name;
    }
    
    protected function getForm($flashError = true)
    {
        $form = new \Nette\Application\UI\Form;
        //$form->addProtection();
        if($flashError) {
           $form->onError[] = function($form) {
                foreach($form->errors as $error) {
                    $this->presenter->flashMessage($error, 'error');
                }
                $this->presenter->payload->invalidForm = true;
            }; 
        } else {
            $form->onError[] = function($form) {
                $this->presenter->payload->invalidForm = true;    
                $this->redrawControl();
            };
        }
        return $form;
    }
    
    protected function getTemplateFilePath($name = null)
    {
        $reflection = $this->getReflection();
        $dir = dirname($reflection->getFileName());
        $filename = $reflection->getShortName() . '.latte';
        if($name) {
            return $dir . \DIRECTORY_SEPARATOR . $name . '.latte';
        } else {
            return $dir . \DIRECTORY_SEPARATOR . $filename;
        }       
    }
}
