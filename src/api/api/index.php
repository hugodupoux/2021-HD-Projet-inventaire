<?php
/**
 * @Author: Hugo Dupoux
 * @filename : api/index.php
 * @creation : 08/06/2021
 * @last_modification : 21/06/2021
 */

//chargement de la configuration
require 'config.inc.php';
require 'conn.inc.php';

//chargement des fonctions du model
require 'api.model.php';
require 'db.model.php';
require 'security.model.php';

require_once 'router.php';

// récupère l'url partielle vers le dossier en cours
$sub_dir = dirname($_SERVER['PHP_SELF']);


/* ******************************  INVENTAIRE  ****************************** */

/**
 * Permet de démarrer un inventaire si tous les objets sont trouvés
 * Ne retourne rien 
 */
route('post', $sub_dir . '/inventory', function ($matches, $rxd) {

    $state = startInventory();

    if ($state == SUCCESS) 
        $responseCode = 201;
    else
        $responseCode = 409;

    http_response_code($responseCode);
    exit();
});


/************************** A SUPPRIEMR  */
/**
 * Permet de paramétrer un objet comme trouvé
 * Retourne -2 si le json est invalide, -1 si l'objet est inexistant, 0 si l'objet est déjà trouvé, 1 si l'opération a réussie
 */
route('post', $sub_dir . '/scan', function ($matches, $rxd) {

    // Takes raw data from the request
    $received_json = (file_get_contents('php://input'));

    $data = json_decode($received_json, true);

    $affectedLines = -2;

    if (isset($data['aho_id'])) {
        $affectedLines = objectScanned($data['aho_id']);
    }

    http_response_code(getCodeHTTP($affectedLines));

    header('Content-Type: application/json');
    echo $affectedLines;
    exit();
});


/**
 * Retourne les statistiques de l'inventaire  
 */
route('get', $sub_dir . '/inventory', function ($matches, $rxd) {
    $data = getInventoryStats();

    http_response_code(HTTPCodeGet($data));

    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
});


// -------------------------------------- EN COURS

/**
 * Retourne les statistiques de l'inventaire  
 */
route('get', $sub_dir . '/inventory/([A-Z-0-9]+)/pdf', function ($matches, $rxd) {
    $inv_id = $matches[1][0];

    // retourner pdf 


    http_response_code(200);
    exit();
});


/* ******************************  LISTE DES OBJETS  ****************************** */


/**
 * Retourne les caractéristiques d'un objet par rapport à son identifiant AHO  
 */
route('get', $sub_dir . '/objects/([A-Z-0-9]+)', function ($matches, $rxd) {
    $aho_id = $matches[1][0];
  
    $data = getObject($aho_id);

    http_response_code(HTTPCodeGet($data));

    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
});


/**
 * Retourne la liste de tous les objets, prend un paramètre "archived" en header qui accepte uniquement "true" ou "false"
 */
route('get', $sub_dir . '/objects', function ($matches, $rxd) {
    $headers = getallheaders();

    if (isset($headers["archived"])) {
        if ($headers["archived"] == "true" || $headers["archived"] == "false") { 
            // Récupère la liste des objets (true = 1 ; false = 0)
            $data = getAllObjects(($headers["archived"] == "true") ? 1 : 0);

            http_response_code(HTTPCodeGet($data));
        
            header('Content-Type: application/json');
            echo json_encode($data);
            exit();
        }
    } 

    http_response_code(400);
    echo 'HTTP Header param "archived"';
    exit();
});


/**
 * Retourne la liste de tous les objets archivés 
 */
route('get', $sub_dir . '/objects/archive', function ($matches, $rxd) {
    $data = getAllObjects(true);

    http_response_code(HTTPCodeGet($data));

    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
});


/**
 * Permet d'insérer un objet
 * Retourne -1 si le json est invalide, nombre positif : nombre de lignes affectés (0 ou 1)
 */
route('post', $sub_dir . '/objects', function ($matches, $rxd) {

    // Takes raw data from the request
    $received_json = (file_get_contents('php://input'));

    $affectedLines = insertFromJSON($received_json);
    
    http_response_code(HTTPCodePost($affectedLines));

    header('Content-Type: application/json');
    echo $received_json;
    exit();
});


/**
 * Permet de modifier un objet
 * Retourne -2 si le json est invalide, -1 si l'objet est inexistant, 0 si l'objet est déjà trouvé, 1 si l'opération a réussie
 */
route('put', $sub_dir . '/objects', function ($matches, $rxd) {

    // Takes raw data from the request
    $received_json = (file_get_contents('php://input'));

    $data = json_decode($received_json, true);

    $affectedLines = updateFromJSON($received_json);

    http_response_code(getCodeHTTP($affectedLines));

    header('Content-Type: application/json');
    //echo $received_json;
    echo $affectedLines;
    exit();
});


/**
 * Permet d'archiver un objet
 * Retourne -1 si le json est invalide, nombre positif : nombre de lignes affectés (0 ou 1)
 */
route('put', $sub_dir . '/objects/archive', function ($matches, $rxd) {

    // Takes raw data from the request
    $received_json = (file_get_contents('php://input'));

    $data = json_decode($received_json, true);

    $affectedLines = archiveFromJSON($received_json);
    
    http_response_code(getCodeHTTP($affectedLines));

    header('Content-Type: application/json');
    echo $affectedLines;
    exit();
});


/* ******************************  SYSTEME  ****************************** */


/**
 * Retourne la liste de tous les motifs de sortie d'inventaire 
 */
route('get', $sub_dir . '/removal-reason', function ($matches, $rxd) {
    $data = getRemovalReason();

    http_response_code(HTTPCodeGet($data));

    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
});



// si l'url ne correspond à aucune route
$data = [
   "error"     => "Route invalide"
];

http_response_code(400);
header('Content-Type: application/json');
echo json_encode($data, JSON_FORCE_OBJECT);
