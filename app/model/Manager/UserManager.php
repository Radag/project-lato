<?php

namespace App\Model;

use Nette;
use Nette\Security\Passwords;


/**
 * Users management.
 */
class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{

	/** @var Nette\Database\Context */
	private $database;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;
                
                
		$row = $this->database->table('user')->where('NAME', $username)->fetch();

		if (!$row) {
			throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);

		} elseif (!Passwords::verify($password, $row['PASSWORD'])) {
			throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);

		} elseif (Passwords::needsRehash($row['PASSWORD'])) {
			$row->update(array(
				'PASSWORD' => Passwords::hash($password),
			));
		}

		$arr = $row->toArray();
		unset($arr['PASSWORD']);
		return new Nette\Security\Identity($row['ID_USER'], 'admin', $arr);
	}


	/**
	 * Adds new user.
	 * @param  string
	 * @param  string
	 * @return void
	 * @throws DuplicateNameException
	 */
	public function add($username, $password)
	{
		try {
			$this->database->table('user')->insert(array(
				'NAME' => $username,
				'PASSWORD' => Passwords::hash($password),
			));
		} catch (Nette\Database\UniqueConstraintViolationException $e) {
			throw new DuplicateNameException;
		}
	}

}



class DuplicateNameException extends \Exception
{}
