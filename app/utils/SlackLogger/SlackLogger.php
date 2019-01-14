<?php
namespace App\Util\SlackLogger;

use Tracy\Debugger;
use Tracy\Logger;


class SlackLogger extends Logger
{
    /** @var string */
    private $slackUrl;

    /** @var array */
    private $handlers = [];

    /** @var IMessageFactory */
    private $messageFactory;

    /** @var int */
    private $timeout;

    public function __construct($slackUrl, IMessageFactory $messageFactory, $data)
    {
        parent::__construct(Debugger::$logDirectory, Debugger::$email, Debugger::getBlueScreen());
        $this->slackUrl = $slackUrl;
        $this->messageFactory = $messageFactory;
        $this->timeout = $data['timeout'];
    }
    
    /**
     * @inheritdoc
     */
    public function log($value, $priority = self::INFO)
    {
        $logFile = parent::log($value, $priority);
        $message = $this->messageFactory->create($value, $priority, $logFile);
        if(is_object($value)) {
            $message->setTrace(mb_substr($value->getTraceAsString(), 0, 500));
        }        
        $event = new MessageSendEvent($message, $value, $priority, $logFile);

        foreach ($this->handlers as $handler) {
            if (!is_callable($handler)) {
                throw new HandlerException($handler);
            }
            $handler($event);
        }


        if (!$event->isCancelled()) {
            //$this->sendSlackMessage($message);
        }
        return $logFile;
    }


    /**
     * @param IMessage $message
     */
    private function sendSlackMessage(IMessage $message)
    {
        $body = [
            "icon_url" => $message->getIcon(),
            "username" => "admin-error",
            "text" => $message->getText(),
            "attachments" => [
                (object)[
                    "color" => $message->getColor(),
                    "title" => $message->getTitle(),
                    "fields" =>[
                        (object) [
                            "value" => $message->getTrace(),
                            "short" => false
                        ]
                    ],
                ]
            ]
        ];
        $client = new \GuzzleHttp\Client();
        $client->post($this->slackUrl, [
            'json' => $body
        ]);
    }

}
