<?php

namespace App\Model\Entities;

class Task extends AbstractEntity
{
    public $id = null;
    public $idClassificationGroup = null;
    public $title = null;
    public $idMessage = null;
    public $deadline = null;
    public $online = null;
    public $message = null;
    public $timeLeft = null;
    //konkrétní úkol pro právě přihlášeného uživatele
    public $commit = null;
    //počet odevzdaných ukolů
    public $commitCount = null;
    //pole všech odevzdaných ukolů
    public $commitArray = array();
    //počet studentů, kteří ukol maji
    public $taskMembers = null;
    
    public $isCreator = false;
    public $isLate = null;
    public $createClassification = null;
}
