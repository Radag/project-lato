<?php

namespace App\Model\Manager;

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
		list($email, $password) = $credentials;
                
                
		$row = $this->database->table('user')->where('EMAIL', $email)->fetch();
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
	public function add($values)
	{
		try {
                    $this->database->beginTransaction();
                    
                    $this->database->table('user')->insert(array(
                            'EMAIL' => $values->email,
                            'NAME' => $values->name,
                            'SURNAME' => $values->surname,
                            'PASSWORD' => Passwords::hash($values->password1)
                    ));
                    $idUser = $this->database->query('SELECT MAX(ID_USER) FROM user')->fetchField();
                    
                    $urlId = $idUser . '_' . Nette\Utils\Strings::webalize($values->name . '_' . $values->surname);
                    $this->database->query("UPDATE user SET URL_ID=? WHERE ID_USER=?", $urlId, $idUser);
    
                    $this->database->commit();
		} catch (Nette\Database\UniqueConstraintViolationException $e) {
			throw new DuplicateNameException;
		}
	}
        
        /**
         * 
         * @param int $idUser
         * @return User
         */
        public function get($idUser, $urlId = false)
        {
            $user = new User;
            if($urlId) {
                $userData = $this->database->query("SELECT * FROM vw_user_detail WHERE URL_ID=?", $idUser)->fetch();  
            } else {
                $userData = $this->database->query("SELECT * FROM vw_user_detail WHERE ID_USER=?", $idUser)->fetch(); 
            }
            
            $user->id = $userData->ID_USER;
            $user->surname = $userData->SURNAME;
            $user->name = $userData->NAME;
            $user->email = $userData->EMAIL;
            $user->urlId = $userData->URL_ID;
            $user->username = $userData->USERNAME;
            if($userData->PROFILE_FILENAME) {
                $user->profileImage = "https://cdn.lato.cz/" . $userData->PROFILE_PATH . "/" . $userData->PROFILE_FILENAME;
            } else {
                if($userData->SEX == 'M') {
                    $user->profileImage = '/images/default-avatar_man.png';
                } else {
                    $user->profileImage = '/images/default-avatar_woman.png';
                }
            }
                
            return $user;
        }
        
        public function assignProfileImage(\App\Model\Entities\User $user, $file)
        {
            $this->database->query("UPDATE user SET PROFILE_IMAGE=? WHERE ID_USER=?", $file['idFile'], $user->id);
        }
        
        public function updateUser($values, $user)
        {
            $this->database->query("UPDATE user SET NAME=?, SURNAME=?, EMAIL=?, BIRTHDAY=? WHERE ID_USER=?", $values['name'], $values['surname'], $values['email'], $values['birthday'], $user->id);
        }
        
        public function getUsersList() {
            $return = array();
            $users =  $this->database->query("SELECT * FROM vw_user_detail")->fetchAll();
            foreach($users as $user) {
                $return[$user->USERNAME] = 'https://cdn.lato.cz/' . $user->PROFILE_PATH . '/' . $user->PROFILE_FILENAME;
            }
            return $return;
        }
        
        public function getByName($name) {
            return $this->database->query("SELECT ID_USER FROM vw_user_detail WHERE USERNAME=?", $name)->fetchField();
        }
        
        public function switchUserRelation($idUser, $idRelated, $add) {
            if($add) {
                $this->database->table('user_relations')->insert(array(
                    'ID_USER' => $idUser,
                    'ID_RELATED_USER' => $idRelated
                ));
            } else {
                $this->database->query("DELETE FROM user_relations WHERE ID_USER=? AND ID_RELATED_USER=?", $idUser, $idRelated);
            }            
            
        }
        
        public function isFriend($idUser, $idRelated) {
            $relation =  $this->database->query("SELECT TYPE FROM user_relations WHERE ID_USER=? AND ID_RELATED_USER=?", $idUser, $idRelated)->fetchField();
            
            return $relation == 'FRIEND';
            
        }

}



class DuplicateNameException extends \Exception
{}
