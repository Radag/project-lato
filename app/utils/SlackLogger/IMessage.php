<?php
/**
 * @author Tomáš Blatný
 */

namespace App\Util\SlackLogger;

interface IMessage
{

	/**
	 * @return string
	 */
	function getChannel();


	/**
	 * @param string $channel
	 * @return $this
	 */
	function setChannel($channel);

	/**
	 * @return string
	 */
	function getIcon();

	/**
	 * @param string $icon
	 * @return $this
	 */
	function setIcon($icon);

	/**
	 * @return string
	 */
	function getName();

	/**
	 * @param string $name
	 * @return $this
	 */
	function setName($name);

	/**
	 * @return string
	 */
	function getTitle();

	/**
	 * @param string $title
	 * @return $this
	 */
	function setTitle($title);

	/**
	 * @return string
	 */
	function getText();

	/**
	 * @param string $text
	 * @return $this
	 */
	function setText($text);

	/**
	 * @return string
	 */
	function getColor();

	/**
	 * @param string $color
	 * @return $this
	 */
	function setColor($color);
        
        /**
	 * @return string
	 */
	function getTrace();

	/**
	 * @param string $text
	 * @return $this
	 */
	function setTrace($text);

}
