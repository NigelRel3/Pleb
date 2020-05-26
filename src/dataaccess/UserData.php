<?php
namespace Pleb\dataaccess;

class UserData    {
    private $pdo = null;
    
    public function __construct( \PDO $pdo )    {
        $this->pdo = $pdo;
    }
    
    public function fetch ( string $uuid, string $infoType )   {
        $sql = "select `info`
                from `userInfo` ui
                join `users` u on u.`id` = ui.`userID`
                where u.`uuid` = :uuid and ui.`infoType` = :infoType";
        $query = $this->pdo->prepare($sql);
        $query->execute( ["uuid" => $uuid, "infoType" => $infoType  ]);
        return $query->fetch()['info'];
    }
    
    public function save ( string $uuid, string $infoType, $data )   {
        $sql = "insert into `userInfo` (userID, infoType, info) 
                    select `id`, :infoType, :info
                        from `users`
                        where `uuid` = :uuid
                    on duplicate key update info = :info";
        
        $return = false;
        $query = $this->pdo->prepare($sql);
        if ( $query->execute( ["uuid" => $uuid, "infoType" => $infoType,
                "info" => json_encode($data) ] )) {
                $return = $this->pdo->lastInsertId("id");
        }
        return $return;
    }
}