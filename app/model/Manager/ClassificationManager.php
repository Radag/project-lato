<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use Nette;
use App\Model\Entities\Classification;

/**
 * Description of TaskManager
 *
 * @author Radaq
 */
class ClassificationManager extends BaseManager 
{     
    public function createClassification(Classification $classification)
    {
        $this->database->beginTransaction();
        $this->database->table('classification')->insert(array(
                'ID_USER' => $classification->user->id,
                'ID_GROUP' => $classification->group->id,
                'NAME' => $classification->name,
                'CREATED_WHEN' => new \DateTime(),
                'CREATED_BY' => $this->user->id
            ));        
        $this->database->commit();
    }
    
    public function getUserClassification($idUser, $idGroup)
    {
        $classificationsArray = array();
        $classifications = $this->database->query("SELECT * FROM classification WHERE ID_USER=? AND ID_GROUP=?", $idUser, $idGroup)->fetchAll();
        foreach($classifications as $class) {
            $classification = new Classification;
            $classification->idClassification = $class->ID_CLASSIFICATION;
            $classification->name = $class->NAME;
            $classification->user = $class->ID_USER;
            $classification->group = $class->ID_GROUP;
            $classification->grade = $class->GRADE;
            $classification->lastChange = $class->LAST_CHANGE;
            $classificationsArray[] = $classification;
        }
        
        return $classificationsArray;
    }
}
