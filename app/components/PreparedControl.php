<?php

namespace App\Components;

use Nette\Application\UI\Control;
use App\Helpers\HelpersList;

class PreparedControl extends Control
{
    /**
     * @return ITemplate
     */
    protected function createTemplate()
    {
        $template = parent::createTemplate();
        
        $template->addFilter('timeDifferceText',function($timeLeft) {
            return HelpersList::timeDifferceText($timeLeft);
        });
        
        //if ($this->autoSetupTemplateFile) $template->setFile($this->getTemplateFilePath());
       // $template->registerHelperLoader('HelpersList::loader');
        return $template;
    }
    
}