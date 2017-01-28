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
                $data = array('NOTICE' => $classification->notice, 'GRADE' => $classification->grade, 'LAST_CHANGE' => new \DateTime());
                $this->database->query("UPDATE classification SET ? WHERE ID_CLASSIFICATION=?", $data, $idClassification);
            } else {
                $insert = true;
            }   
        } else if($classification->idClassification !== null) {
            $data = array('NOTICE' => $classification->notice, 'GRADE' => $classification->grade, 'LAST_CHANGE' => new \DateTime());
            $this->database->query("UPDATE classification SET ? WHERE ID_CLASSIFICATION=?", $data, $classification->idClassification);       
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
                'GRADE' => $classification->grade,
                'ID_PERIOD' => $classification->idPeriod
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
            $classification->classificationDate = $class->CLASSIFICATION_DATE;
            $classificationsArray[] = $classification;
        }
        
        return $classificationsArray;
    }
    
    public function getClassification($idClassification)
    {
        $query = "SELECT * FROM classification WHERE ID_CLASSIFICATION=?";
        $class = $this->database->query($query, $idClassification)->fetch();

        $classification = new Classification;
        $classification->idClassificationGroup = $class->ID_CLASSIFICATION_GROUP;
        $classification->idClassification = $class->ID_CLASSIFICATION;
        $classification->name = $class->NAME;
        $classification->user = new \App\Model\Entities\User();
        $classification->user->id = $class->ID_USER;
        $classification->group = $class->ID_GROUP;
        $classification->grade = $class->GRADE;
        $classification->notice = $class->NOTICE;
        
        return $classification;
    }
    
    public function getGroupClassification($idGroupClassification)
    {
        $classificationArray = $this->database->query("SELECT * FROM classification_group WHERE ID_CLASSIFICATION_GROUP=?", $idGroupClassification)->fetch();
        $classificationGroup = new ClassificationGroup();
        $classificationGroup->idClassificationGroup = $classificationArray->ID_CLASSIFICATION_GROUP;
        $classificationGroup->name = $classificationArray->NAME;
        $classificationGroup->classificationDate = $classificationArray->CLASSIFICATION_DATE;
        if(!empty($classificationArray->ID_TASK)) {
            $classificationGroup->task = new \App\Model\Entities\Task();
            $classificationGroup->task->idTask = $classificationArray->ID_TASK;
        }
        
        
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
        $values = array(
                'ID_GROUP' => $groupClassification->group->id,
                'NAME' => $groupClassification->name,
                'ID_TASK' => isset($groupClassification->task) ? $groupClassification->task->idTask : null
                );
                
        
        $this->database->table('classification_group')->insert($values);
        
        return $this->database->query("SELECT MAX(ID_CLASSIFICATION_GROUP) FROM classification_group")->fetchField();
    }
    
    public function updateClassificationGroup($values)
    {
        $data = array('NAME' => $values->name, 'CLASSIFICATION_DATE' => $values->date);
        $this->database->query("UPDATE classification_group SET ? WHERE ID_CLASSIFICATION_GROUP=?", $data, $values->id);       
       
    }

    public function getSchoolPeriods()
    {
        return $this->database->query("SELECT * FROM school_period")->fetchAll();
    }
    
    public function getMyClassification(\App\Model\Entities\User $user, $period)
    {
        $classifications = $this->database->query(
              "SELECT 
                    T1.*,
                    T3.GRADE,
                    T3.ID_CLASSIFICATION,
                    T3.NOTICE AS GRADE_NOTICE,
                    IF(T3.ID_CLASSIFICATION_GROUP IS NULL, T3.NAME, T4.NAME) AS GRADE_NAME,
                    IF(T3.LAST_CHANGE IS NULL, T3.CREATED_WHEN, T3.LAST_CHANGE) AS GRADE_LAST_CHANGE
                FROM 
                    vw_user_groups_detail T1 
                    LEFT JOIN group_period T2 ON T1.ID_GROUP=T2.ID_GROUP 
                    LEFT JOIN classification T3 ON (T3.ID_GROUP = T1.ID_GROUP AND T1.ID_USER=T3.ID_USER AND T3.ID_PERIOD=?)
                    LEFT JOIN classification_group T4 ON T4.ID_CLASSIFICATION_GROUP=T3.ID_CLASSIFICATION_GROUP
                WHERE (T2.ID_PERIOD=? OR T2.ID_PERIOD IS NULL) AND T1.ID_USER=? AND T1.ID_RELATION=2",
              $period, $period , $user->id)->fetchAll();
        
        $return = array();
        foreach($classifications as $class) {
            if(!isset($return[$class->ID_GROUP])) {
                $group = new \App\Model\Entities\Group();
                $group->name = $class->NAME;
                $group->mainColor = $class->MAIN_COLOR;
                $group->shortcut = $class->SHORTCUT;
                $group->statistics = array('last_change' => null, 'avg_grade' => 0, 'count_grade' => 0);
                $group->classification = array();
                $return[$class->ID_GROUP] = $group;
            }
            if(!empty($class->ID_CLASSIFICATION)) {
                $classObject = new Classification();
                $classObject->grade = $class->GRADE;
                $classObject->notice = $class->GRADE_NOTICE;
                $classObject->name = $class->GRADE_NAME;
                $classObject->lastChange = $class->GRADE_LAST_CHANGE;    
                $return[$class->ID_GROUP]->classification[] = $classObject;
                if($return[$class->ID_GROUP]->statistics['last_change'] === null ||
                   $class->GRADE_LAST_CHANGE > $return[$class->ID_GROUP]->statistics['last_change']
                ) {
                    $return[$class->ID_GROUP]->statistics['last_change'] = $class->GRADE_LAST_CHANGE;
                }
                $return[$class->ID_GROUP]->statistics['avg_grade'] += $class->GRADE;
                $return[$class->ID_GROUP]->statistics['count_grade']++;
            }
        }
        
        foreach($return as $ret) {
            if($ret->statistics['count_grade'] > 0) {
                $ret->statistics['avg_grade'] = round($ret->statistics['avg_grade']/$ret->statistics['count_grade'], 2);
            }
        }
        return $return;
        
    }
    
}
