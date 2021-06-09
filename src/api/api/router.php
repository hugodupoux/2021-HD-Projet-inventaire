<?php
/**
 * @Author: Hugo Dupoux
 * @filename : api/router.php
 * @creation : 08/06/2021
 * @last_modification : 09/06/2021
 */

/**
 * @param $method : méthode nécessaire pour la route
 * @param $regex: modèle de la route à évaluer
 * @param $cb:  fonction de rappel invoqué si la route est bonne
 * @return int
 *
 * l'envoi doit être x-www-form-urlencode, hé mince
 */
function route ($method, $regex, $cb) {

    if( strtoupper($method) !== $_SERVER['REQUEST_METHOD'])
        return 0;

    $recieved_datas = [];

    switch ($_SERVER['REQUEST_METHOD']){

        case 'PUT':
            parse_str(file_get_contents("php://input"),$recieved_datas);
            break;

        case 'POST':
            $recieved_datas = $_POST;
            break;
    }

    $regex = str_replace('/', '\/', $regex);

    $is_match = preg_match('/^' . ($regex) . '$/', $_SERVER['REQUEST_URI'], $matches, PREG_OFFSET_CAPTURE);

    // appel la fonction passée en paramètre si il y a correspondence
    if ($is_match) {
        $cb($matches, $recieved_datas);
    }
}

