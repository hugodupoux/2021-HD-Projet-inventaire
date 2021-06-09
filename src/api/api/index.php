<?php
/**
 * @Author: Hugo Dupoux
 * @filename : api/index.php
 * @creation : 08/06/2021
 * @last_modification : 09/06/2021
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
 * Retourne -1 si tous les objets ne sont pas trouvés, ou 1 si l'opérationa réussie 
 */
route('post', $sub_dir . '/inventory', function ($matches, $rxd) {

    $affectedLines = startInventory();
    
    if ($affectedLines === -1) {
        http_response_code(400);
    } else {
        http_response_code(200);
    }

    header('Content-Type: application/json');
    //echo $received_json;
    echo $affectedLines;
    exit();
});


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

// -------------------------------------- EN COURS

/**
 * Retourne les statistiques de l'inventaire  
 */
route('get', $sub_dir . '/inventory/([A-Z-0-9]+)/stats', function ($matches, $rxd) {
    $inv_id = $matches[1][0];

    $data = getInventoryStats(inv_id);

    http_response_code(200);
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
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
});


/* ******************************  LISTE DES OBJETS  ****************************** */


/**
 * Retourne les caractéristiques d'un objet par rapport à son identifiant AHO  
 */
route('get', $sub_dir . '/objects/([A-Z-0-9]+)', function ($matches, $rxd) {
    $aho_id = $matches[1][0];
  
    $data = getObject($aho_id);

    if ($data == null) {
        http_response_code(404);
    } else {
        http_response_code(200);
    }

    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
});


/**
 * Retourne la liste de tous les objets 
 */
route('get', $sub_dir . '/objects', function ($matches, $rxd) {
    $data = getAllObjects(false);

    if ($data == null) {
        http_response_code(404);
    } else {
        http_response_code(200);
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
});


/**
 * Retourne la liste de tous les objets archivés 
 */
route('get', $sub_dir . '/objects/archive', function ($matches, $rxd) {
    $data = getAllObjects(true);

    if ($data == null) {
        http_response_code(404);
    } else {
        http_response_code(200);
    }
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

    $data = json_decode($received_json, true);

    $affectedLines = insertFromJSON($received_json);
    
    if ($affectedLines === -2) {
        http_response_code(400);
    } else {
        http_response_code(200);
    }

    header('Content-Type: application/json');
    //echo $received_json;
    echo $affectedLines;
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

    if ($data == null) {
        http_response_code(404);
    } else {
        http_response_code(200);
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
});



// si l'url ne correspond à aucune route
$data = [];
$data = [
   "error"     => "Route invalide"
];

http_response_code(400);
header('Content-Type: application/json');
echo json_encode($data, JSON_FORCE_OBJECT);
