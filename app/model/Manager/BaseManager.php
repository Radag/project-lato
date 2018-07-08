<?php

namespace App\Model\Manager;

use Nette;


/**
 * Users management.
 */
class BaseManager 
{
    use Nette\SmartObject;
    protected $user;

    /** @var Nette\Database\Context */
    protected $database;
    
    /** @var \Dibi\Connection  */
    protected $db;
    
    /** @var \App\Di\FtpSender  */
    protected $ftpSender;

    public function __construct(
        Nette\Database\Context $database,
        Nette\Security\User $user,
        \Dibi\Connection $db,
        \App\Di\FtpSender $ftpSender
    )
    {
        $this->database = $database;
        $this->user = $user;
        $this->db = $db;
        $this->ftpSender = $ftpSender;
    }

  
}

