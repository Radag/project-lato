<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream;

use App\Model\Manager\TaskManager;
use App\Model\Manager\ClassificationManager;

class RightColumn extends \App\Components\BaseComponent
{
    /** @var  TaskManager @inject */
    protected $taskManager;
    
    /** @var  ClassificationManager @inject */
    protected $classificationManager;
    
    protected $showDeleted = false;
    
    public function __construct(
        TaskManager $taskManager,
        ClassificationManager $classificationManager
    )
    {
        $this->taskManager = $taskManager;
        $this->classificationManager = $classificationManager;
    }
        
    
    public function render() {
        $this->template->lastClassificationChange = $this->classificationManager->getLastChange($this->presenter->activeUser->id, $this->presenter->activeGroup->id);
        $this->template->actualTasks = $this->taskManager->getClosestTask(array($this->presenter->activeGroup->id => $this->presenter->activeGroup));
        parent::render();
    }
   
    public function handleChangeFilter($filter) 
    {   
        $this->parent['messagesColumn']->setFilter($filter);
    }
    
    protected function createComponentStreamSettingsForm()
    {
        $form = new \Nette\Application\UI\Form;

        $form->setMethod('get');
        $form->addCheckbox('showDeleted','Zobrazit smazané položky', [true, false])
             ->setDefaultValue($this->showDeleted);
        $form->onSuccess[] = function($form, $values) {
            $this->parent['messagesColumn']->showDeleted($values->showDeleted); 
        };
        return $form;        
    }
}
