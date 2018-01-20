<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

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
            $idClassification = $this->db->fetchSingle("SELECT id FROM classification WHERE classification_group_id=? AND user_id=?", $classification->idClassificationGroup, $classification->idUser);
            if($idClassification) {
                $this->db->query("UPDATE classification SET ", [
                    'notice' => $classification->notice, 
                    'grade' => $classification->grade,
                    'name' => $classification->name,
                    'classification_date' => $classification->date
                ], " WHERE id=?" ,$idClassification);
            } else {
                $insert = true;
            }   
        } else if($classification->idClassification !== null) {
            $this->db->query("UPDATE classification SET ", [
                'notice' => $classification->notice, 
                'grade' => $classification->grade,
                'name' => $classification->name,
                'classification_date' => $classification->date
            ], " WHERE id=?", $classification->idClassification);       
        } else {
            $insert = true;
        }
        
        if($insert) {
            $this->db->query('INSERT INTO classification', [
                'user_id' => $classification->idUser,
                'group_id' => $classification->group->id,
                'name' => $classification->name,
                'classification_group_id' => $classification->idClassificationGroup,
                'notice' => $classification->notice,
                'created_by' => $this->user->id,
                'grade' => $classification->grade,
                'period_id' => $classification->idPeriod,
                'classification_date' => $classification->date 
            ]);  
        }
             
        $this->db->commit();
    }
    
    public function updateTaskClassification(\App\Model\Entities\Task $task, \App\Model\Entities\Group $group, $groupManager) 
    {
        $exist = $this->db->fetch("SELECT * FROM classification_group WHERE task_id=?", $task->id);
        if(!$exist && $task->create_classification == 1) {
            $groupClassification = new ClassificationGroup();
            $groupClassification->task = $task;
            $groupClassification->group = $group;
            $groupClassification->idPerion = $group->activePeriodId;
            $groupClassification->name = $task->title;
            $classGroupId = $this->createGroupClassification($groupClassification);
            $students = $groupManager->getGroupUsers($group->id, GroupManager::RELATION_STUDENT);
            foreach($students as $student) {
                $classification = new \App\Model\Entities\Classification();
                $classification->idClassificationGroup = $classGroupId;
                $classification->group = $group;
                $classification->idUser = $student->id;
                $classification->idPeriod = $group->activePeriodId;
                $this->createClassification($classification);
            }
            
        } elseif($exist && $task->create_classification == 0) {
            $this->db->query("DELETE FROM classification_group WHERE id=?", $exist->id);
        }
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
        $query = "SELECT * FROM classification WHERE id=?";
        $class = $this->database->query($query, $idClassification)->fetch();

        $classification = new Classification;
        $classification->idClassificationGroup = $class->classification_group_id;
        $classification->id = $class->id;
        $classification->name = $class->name;
        $classification->user = new \App\Model\Entities\User();
        $classification->user->id = $class->user_id;
        $classification->group = $class->group_id;
        $classification->grade = $class->grade;
        $classification->notice = $class->notice;
        
        return $classification;
    }
    
    public function getLastChange($idUser, $idGroup)
    {
        $query = "SELECT IFNULL(last_change, created_when) AS last_change FROM classification WHERE user_id=? AND group_id=?";
        return $this->db->fetchSingle($query, $idUser, $idGroup);
    }
    
    public function getGroupPeriodClassification(\App\Model\Entities\Group $group)
    {
        $return = [];
        $classifications = $this->db->fetchAll("SELECT * FROM vw_classification WHERE group_id=? AND period_id=?", $group->id, $group->activePeriodId);
        foreach($classifications as $class) {
            $classObject = new Classification;
            $classObject->classificationDate = $class->classification_date;
            $classObject->grade = $class->grade;
            $classObject->id = $class->id;
            $classObject->name = $class->name;
            $classObject->idClassificationGroup = $class->classification_group_id;
            $return[$class->user_id][] = $classObject;
        }
        return $return;
    }
    
    public function getGroupClassification($idGroupClassification)
    {
        $classificationArray = $this->db->fetch("SELECT * FROM classification_group WHERE id=?", $idGroupClassification);
        $classificationGroup = new ClassificationGroup();
        $classificationGroup->id = $classificationArray->id;
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
            $classObject->user = new \App\Model\Entities\User($classification);
            $classificationGroup->classifications[] = $classObject;
        }
        
        return $classificationGroup;
    }
    
    public function createGroupClassification(ClassificationGroup $groupClassification)
    {
        $this->db->query("INSERT INTO classification_group", [
            'group_id' => $groupClassification->group->id,
            'name' => $groupClassification->name,
            'task_id' => isset($groupClassification->task) ? $groupClassification->task->id : null,
            'period_id' => $groupClassification->idPerion
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
        $return = [];
        foreach($classifications as $class) {
            if(!isset($return[$class->group_id])) {
                $group = new \App\Model\Entities\Group();
                $group->name = $class->group_name;
                $group->mainColor = $class->main_color;
                $group->shortcut = $class->shortcut;
                $group->colorScheme = $class->group_color_code;
                $group->statistics = (object)['last_change' => null, 'avg_grade' => 0, 'count_grade' => 0];
                $group->classification = [];
                $return[$class->group_id]['group'] = $group;
            }
            if(!empty($class->id)) {
                $classObject = new Classification();
                $classObject->grade = $class->grade;
                $classObject->notice = $class->notice;
                $classObject->name = $class->grade_name;
                $classObject->lastChange = $class->classification_date;    
                $return[$class->group_id]['classification'][] = $classObject;
                if($return[$class->group_id]['group']->statistics->last_change === null ||
                   $class->classification_date > $return[$class->group_id]['group']->statistics->last_change
                ) {
                    $return[$class->group_id]['group']->statistics->last_change = $class->classification_date;
                }
                if($class->grade != '—') {
                    $grade = $class->grade == 'N' ? 5 : (int)$class->grade;
                    $return[$class->group_id]['group']->statistics->avg_grade += $grade;
                    $return[$class->group_id]['group']->statistics->count_grade++;
                }
            }
        }
        foreach($return as $ret) {
            if($ret['group']->statistics->count_grade > 0) {
                $ret['group']->statistics->avg_grade = round($ret['group']->statistics->avg_grade/$ret['group']->statistics->count_grade, 2);
            }
        }
        return $return;
        
    }
    
}
