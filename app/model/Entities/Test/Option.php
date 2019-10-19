<?php

namespace App\Model\Entities\Test;

use App\Model\Entities\AbstractEntity;

class Option extends AbstractEntity {
    public $id = null;
    public $questionId = null;
    public $name = null;
    public $isCorrect = null;
    public $number = null;
}
