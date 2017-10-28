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
                    throw new Nette\Security\AuthenticationException('Špatné uživatelské jméno nebo heslo.', self::IDENTITY_NOT_FOUND);

            } elseif (!Passwords::verify($password, $row['PASSWORD'])) {
                    throw new Nette\Security\AuthenticationException('Špatné uživatelské jméno nebo heslo.', self::INVALID_CREDENTIAL);

            } elseif (Passwords::needsRehash($row['PASSWORD'])) {
                    $row->update(array(
                            'PASSWORD' => Passwords::hash($password),
                    ));
            }
            $this->setLastLogin($row['ID_USER']);
            
            $arr = $row->toArray();
            unset($arr['PASSWORD']);
            return new Nette\Security\Identity($row['ID_USER'], 'admin', $arr);
    }

    public function setLastLogin($idUser)
    {
        $data = array('LAST_LOGIN' => new \DateTime, 'LAST_LOGIN_IP' => $_SERVER['REMOTE_ADDR']);
        $this->database->query("UPDATE user SET ? WHERE ID_USER=?", $data, $idUser);

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
                        'PASSWORD' => Passwords::hash($values->password1),
                        'REGISTER_IP' => $_SERVER['REMOTE_ADDR']
                ));
                $idUser = $this->database->query('SELECT MAX(ID_USER) FROM user')->fetchField();

                $urlId = $idUser . '_' . Nette\Utils\Strings::webalize($values->name . '_' . $values->surname);
                $this->database->query("UPDATE user SET URL_ID=? WHERE ID_USER=?", $urlId, $idUser);

                $this->database->commit();

                return $idUser;
            } catch (Nette\Database\UniqueConstraintViolationException $e) {
                    throw new DuplicateNameException;
            }


    }
    
    public function updatePassword(User $user, $password) 
    {
        $this->database->query("UPDATE user SET PASSWORD=? WHERE ID_USER=?", Passwords::hash($password), $user->id);
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
        if($userData) {       
            $user->id = $userData->ID_USER;
            $user->surname = $userData->SURNAME;
            $user->name = $userData->NAME;
            $user->email = $userData->EMAIL;
            $user->urlId = $userData->URL_ID;
            $user->username = $userData->USERNAME;
            $user->birthday = $userData->BIRTHDAY;
            $user->profileImage = User::createProfilePath($userData->PROFILE_PATH, $userData->PROFILE_FILENAME, $userData->SEX);
            return $user;
        } else {
            return null;
        }
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

    public function verifyEmail(User $user, $email) 
    {
        $this->database->query("UPDATE user SET EMAIL_VERIFY=1 WHERE ID_USER=? AND EMAIL=?", $user->id, $email);
    }

    public function getUserByMail($email, $secret = null) 
    {
        if($secret !== null) {
            $idUser = $this->database->query("SELECT ID_USER FROM vw_user_detail WHERE EMAIL=? AND SECRET=?", $email, $secret)->fetchField();
        } else {
            $idUser = $this->database->query("SELECT ID_USER FROM vw_user_detail WHERE EMAIL=?", $email)->fetchField();
        }
        
        if($idUser) {
            return $this->get($idUser);
        } else {
            return false;
        }
    }
    
    public function generateSecret($idUser) 
    {
        $secret = mb_strtoupper(substr(md5(openssl_random_pseudo_bytes(40)),-30));
        $this->database->query("UPDATE user SET SECRET=? WHERE ID_USER=?", $secret, $idUser);
        return $secret;
    }
    
}



class DuplicateNameException extends \Exception
{}
