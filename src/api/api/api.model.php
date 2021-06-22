<?php
/**
 * @Author: Hugo Dupoux
 * @folder : IoT/Site
 * @filename : api.model.php
 * @creation : 08/06/2021
 * @last_modification : 22/06/2021
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
        return OBJECT_DOESNT_EXIST;
    }
}


// -------------------------------------- EN COURS


/**
 * Retourne un tableau des statistiques de l'inventaire en cours 
 */
function getInventoryStats() {
    // Si l'inventaire est fini, retourner un tableau vide
    if (checkInventoryFinished()) 
        return array();

    $numberOfObject = getNumberOfObjects();
    $numberOfFoundedObject = getNumbersOfFoundedObjects();
    if ($numberOfFoundedObject == 0)
        $percentOfObjectFounded = 0;
    else 
        $numberOfNotFoundedObject = $numberOfObject - $numberOfFoundedObject;

    // Retourne le tableau 
    return array ("numberOfObject" => $numberOfObject,
                  "numberOfFoundedObject" => $numberOfFoundedObject,
                  "numberOfNotFoundedObject" => $numberOfNotFoundedObject);
}


// -------------------------------------- EN COURS

/**
 * 
 */
function generateInventoryPDF () {

}


/* ******************************  GESTION JSON  ****************************** */

/**
* Insert un objet avec le json passé en paramètre
* @param json chaine json contenant les paramètre pour l'nisertion 
* @return int Retourne OBJECT_DOESNT_EXIST si l'objet n'existe pas, INVALID_JSON si json invalide, 0 si aucune ligne affectée, 1 si réussi
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
        return INVALID_JSON;        
}


/**
 * Modifie des champs d'un objet avec le json passé en paramètre
 * @param json chaine json contenant les paramètre de la modification
 * @return int Retourne OBJECT_DOESNT_EXIST si l'objet n'existe pas, INVALID_JSON si json invalide, 0 si aucune ligne affectée, 1 si réussi
 */
function updateFromJSON ($json) {

    $data = json_decode($json, true);

    $isValid = true;

    $aho_id = '';
    $objectScanned = null;
    $name = null;
    $price = null;
    $removalReason = null;
    $removalYear = null;

    // Gestion de l'identifiant AHO
    if (isset($data['aho_id'])) {
        $aho_id = $data['aho_id'];
    } else {
        echo "aho_id ";
        $isValid = false;
    }

    // Vérifier si l'objet existe 
    if (!checkObjectExist($aho_id)) {
        echo "object_doesnt_exist ";
        return OBJECT_DOESNT_EXIST;
    }

    // Gestion de l'objet scanné ou non 
    if (isset($data['obj_scanne'])) {
        $objectScanned = $data['obj_scanne'];
    } 

    // Gestion du nom de l'objet 
    if (isset($data['name'])) {
        $name = $data['name'];
    } 

    // Gestion du prix de l'objet 
    if (isset($data['price'])) {
        $price = $data['price'];
    } 

    // Gestion de la raison de sortie de l'inventaire 
    if (isset($data['removalReason'])) {
        $removalReason = $data['removalReason'];
    } 

    // Gestion de la raison de l'année de sortie de l'inventaire 
    if (isset($data['removalYear'])) {
        $removalYear = $data['removalYear'];
        if (!checkRemovalReason($removalReason)) {
            echo "removalReason_invalid_fk ";
            $isValid = false;
        }
    } 

    // Vérifie si les deux champs de sortie d'inventaire sont spécifiés
    if (isset($data['removalReason']) || isset($data['removalYear'])) {
        if (empty($removalReason) || empty($removalYear)) { 
            echo "removalReason & removalYear ";
            $isValid = false;
        } 
    }

    // Si tous les paramètres oligatoires sont valides, effectuer l'archivage
    if ($isValid) 
        return updateObject ($aho_id, $objectScanned, $name, $price, $removalReason, $removalYear);
    else
        return INVALID_JSON;
}


/* ******************************  FONCTIONS INTERNES  ****************************** */


/**
 * Vérifie si tous les objets ont étés trouvés 
 *@return boolean True si tous les objets ont été trouvés, False sinon 
 */
function checkInventoryFinished() {
    
    $objectsNumbers = getNumberOfObjects();
    $foundedObjectsNumber = getNumbersOfFoundedObjects();

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


/**
 * Vérifie si l'id passé en paramètre existe dans la table des raisons de sortie d'inventaire 
 * @return boolean True si l'id passé en paramètre existe, false sinon 
 */
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
 * Retourne un code HTTP en fonction dzu tableau passp en paramètre
 * @param array tableau à contrôler
 * @return boolean 404 si tableau vide, 200 sinon 
 */
function HTTPCodeGet ($array) {
    if (empty($array)) 
        return 404;
    else
        return 200;
}
