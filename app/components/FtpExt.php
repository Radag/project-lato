<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Di;


class FtpExt extends \Nette\DI\CompilerExtension
{
    
    private $defaults = [
        'ip' => null,
        'name' => null,
        'password' => null
    ];
    
    
    public function loadConfiguration()
    {
        $this->validateConfig($this->defaults);
        $this->defaults = $this->config;
        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix('ftp_ext'))
                ->setFactory('App\Di\FtpSender', [$this->config]);
    }
    
}