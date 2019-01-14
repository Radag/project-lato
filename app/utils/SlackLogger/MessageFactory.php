<?php
namespace App\Util\SlackLogger;

use Exception;
use Throwable;
use Tracy\ILogger;


class MessageFactory implements IMessageFactory
{

	/** @var array */
	private $defaults;

	/** @var string|NULL */
	private $logUrl;


	public function __construct(array $defaults, $logUrl)
	{
		$this->defaults = $defaults;
		$this->logUrl = $logUrl;
	}


	/**
	 * @inheritdoc
	 */
	public function create($exception, $priority, $logFile)
	{
            
                $message = new Message($this->defaults);

		$text = ucfirst($priority) . ': ';
		if ($exception instanceof Exception || $exception instanceof Throwable) {
			$text .= $exception->getMessage();
		} elseif (is_array($exception)) {
			$text .= reset($exception);
		} else {
			$text .= (string) $exception;
		}

		if ($this->logUrl && $logFile) {
			$text .= ' (<' . str_replace('__FILE__', basename($logFile), $this->logUrl) . '|Open log file>)';
		}

		switch ($priority) {
			case ILogger::DEBUG:
			case ILogger::INFO:
				$color = '#444444';
				break;
			case ILogger::ERROR:
			case ILogger::WARNING:
				$color = 'warning';
				break;
			case ILogger::EXCEPTION:
			case ILogger::CRITICAL:
				$color = 'danger';
			break;
			default:
				$color = null;
				break;
		}

		if ($color) {
			$message->setColor($color);
		}
                $message->setTitle($_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		$message->setText($text);
                \Tracy\Debugger::barDump($message);
		return $message;
	}

}
