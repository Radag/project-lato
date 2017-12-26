<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Di;


class FtpSender
{    
    
    private $config;
    
    public function __construct($config = null)
    {
        $this->config = $config;
    }

    
    public function getConnection()
    {
        $conn_id = ftp_connect($this->config['ip']) or die("Couldn't connect to " . $this->config['ip']);
        $login_result = ftp_login($conn_id, $this->config['name'], $this->config['password']);
        return $conn_id;
    }
    
}