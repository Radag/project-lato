<?php

namespace App\Model\Manager;

use Nette;
use App\Model\LatoSettings;


class BaseManager 
{
    use Nette\SmartObject;
    protected $user;

    /** @var \Dibi\Connection  */
    protected $db;
    
    /** @var \App\Di\FtpSender  */
    protected $ftpSender;
    
    /** @var LatoSettings **/
    protected $settings;
    
    public function __construct(
        Nette\Security\User $user,
        \Dibi\Connection $db,
        \App\Di\FtpSender $ftpSender,            
        LatoSettings $settings
    )
    {
        $this->user = $user;
        $this->db = $db;
        $this->ftpSender = $ftpSender;
        $this->settings = $settings;
    }
}

