<?php


namespace App\Model\Entities;


class ClassificationGroup extends AbstractEntity{
    public $id = null;
    public $group = null;
    public $name = null;
    public $task = null;
    public $classifications = [];
    public $classificationDate = null;
    public $idPeriod = null;
    public $forAll = null;
}
