<?php

$mode="Export";
$mode="Import";
$table="userInfo";
$table="templates";
try
{
    $db = new PDO("mysql:host=172.17.0.4;dbname=Pleb",
        "root", 'a177fgvTRw');
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $column =($table == "userInfo") ? "info" : "template";
    if ( $mode === "Export" ){
        $query = "SELECT * FROM $table";
        
        $sql = $db->prepare($query);
        $sql->execute();
        $info=$sql->fetchAll(PDO::FETCH_ASSOC);
            
        foreach ( $info as &$row )   {
            $row[$column] = json_decode($row[$column], true);
        }
        file_put_contents("./Data/$table.json", json_encode( $info, JSON_PRETTY_PRINT));
    }
    else    {
        if ( $table == "userInfo" ) {
            $query = "INSERT INTO userInfo (`id`, `userID`, `infoType`, `info`)
                VALUES (:id, :userID, :infoType, :info)
                ON DUPLICATE KEY UPDATE
                    infoType = :infoType,
                    info = :info";
        }
        else    {
            $query = "INSERT INTO templates (`id`, `name`, `auto`, `template`)
                VALUES (:id, :name, :auto, :template)
                ON DUPLICATE KEY UPDATE
                    name = :name,
                    auto = :auto,
                    template = :template";
        }
        
        $info = json_decode(file_get_contents("./Data/$table.json"), true);
        $sql = $db->prepare($query);
        foreach ( $info as $row )   {
            $row[$column] = json_encode($row[$column]);
            $sql->execute($row);
        }
    }
    echo "Complete";
}
catch (PDOException $e)
{
    echo $e;
}

