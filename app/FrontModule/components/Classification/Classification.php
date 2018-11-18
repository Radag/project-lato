<?php

namespace App\FrontModule\Components;

use App\Model\Manager\ClassificationManager;

class Classification extends \App\Components\BaseComponent
{

    /** @var ClassificationManager */
    public $classificationManager;

    public function __construct(
        ClassificationManager $classificationManager
    )
    {
        $this->classificationManager = $classificationManager;
    }
    
    public function render() 
    {
        $this->template->myClassification = $this->classificationManager->getMyClassification($this->presenter->activeUser);
        parent::render();
    }    
}
