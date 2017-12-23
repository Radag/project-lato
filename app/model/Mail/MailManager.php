<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Mail;

use Nette;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use App\Model\Manager\PublicActionManager;
use App\Model\Manager\UserManager;
use \Mailgun\Mailgun;

/**
 * Description of MailManager
 *
 * @author Radaq
 */
class MailManager 
{     
    
    /** @var PublicActionManager @inject */
    protected $publicActionManager;
    
    /** @var UserManager @inject */
    protected $userManager;
    
    
    public function __construct(PublicActionManager $publicActionManager, UserManager $userManager)
    {
        $this->publicActionManager = $publicActionManager;
        $this->userManager = $userManager;
    }
    
    public function sendRegistrationMail($values, $idUser, \Nette\Application\UI\Presenter $presenter)
    {
        $latte = new \Latte\Engine;
        $hashCode = $this->publicActionManager->addNewAction(PublicActionManager::ACTION_MAIL_VALIDATION);
        $params = [
            'link' => $presenter->link('//:Public:Action:default', array('id' => $hashCode, 'idUser'=>$idUser, 'email'=>base64_encode($values->email)))
        ];
        $body = $latte->renderToString(__DIR__ . '/templates/registrationMail.latte', $params);
        
        $mailData = (object)array(
            'from' => 'info@lato.cz',
            'to' => $values->email,
            'subject' => 'Potvrzení registrace',
            'body' => $body,
        );
        $this->sendMail($mailData);
       
    }
    
    public function sendLostPasswordMail($email, \Nette\Application\UI\Presenter $presenter)
    {
        $latte = new \Latte\Engine;
        $user = $this->userManager->getUserByMail($email); 
        
        if($user) {
            $hashCode = $this->publicActionManager->addNewAction(PublicActionManager::ACTION_LOST_PASS);
            $secret = $this->userManager->generateSecret($user->id);
            $params = [
                'link' => $presenter->link('//:Public:Action:default', array('id' => $hashCode, 'secret' => $secret, 'email' => base64_encode($email)))
            ];
            $body = $latte->renderToString(__DIR__ . '/templates/lostPasswordMail.latte', $params);

            $mailData = (object)array(
                'from' => 'info@lato.cz',
                'to' => $email,
                'subject' => 'Žádost o reset hesla',
                'body' => $body,
            );
            $this->sendMail($mailData);
        }
    }
    
    public function sendMail($mailData)
    { 
        /*
        $mail = new Message;
        $mail->setFrom($mailData->from)
            ->addTo($mailData->to)
            ->setSubject($mailData->subject)
            ->setHtmlBody($mailData->body);
        
        $mailer = new SendmailMailer;
        $mailer->send($mail);
        */
        
        $key = 'key-926f0ed9318d18c1ee7f13f844623db5';//$this->presenter->context->getParameters()['mailgun']['key'];
        $domain = 'lato.cz';//$this->presenter->context->getParameters()['mailgun']['domain'];
        $mg = Mailgun::create($key);
        $mg->messages()->send($domain, [
          'from'    => $mailData->from,
          'to'      => $mailData->to,
          'subject' => $mailData->subject,
          'html'    => $mailData->body
        ]);
    }
    
}
