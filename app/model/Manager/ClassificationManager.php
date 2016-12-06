<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use Nette;
use App\Model\Entities\Classification;
use App\Model\Entities\ClassificationGroup;

/**
 * Description of TaskManager
 *
 * @author Radaq
 */
class ClassificationManager extends BaseManager 
{     
    public function createClassification(Classification $classification)
    {
        $insert = false;
        $this->database->beginTransaction();
        if($classification->idClassificationGroup !== null) {
            $idClassification = $this->database->query("SELECT ID_CLASSIFICATION FROM classification WHERE ID_CLASSIFICATION_GROUP=? AND ID_USER=?", $classification->idClassificationGroup, $classification->user->id)->fetchField();
            if($idClassification) {
                $data = array('NOTICE' => $classification->notice, 'GRADE' => $classification->grade);
                $this->database->query("UPDATE classification SET ? WHERE ID_CLASSIFICATION=?", $data, $idClassification);
            } else {
                $insert = true;
            }           
        } else {
            $insert = true;
        }
        
        if($insert) {
            $this->database->table('classification')->insert(array(
                'ID_USER' => $classification->user->id,
                'ID_GROUP' => $classification->group->id,
                'NAME' => $classification->name,
                'ID_CLASSIFICATION_GROUP' => $classification->idClassificationGroup,
                'NOTICE' => $classification->notice,
                'CREATED_WHEN' => new \DateTime(),
                'CREATED_BY' => $this->user->id,
                'GRADE' => $classification->grade
            ));   
        }
             
        $this->database->commit();
    }
    
    public function getUserClassification($idUser, $idGroup)
    {
        $classificationsArray = array();
        $query = "SELECT * FROM vw_classification
                  WHERE (ID_USER=? OR ID_USER IS NULL) AND ID_GROUP = ?
                  AND (CLG NOT IN (SELECT ID_CLASSIFICATION_GROUP FROM classification WHERE ID_USER=? AND ID_GROUP = ? AND ID_CLASSIFICATION_GROUP IS NOT NULL)
                  OR CLG IS NULL)";
        
        
        $classifications = $this->database->query($query, $idUser, $idGroup, $idUser, $idGroup)->fetchAll();
        foreach($classifications as $class) {
            $classification = new Classification;
            $classification->idClassificationGroup = $class->ID_CLASSIFICATION_GROUP;
            $classification->idClassification = $class->ID_CLASSIFICATION;
            $classification->name = $class->NAME;
            $classification->user = $class->ID_USER;
            $classification->group = $class->ID_GROUP;
            $classification->grade = $class->GRADE;
            $classification->lastChange = '';//$class->LAST_CHANGE;
            $classificationsArray[] = $classification;
        }
        
        return $classificationsArray;
    }
    
    public function getGroupClassification($idGroupClassification)
    {
        $classificationArray = $this->database->query("SELECT * FROM classification_group WHERE ID_CLASSIFICATION_GROUP=?", $idGroupClassification)->fetch();
        $classificationGroup = new ClassificationGroup();
        $classificationGroup->idClassificationGroup = $classificationArray->ID_CLASSIFICATION_GROUP;
        $classificationGroup->name = $classificationArray->NAME;
        
        $classifications = $this->database->query("SELECT * FROM classification WHERE ID_CLASSIFICATION_GROUP=?", $idGroupClassification)->fetchAll();
        foreach($classifications as $classification) {
            $classObject = new Classification();
            $classObject->grade = $classification->GRADE;
            $classObject->notice = $classification->NOTICE;
            $classObject->user = $classification->ID_USER;
            $classificationGroup->classifications[] = $classObject;
        }
        
        return $classificationGroup;
    }
    
    public function createGroupClassification(ClassificationGroup $groupClassification)
    {
        $this->database->table('classification_group')->insert(array(
                'ID_GROUP' => $groupClassification->group->id,
                'NAME' => $groupClassification->name
            ));
        
        return $this->database->query("SELECT MAX(ID_CLASSIFICATION_GROUP) FROM classification_group")->fetchField();
    }
}
