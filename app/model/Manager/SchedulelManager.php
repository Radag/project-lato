<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Manager;

use Nette;

/**
 * Description of MaterialManager
 *
 * @author Radaq
 */
class SchedulelManager extends BaseManager
{ 
    
    public function getTodaySchedule($groups) 
    {
        if(!empty($groups)) {
            $schedule = $this->database->query("SELECT 
                        T1.TIME_FROM,
                        T1.TIME_TO,
                        T2.SHORTCUT,
                        T2.ROOM
                    FROM group_schedule T1
                    LEFT JOIN groups T2 ON (T1.ID_GROUP = T2.ID_GROUP AND T2.ARCHIVED=0)
                    WHERE T1.ID_GROUP IN (" . implode(",", array_keys($groups)) . ") 
                    AND T1.DAY_IN_WEEK = ?
                    ORDER BY T1.TIME_FROM ASC", date("N"))->fetchAll();
            return $schedule;
        } else {
            return array();
        }
    }
}
