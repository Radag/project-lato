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

    /** @var \Dibi\Connection  */
    protected $db;
    
    /** @var \App\Di\FtpSender  */
    protected $ftpSender;
    
    public function __construct(
        Nette\Security\User $user,
        \Dibi\Connection $db,
        \App\Di\FtpSender $ftpSender
    )
    {
        $this->user = $user;
        $this->db = $db;
        $this->ftpSender = $ftpSender;
    }

  
}

