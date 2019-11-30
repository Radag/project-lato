<?php

namespace App\Model\Entities\Test;

use App\Model\Entities\AbstractEntity;

class TestSummary extends AbstractEntity {
    
    // info pro učitele
    public $studentsCount = null;
    public $studentsCountTotal = null;
    public $studentsPercent = null;
    
    //info pro studenta k jeho testu
    public $filledCount = null;
    public $percent = null;
    public $grade = null;
}
