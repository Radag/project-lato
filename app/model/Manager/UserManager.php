<?php

namespace App\Model\Manager;

use Nette;
use Nette\Security\Passwords;
use Nette\Security\IIdentity;
use App\Model\Entities\User;

class UserManager implements Nette\Security\IAuthenticator
{
    use Nette\SmartObject;
    
    /** @var \Dibi\Connection  */
    protected $db;
    
    public function __construct(
        \Dibi\Connection $db
    )
    {
        $this->db = $db;
    }

    public $freeLogin = false;
    
    public function authenticate(array $credentials): IIdentity
    {
        $passwords = new Passwords();
        list($email, $password) = $credentials;
        $row = $this->db->fetch("SELECT * FROM user_real JOIN user USING (id) WHERE email=?", $email);
        if(!$this->freeLogin) {
            if (!$row) {
                throw new Nette\Security\AuthenticationException('Špatné uživatelské jméno nebo heslo.', self::IDENTITY_NOT_FOUND);
            } elseif (!$passwords->verify($password, $row->password)) {
                throw new Nette\Security\AuthenticationException('Špatné uživatelské jméno nebo heslo.', self::INVALID_CREDENTIAL);
            } elseif ($passwords->needsRehash($row->password)) {
                $this->db->query("UPDATE user_real ", ['password' => $passwords->hash($password)], "WHERE id=?", $row->id);
            }
        }
        
        $this->setLastLogin($row->id);

        $arr = $row->toArray();
        unset($arr['password']);
        return new Nette\Security\Identity($row->id, 'user', $arr);
    }

    public function setLastLogin($idUser)
    {
        $this->db->query("UPDATE user_real SET last_login=NOW(), last_login_ip=? WHERE id=?",  $_SERVER['REMOTE_ADDR'], $idUser);
    }

    public function add($values)
    {
        try {
            $passwords = new Passwords();
            $this->db->begin();
            $this->db->query("INSERT INTO user", [
                'name' => $values->name,
                'surname' => $values->surname
            ]);
            $idUser = $this->db->getInsertId();
            
            $this->db->query("INSERT INTO user_real", [
                'email' => $values->email,
                'password' => $passwords->hash($values->password1),
                'register_ip' => $_SERVER['REMOTE_ADDR'],
                'id' => $idUser,
                'role' => $values->role
            ]);
 
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
        $passwords = new Passwords();
        $this->db->query("UPDATE user_real SET ", ['password' => $passwords->hash($password)], "WHERE id=?", $user->id);
    }

    public function get($idUser, $slug = false, $includeFictive = false) : ?User
    { 
        if($slug) {
            $userData = $this->db->fetch("SELECT * FROM user JOIN user_real USING(id) WHERE slug=?", $idUser);  
        } else {
            if($includeFictive) {
                $userData = $this->db->fetch("SELECT * FROM user WHERE id=?", $idUser); 
            } else {
                $userData = $this->db->fetch("SELECT * FROM user JOIN user_real USING(id) WHERE id=?", $idUser); 
            }            
        }
        if($userData) {
			$user = new User($userData);
			$user->roles[] = $userData->role;
            return $user;
        } else {
            return null;
        }
    }
        
    public function getMultiple($ids, $slug = false, $includeFictive = false)
    { 
        $return = [];
        if(!empty($ids)) {
            if($slug) {
                $usersData = $this->db->fetchAll("SELECT * FROM user JOIN user_real USING(id) WHERE slug IN (?)", $ids);  
            } else {
                if($includeFictive) {
                    $usersData = $this->db->fetchAll("SELECT * FROM user LEFT JOIN user_real USING(id) WHERE id IN (?)", $ids);  
                } else {
                    $usersData = $this->db->fetchAll("SELECT * FROM user JOIN user_real USING(id) WHERE id IN (?)", $ids);  
                    
                }
                
            }
            foreach($usersData as $user) {
                $return[] = new User($user);
            }
        }        
        return $return;
    }

    public function assignProfileImage(User $user, \App\Model\Entities\File $file)
    {
        $this->db->query("UPDATE user_real SET", ['profile_image' => $file->fullPath], "WHERE id=?", $user->id);
    }
    
    public function assignBackgroundImage(User $user, $file)
    {
        $this->db->query("UPDATE user_real SET", ['background_image' => $file['fullPath']], "WHERE id=?", $user->id);
    }

    public function updateUser($values, $user)
    {
        $this->db->query("UPDATE user SET", [
            'name' => $values['name'],
            'surname' => $values['surname']
        ], "WHERE id=?", $user->id);
        
        $this->db->query("UPDATE user_real SET", [
            'email_notification' => $values['emailNotification'],
            'email' => $values['email'],
            'birthday' => $values['birthday'] ? $values['birthday'] : null
        ], "WHERE id=?", $user->id);
    }

    public function verifyEmail(User $user, $email) 
    {
        $this->db->query("UPDATE user_real SET email_verify=1 WHERE id=? AND email=?", $user->id, $email);
    }

    public function getUserByMail($email, $secret = null) 
    {
        if($secret !== null) {
            $idUser = $this->db->fetchSingle("SELECT id FROM user_real WHERE email=? AND secret=?", $email, $secret);
        } else {
            $idUser = $this->db->fetchSingle("SELECT id FROM user_real WHERE email=?", $email);
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
        $this->db->query("UPDATE user_real SET secret=? WHERE id=?", $secret, $idUser);
        return $secret;
    }
    
    public function searchGroupUser($term, $bannedIds)
    {
        $return = [];
        $users = $this->db->fetchAll("SELECT * FROM user JOIN user_real USING(id) WHERE email LIKE %~like~ OR CONCAT(name, ' ', surname) LIKE %~like~ AND id NOT IN (?)", $term, $term, $bannedIds);
        foreach($users as $user) {
            $return[] = new User($user);
        }
        return $return;
    }
}



class DuplicateNameException extends \Exception
{}
