<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Group;


class Students extends \App\Components\BaseComponent
{    

    /** @var IClassificationFactory @inject */
    public $classification;
    
    /** @var IStudentsListFactory @inject */
    public $studentsList;
    
    public function __construct(
        IClassificationFactory $classification,
        IStudentsListFactory $studentsList
    )
    {
        $this->classification = $classification;
        $this->studentsList = $studentsList;
    }
    
    /** @persistent */
    public $classGroupId;
    
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
    
    public function showClassification($idGroup, $classificationGroup = null)
    {
        if($classificationGroup) {
            $this['classification']->setClassification($classificationGroup);
        }
        $this->classGroupId = $idGroup;
        $this->redrawControl();
    } 
}
