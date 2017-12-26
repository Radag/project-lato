<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Di;

use \Mailgun\Mailgun;

class MailgunSender
{    
    
    private $config;
    
    public function __construct($config = null)
    {
        $this->config = $config;
    }

    
    public function sendMail($mailData)
    {
        $mg = Mailgun::create($this->config['key']);
        $mg->messages()->send($this->config['domain'], [
          'from'    => $mailData->from,
          'to'      => $mailData->to,
          'subject' => $mailData->subject,
          'html'    => $mailData->body
        ]);
    }
    
}