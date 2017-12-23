<?php

namespace App\Model\Manager;

use Nette;


/**
 * Users management.
 */
class BaseManager extends Nette\Object
{
    protected $user;

    /** @var Nette\Database\Context */
    protected $database;
    
    /** @var \Dibi\Connection  */
    protected $db;

    public function __construct(
        Nette\Database\Context $database,
        Nette\Security\User $user,
        \Dibi\Connection $db
    )
    {
        $this->database = $database;
        $this->user = $user;
        $this->db = $db;
    }

  
}

