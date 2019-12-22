<?php

namespace App\Model\Entities;

class TaskCommit extends AbstractEntity
{
    public $idTask = null;
    public $idCommit = null;
    public $comment = null;
    public $user = null;
    public $files = [];
    public $created = null;
    public $updated = null;
    public $isLate = null;
    public $grade = null;
}
