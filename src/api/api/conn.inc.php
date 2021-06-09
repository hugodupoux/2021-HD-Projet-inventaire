<?php
/**
 * @Author: Hugo Dupoux
 * @filename : conn.inc.php
 * @creation : 08/06/2021
 * @last_modification : 08/06/2021
 */

function conn_db($base)
{
    $user='root';
    $pass='';

	$dsn='mysql:host=localhost;dbname='.$base.';charset=UTF8';
    try
    {

        $dbh = new PDO($dsn, $user, $pass);
        /*** les erreurs sont gérées par des exceptions ***/
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbh;
    }
    catch (PDOException $e)
    {
        print "erreur ! :". $e->getMessage()."<br/>";
        die();
    }
}


