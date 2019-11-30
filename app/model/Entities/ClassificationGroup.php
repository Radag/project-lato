<?php


namespace App\Model\Entities;


class ClassificationGroup extends AbstractEntity
{
    
    const TYPE_NORMAL = 'normal';
    const TYPE_TEST = 'test';
    const TYPE_HOMEWORK = 'homework';

    public $id = null;
    public $group = null;
    public $name = null;
    public $task = null;
    public $classifications = [];
    public $classificationDate = null;
    public $idPeriod = null;
    public $forAll = null;
    public $type = self::TYPE_NORMAL;
}
