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

    /** @var \Dibi\Connection  */
    protected $db;
    
    public function __construct(
        \Dibi\Connection $db
    )
    {
        $this->db = $db;
    }

    public function authenticate(array $credentials)
    {
        list($email, $password) = $credentials;
        $row = $this->db->fetch("SELECT * FROM user WHERE email=?", $email);
        if (!$row) {
            throw new Nette\Security\AuthenticationException('Špatné uživatelské jméno nebo heslo.', self::IDENTITY_NOT_FOUND);
        } elseif (!Passwords::verify($password, $row->password)) {
            throw new Nette\Security\AuthenticationException('Špatné uživatelské jméno nebo heslo.', self::INVALID_CREDENTIAL);
        } elseif (Passwords::needsRehash($row->password)) {
            $this->db->query("UPDATE user ", ['password' => Passwords::hash($password)], "WHERE id=?", $row->id);
        }
        $this->setLastLogin($row->id);

        $arr = $row->toArray();
        unset($arr['password']);
        return new Nette\Security\Identity($row->id, 'user', $arr);
    }

    public function setLastLogin($idUser)
    {
        $this->db->query("UPDATE user SET last_login=NOW(), last_login_ip=? WHERE id=?",  $_SERVER['REMOTE_ADDR'], $idUser);
    }

    public function add($values)
    {
        try {
            $this->db->begin();
            $this->db->query("INSERT INTO user", [
                'email' => $values->email,
                'name' => $values->name,
                'surname' => $values->surname,
                'password' => Passwords::hash($values->password1),
                'register_ip' => $_SERVER['REMOTE_ADDR']
            ]);
 
            $idUser = $this->db->getInsertId();

            $slug = $idUser . '_' . Nette\Utils\Strings::webalize($values->name . '_' . $values->surname);
            $this->db->query("UPDATE user SET ", ['slug' => $slug], "WHERE id=?", $idUser);
            $this->db->commit();
            return $idUser;
        } catch (Nette\Database\UniqueConstraintViolationException $e) {
            throw new DuplicateNameException;
        }
    }
    
    public function updatePassword(User $user, $password) 
    {
        $this->db->query("UPDATE user", ['password' =>  Passwords::hash($password)], "WHERE id=?", $user->id);
    }

    public function get($idUser, $slug = false)
    {
        
        if($slug) {
            $userData = $this->db->fetch("SELECT * FROM user WHERE slug=?", $idUser);  
        } else {
            $userData = $this->db->fetch("SELECT * FROM user WHERE id=?", $idUser); 
        }
        if($userData) {       
            return new User($userData);
        } else {
            return null;
        }
    }

    public function assignProfileImage(User $user, $file)
    {
        $this->db->query("UPDATE user SET", ['profile_image' => $file['fullPath']], "WHERE id=?", $user->id);
    }
    
    public function assignBackgroundImage(User $user, $file)
    {
        $this->db->query("UPDATE user SET", ['background_image' => $file['fullPath']], "WHERE id=?", $user->id);
    }

    public function updateUser($values, $user)
    {
        $this->db->query("UPDATE user SET", [
            'name' => $values['name'],
            'email_notification' => $values['emailNotification'],
            'surname' => $values['surname'],
            'email' => $values['email'],
            'birthday' => $values['birthday']
        ], "WHERE id=?", $user->id);
    }

    public function getByName($name) {
        return $this->db->query("SELECT ID_USER FROM vw_user_detail WHERE USERNAME=?", $name)->fetchField();
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
        $this->db->query("UPDATE user SET email_verify=1 WHERE id=? AND email=?", $user->id, $email);
    }

    public function getUserByMail($email, $secret = null) 
    {
        if($secret !== null) {
            $idUser = $this->db->fetchSingle("SELECT id FROM user WHERE email=? AND secret=?", $email, $secret);
        } else {
            $idUser = $this->db->fetchSingle("SELECT id FROM user WHERE email=?", $email);
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
        $this->db->query("UPDATE user SET secret=? WHERE id=?", $secret, $idUser);
        return $secret;
    }
    
    public function searchGroupUser($term)
    {
        $return = [];
        $users = $this->db->fetchAll("SELECT * FROM user WHERE email LIKE %~like~ OR CONCAT(name, ' ', surname) LIKE %~like~", $term, $term);
        foreach($users as $user) {
            $return[] = new User($user);
        }
        return $return;
    }
}



class DuplicateNameException extends \Exception
{}
