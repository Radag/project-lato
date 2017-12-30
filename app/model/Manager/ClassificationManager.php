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
        $this->db->begin();
        if($classification->idClassificationGroup !== null) {
            $idClassification = $this->db->fetchSingle("SELECT ID_CLASSIFICATION FROM classification WHERE ID_CLASSIFICATION_GROUP=? AND ID_USER=?", $classification->idClassificationGroup, $classification->user->id);
            if($idClassification) {
                $this->db->query("UPDATE classification SET ", [
                    'NOTICE' => $classification->notice, 
                    'GRADE' => $classification->grade,
                    'NAME' => $classification->name,
                    'CLASSIFICATION_DATE' => $classification->date
                ], " WHERE ID_CLASSIFICATION=?" ,$idClassification);
            } else {
                $insert = true;
            }   
        } else if($classification->idClassification !== null) {
            $this->db->query("UPDATE classification SET ", [
                'NOTICE' => $classification->notice, 
                'GRADE' => $classification->grade,
                'NAME' => $classification->name,
                'CLASSIFICATION_DATE' => $classification->date
            ], " WHERE ID_CLASSIFICATION=?", $classification->idClassification);       
        } else {
            $insert = true;
        }
        
        if($insert) {
            $this->db->query('INSERT INTO classification', [
                'ID_USER' => $classification->user->id,
                'ID_GROUP' => $classification->group->id,
                'NAME' => $classification->name,
                'ID_CLASSIFICATION_GROUP' => $classification->idClassificationGroup,
                'NOTICE' => $classification->notice,
                'CREATED_BY' => $this->user->id,
                'GRADE' => $classification->grade,
                'ID_PERIOD' => $classification->idPeriod,
                'CLASSIFICATION_DATE' => $classification->date 
            ]);  
        }
             
        $this->db->commit();
    }
    
    public function getUserClassification($idUser, $idGroup)
    {
        $classificationsArray = [];
        $query = "SELECT * FROM vw_classification
                  WHERE (user_id=? OR user_id IS NULL) AND group_id = ?
                  AND (clg NOT IN (SELECT classification_group_id FROM classification WHERE user_id=? AND group_id = ? AND classification_group_id IS NOT NULL)
                  OR clg IS NULL)";
        
        
        $classifications = $this->db->fetchAll($query, $idUser, $idGroup, $idUser, $idGroup);
        foreach($classifications as $class) {
            $classification = new Classification;
            $classification->idClassificationGroup = $class->classification_group_id;
            $classification->idClassification = $class->id;
            $classification->name = $class->name;
            $classification->user = $class->user_id;
            $classification->group = $class->group_id;
            $classification->grade = $class->grade;
            $classification->classificationDate = $class->classification_date;
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
    
    public function getLastChange($idUser, $idGroup)
    {
        $query = "SELECT IFNULL(last_change, created_when) AS last_change FROM classification WHERE user_id=? AND group_id=?";
        return $this->db->fetchSingle($query, $idUser, $idGroup);
    }
    
    public function getGroupClassification($idGroupClassification)
    {
        $classificationArray = $this->db->fetch("SELECT * FROM classification_group WHERE id=?", $idGroupClassification);
        $classificationGroup = new ClassificationGroup();
        $classificationGroup->idClassificationGroup = $classificationArray->id;
        $classificationGroup->name = $classificationArray->name;
        $classificationGroup->classificationDate = $classificationArray->classification_date;
        if(!empty($classificationArray->task_id)) {
            $classificationGroup->task = new \App\Model\Entities\Task();
            $classificationGroup->task->id = $classificationArray->task_id;
        }
        
        
        $classifications = $this->database->query("SELECT * FROM classification WHERE classification_group_id=?", $idGroupClassification)->fetchAll();
        foreach($classifications as $classification) {
            $classObject = new Classification();
            $classObject->grade = $classification->grade;
            $classObject->notice = $classification->notice;
            $classObject->user = $classification->user_id;
            $classificationGroup->classifications[] = $classObject;
        }
        
        return $classificationGroup;
    }
    
    public function createGroupClassification(ClassificationGroup $groupClassification)
    {
        $this->db->query("INSERT INTO classification_group", [
            'group_id' => $groupClassification->group->id,
            'name' => $groupClassification->name,
            'task_id' => isset($groupClassification->task) ? $groupClassification->task->idTask : null
        ]);

        return $this->db->getInsertId();
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
    
    public function getMyClassification(\App\Model\Entities\User $user)
    {
        $query = "SELECT
                        T1.id,
                        T1.grade,
                        T1.notice,
                        T1.classification_date,
                        T1.name AS grade_name,
                        T2.id AS group_id,
                        T2.name AS group_name,
                        T4.main_color,
                        T2.shortcut,
                        T4.code AS group_color_code
                FROM vw_classification T1
                LEFT JOIN `group` T2 ON T1.group_id=T2.id
                LEFT JOIN group_scheme T4 ON T2.group_scheme_id = T4.id
                LEFT JOIN group_period T3 ON T1.period_id=T3.id
                WHERE T3.active=1 AND T1.user_id=?";
        $classifications = $this->db->fetchAll($query, $user->id);
        
        $return = array();
        foreach($classifications as $class) {
            if(!isset($return[$class->group_id])) {
                $group = new \App\Model\Entities\Group();
                $group->name = $class->group_name;
                $group->mainColor = $class->main_color;
                $group->shortcut = $class->shortcut;
                $group->colorScheme = $class->group_color_code;
                $group->statistics = ['last_change' => null, 'avg_grade' => 0, 'count_grade' => 0];
                $group->classification = [];
                $return[$class->group_id] = $group;
            }
            if(!empty($class->id)) {
                $classObject = new Classification();
                $classObject->grade = $class->grade;
                $classObject->notice = $class->notice;
                $classObject->name = $class->grade_name;
                $classObject->lastChange = $class->classification_date;    
                $return[$class->id]->classification[] = $classObject;
                if($return[$class->id]->statistics['last_change'] === null ||
                   $class->classification_date > $return[$class->id]->statistics['last_change']
                ) {
                    $return[$class->id]->statistics['last_change'] = $class->classification_date;
                }
                $return[$class->id]->statistics['avg_grade'] += (int)$class->grade;
                $return[$class->id]->statistics['count_grade']++;
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
