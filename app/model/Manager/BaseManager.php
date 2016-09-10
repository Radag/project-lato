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

    public function __construct(Nette\Database\Context $database,
                    Nette\Security\User $user
    )
    {
            $this->database = $database;
            $this->user = $user;
    }

  
}

