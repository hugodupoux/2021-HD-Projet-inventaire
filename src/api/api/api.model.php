<?php
/**
 * @Author: Hugo Dupoux
 * @folder : IoT/Site
 * @filename : api.model.php
 * @creation : 08/06/2021
 * @last_modification : 09/06/2021
 */


/* ******************************  INVENTAIRE  ****************************** */


/**
 * Permet de préparer la base de données à un nouvel inventaire en parametrant tous les colonnes obj_scanne à False
 */
function startInventory (){
    // vérifier que tous les objets soient non scannés 
    if (checkInventoryFinished()) { 
        setAllObjectsNotFound();
        return 1;
    } else { 
        return -1;
    }
}


/**
 * Permet de paramétrer un obejt comme scanné
 * @return int -1 si l'objet n'existe pas, 0 si l'objet est déjà trouvé, ou 1 si ça a fonctionné
 */
function objectScanned($aho_id) {
    // Si l'objet n'existe pas, retourner -1
    if (checkObjectExist($aho_id)) 
        return setObjectFound($aho_id);    
    else 
        return -1;    
}


// -------------------------------------- EN COURS


/**
 * Retourne un json     
 */
function getInventoryStats() {

}


// -------------------------------------- EN COURS

/**
 * 
 */
function generateInventoryPDF () {

}


/* ******************************  GESTION JSON  ****************************** */

function scanFromJSON($json) { 



}



/**
 * Permet d'extraire les données et de valider un json avant de modifier un objet 
 */
function insertFromJSON ($json) {

    $data = json_decode($json, true);

    $isValid = true;

    $aho_id = '';
    $entryYear = 0;
    $name = '';
    $price = 0;

    // Gestion de l'identifiant AHO
    if (!empty($data['aho_id'])) {
        $aho_id = $data['aho_id'];
    } else {
        echo "aho_id ";
        $isValid = false;
    }

    // Gestion de l'annnée d'entrée 
    if (!empty($data['entryYear'])) {
        $entryYear = $data['entryYear'];
    } else {
        echo "entryYear ";
        $isValid = false;
    }

    // Gestion du nom 
    if (!empty($data['name'])) {
        $name = $data['name'];
    } else {
        echo "name ";
        $isValid = false;
    }

    // Gestion du prix 
    if (!empty($data['price'])) {
        $price = $data['price'];
    } else {
        echo "price ";
        $isValid = false;
    }


    // Si tous les paramètres sont valides, effectuer l'archivage
    if ($isValid)
        return insertObject ($aho_id, $entryYear, $name, $price);
    else 
        return -2;
}


/**
 * Permet d'extraire les données et de valider un json avant de modifier un objet 
 */
function updateFromJSON ($json) {

    $data = json_decode($json, true);

    $isValid = true;

    $aho_id = '';
    $name = 0;
    $price = 0;

    // Gestion de l'identifiant AHO
    if (!empty($data['aho_id'])) {
        $aho_id = $data['aho_id'];
    } else {
        echo "aho_id ";
        $isValid = false;
    }

    // Gestion de l'année de sortie d'inventaire
    if (!empty($data['name'])) {
        $name = $data['name'];
    } else {
        echo "name ";
        $isValid = false;
    }

    // Gestion de la raison de sortie de l'inventaire 
    if (!empty($data['price'])) {
        $price = $data['price'];
    } else {
        echo "price ";
        $isValid = false;
    }

    // Si tous les paramètres sont valides, effectuer l'archivage
    if ($isValid)
        return updateObject ($aho_id, $name, $price);
    else 
        return INVALID_JSON;
}


/**
 * Permet d'extraire les données et de valider un json avant d'archiver un objet 
 */
function archiveFromJSON ($json) {

    $data = json_decode($json, true);

    $isValid = true;

    $aho_id = '';
    $removalYear = 0;
    $removalReason = 0;

    // Gestion de l'identifiant AHO
    if (!empty($data['aho_id'])) {
        $aho_id = $data['aho_id'];
    } else {
        echo "aho_id ";
        $isValid = false;
    }

    // Gestion de l'année de sortie d'inventaire
    if (!empty($data['removalYear'])) {
        $removalYear = $data['removalYear'];
    } else {
        echo "removalYear ";
        $isValid = false;
    }

    // Gestion de la raison de sortie de l'inventaire 
    if (!empty($data['removalReason']) && !checkRemovalReason($removalReason)) {
        $removalReason = $data['removalReason'];
    } else {
        echo "removalReason ";
        $isValid = false;
    }

    // Si tous les paramètres sont valides, effectuer l'archivage
    if ($isValid)
        return archiveObject ($aho_id, $removalYear, $removalReason);
    else 
        return INVALID_JSON;
}


/* ******************************  FONCTIONS INTERNES  ****************************** */


/**
 * Vérifie si tous les objets ont étés trouvés 
 *@return boolean True si tous les objets ont été trouvés, False sinon 
 */
function checkInventoryFinished() {
    
    $objectsNumbers = getNumberOfObjects()[0]['COUNT(*)'];
    $foundedObjectsNumber = getNumbersOfFoundedObjects()[0]['COUNT(*)'];

    if ($objectsNumbers === $foundedObjectsNumber)
        return true;
    else
        return false;
}


/**
 * Vérifie si un objet existe dans la base de données
 * @return boolean true si l'objet existe, false sinon
 */
function checkObjectExist($aho_id) {
    return !empty(getObject($aho_id));
}


function checkRemovalReason($removalReasonToCheck) {
    $removalReasonsList = getRemovalReason();

    foreach ($removalReasonsList as &$currentRemovalReason) {
        if ($currentRemovalReason['motsup_id'] == $removalReasonToCheck) {
            return true;
        }        
    }
    return false;
}


/* ******************************  FONCTIONS API  ****************************** */



/**
 * Permet de retourner 
 */
function getCodeHTTP($state) {

    $responseCode = 0;

    switch ($affectedLines) { 
        case INVALID_JSON: $responseCode = 400;
        break;
        case -1: $responseCode = 404;
        break;
        case 0: 
        case 1: $responseCode = 200;
        break;
    }

    return $responseCode;
}


