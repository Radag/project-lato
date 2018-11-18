<?php

namespace App\FrontModule\Components\Stream;

use App\Model\Manager\TaskManager;
use App\Model\Manager\ClassificationManager;
use App\Model\Manager\GroupManager;

class RightColumn extends \App\Components\BaseComponent
{
    /** @var  TaskManager @inject */
    protected $taskManager;
    
    /** @var  ClassificationManager @inject */
    protected $classificationManager;
    
    /** @var  GroupManager @inject */
    protected $groupManager;
    
    public function __construct(
        TaskManager $taskManager,
        ClassificationManager $classificationManager,
        GroupManager $groupManager
    )
    {
        $this->taskManager = $taskManager;
        $this->classificationManager = $classificationManager;
        $this->groupManager = $groupManager;
    }        
    
    public function render() {
        $this->template->relation = $this->presenter->activeGroup->relation;
        $this->template->lastClassificationChange = $this->classificationManager->getLastChange($this->presenter->activeUser->id, $this->presenter->activeGroup->id);
        $this->template->actualTasks = $this->taskManager->getClosestTask([$this->presenter->activeGroup->id => $this->presenter->activeGroup], true, $this->presenter->activeUser);
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
             ->setDefaultValue($this->presenter->activeGroup->showDeleted);
        $form->onSuccess[] = function($form, $values) {
            $this->groupManager->setDeleted($this->presenter->activeGroup, $values->showDeleted);
            $this->presenter->activeGroup->showDeleted = $values->showDeleted;
            $this->parent['messagesColumn']->redrawControl();
        };
        return $form;        
    }
}
