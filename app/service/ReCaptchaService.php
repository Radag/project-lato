<?php

namespace App\Service;

class ReCaptchaService 
{

    public function checkCode($code)
    {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => '6LcDhq0UAAAAALennnki0ipOeWTlaXACcu8rEHKn',
            'response' => $code
        ];
        $query = http_build_query($data);
        $context  = stream_context_create([
            'http' => [
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                            "Content-Length: " . strlen($query) . "\r\n" .
                            "User-Agent:MyAgent/1.0\r\n",
                'method' => 'POST',
                'content' => $query
            ]
        ]);
        $verify = file_get_contents($url, false, $context);
        $captcha_success = json_decode($verify);
        return $captcha_success->success == false;
    }
}
