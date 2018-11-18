<?php

namespace App\FrontModule\Components\Group;

class Students extends \App\Components\BaseComponent
{    

    /** @var IClassification @inject */
    public $classification;
    
    /** @var IStudentsList @inject */
    public $studentsList;
    
    public function __construct(
        IClassification $classification,
        IStudentsList $studentsList
    )
    {
        $this->classification = $classification;
        $this->studentsList = $studentsList;
    }
    
    public $classGroupId = null;
    
    public function render() 
    {
        $this->template->classGroupId = $this->classGroupId;
        parent::render();
    }
    
    public function createComponentClassification()
    {
        return $this->classification->create();
    }
    
    public function createComponentStudentsList()
    {
        return $this->studentsList->create();
    }
    
    public function showClassification($idGroup, $classificationGroup = null, $redraw = null)
    {
        if($classificationGroup) {
            $this['classification']->setClassification($classificationGroup);
        }
        $this->classGroupId = $idGroup;
        if($redraw !== null && $redraw) {
            $this->redrawControl();
        } elseif($redraw !== null) {
            $this->redirect('this');
        } 
    } 
}
