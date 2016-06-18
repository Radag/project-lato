<?php

namespace App\Model;

use Nette;
use Nette\Security\Passwords;
use App\Model\Entities\User;


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
                
                
		$row = $this->database->table('user')->where('USERNAME', $username)->fetch();
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
				'USERNAME' => $username,
				'PASSWORD' => Passwords::hash($password),
			));
		} catch (Nette\Database\UniqueConstraintViolationException $e) {
			throw new DuplicateNameException;
		}
	}
        
        public function get($idUser)
        {
            $user = new User;
            $messages = $this->database->query("SELECT T1.NAME, T1.SURNAME, T1.ID_USER,
                        T2.PATH,
                        T2.FILENAME
                FROM user T1 
                LEFT JOIN file_list T2 ON T2.ID_FILE=T1.PROFILE_IMAGE
                WHERE T1.ID_USER=?", $idUser)->fetch();
            
            $user->id = $messages->ID_USER;
            $user->surname = $messages->SURNAME;
            $user->name = $messages->NAME;
            $user->profileImage = "http://cdn.lato.cz/" . $messages->PATH . "/" . $messages->FILENAME;
                
            return $user;
        }

}



class DuplicateNameException extends \Exception
{}
