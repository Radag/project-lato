<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Di;


class MailgunExt extends \Nette\DI\CompilerExtension
{
    
    private $defaults = [
        'key' => null,
        'domain' => null
    ];
    
    
    public function loadConfiguration()
    {
        $this->validateConfig($this->defaults);
        $this->defaults = $this->config;
        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix('mailfun_ext'))
                ->setFactory('App\Di\MailgunSender', [$this->config]);
    }
    
}