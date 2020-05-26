<?php
namespace Pleb\dataaccess;

class Template    {
    private $pdo = null;
    
    public function __construct( \PDO $pdo )    {
        $this->pdo = $pdo;
    }
    
    public function fetch ( string $name )   {
        $sql = "select `template`, `auto`
                from `templates` t
                where t.`name` = :name";
        $query = $this->pdo->prepare($sql);
        $query->execute( ["name" => $name  ]);
        return $query->fetch();
    }
    
    public function fetchAuto ( string $name )   {
        $sql = "select `template`
                from `templates` t
                where t.`name` = :name and t.`auto` = true";
        $query = $this->pdo->prepare($sql);
        $query->execute( ["name" => $name  ]);
        if ( $template = $query->fetch() )  {
            $template = $template['template'];
        }
        return $template;
    }
    
}