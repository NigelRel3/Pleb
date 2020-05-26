<?php
namespace Pleb\dataaccess;

class User    {
    private $pdo = null;
    private $userData = null;
    
    public function __construct( \PDO $pdo )    {
        $this->pdo = $pdo;
    }
    
    public function login ( string $userName, string $password ) : bool   {
        $loginOK = false;
        $sql = "select `id`, `password`, `uuid`
                    from `users`
                    where `name` = :name";
        $query = $this->pdo->prepare($sql);
        $query->execute( ["name" => $userName]);
        if ( $userData = $query->fetch() )  {
            if ( password_verify($password, $userData['password']) )    {
                $loginOK = true;
                $this->userData = $userData;
            }
        }
        
        return $loginOK;
    }
    
    public function getUUID() : string  {
        return $this->userData['uuid'] ?? '';
    }
}