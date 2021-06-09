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
    $object = getObject($aho_id);

    // Si l'objet n'existe pas, retourner -1
    if (!isset($object[0]['obj_scanne'])) {
        return -1;
    }

    return setObjectFound($aho_id);
}


// -------------------------------------- EN COURS


/**
 * Retourne un json     
 */
function getInventoryStats() {

}


/**
 * 
 */
function generateInventoryPDF () {

}


/* ******************************  LISTE DES OBJETS  ****************************** */


/**
 * Retourne toutes les informations de l'objet 
 * @param $aho_id string Identifiant AHO de l'objet recherché
 * @return array|null Tableau contenant les informations de l'objet ou null si l'objet n'existe pas
 */
function getObject ($aho_id) { 
    try{
        // Ouverture d'une connexion à la DB
        $dbh = conn_db(DB_NAME);

        // Requête
        $sql = 'SELECT *
                FROM tb_objet
                INNER JOIN tb_motif_suppression ON tb_objet.obj_fk_motif_suppression = tb_motif_suppression.motsup_id
                WHERE obj_aho = :aho_id;';

        // Préparation de la requête sur le serveur
        $stmt = $dbh->prepare($sql);

        $stmt->bindParam(':aho_id',$aho_id,PDO::PARAM_STR);

        // Exécution de la requête
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();

        // Récupère la première ligne de données
        return $stmt->fetchAll();
    }

    catch(PDOException $e){
        echo $e->getMessage();
        die();
    }
}


/**
 * Retourne la liste des objets, archivés ou non archivés suivant le paramètre passé
 * @param $$archived boolean True pour obtenir la liste des objets archivés, False pour obtenir la liste des objets non archivés
 * @return array Tableau de la liste des objets archivés ou non archivés 
 */
function getAllObjects ($archived) { 
    try{
        // Ouverture d'une connexion à la DB
        $dbh = conn_db(DB_NAME);

        $nulParameter = "";

        if ($archived) 
            $nulParameter = 'NOT NULL';
        else
            $nulParameter = 'NULL';

        // Requête
        $sql = "SELECT obj_id, obj_aho, obj_annee_entree, obj_annee_sortie, obj_scanne, obj_nom, obj_prix, motsup_id, motsup_libelle
                FROM tb_objet
                INNER JOIN tb_motif_suppression ON tb_objet.obj_fk_motif_suppression = tb_motif_suppression.motsup_id
                WHERE obj_annee_sortie IS $nulParameter  
                ORDER BY obj_aho DESC;";

        // Préparation de la requête sur le serveur
        $stmt = $dbh->prepare($sql);

        // Exécution de la requête
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();

        // Récupère la première ligne de données
        return $stmt->fetchAll();
    }

    catch(PDOException $e){
        echo $e->getMessage();
        die();
    }

    // Retourne un tableau d'enregistrement
    return $data;
}


/**
 * Insert un nouvel objet dans la base de données 
 * @param $aho_id string Identifiant AHO du nouvel objet 
 * @param $entryYear int Année d'entrée de l'objet dans l'inventaire 
 * @param $name string 
 * @param $price float 
 * @return int Nombre de lignes affectés
 */
function insertObject ($aho_id, $entryYear, $name, $price) { 
    try{
        // Ouverture d'une connexion à la DB
        $dbh = conn_db(DB_NAME);

        // Requête
        $sql = 'INSERT INTO tb_objet (obj_id, obj_aho, obj_annee_entree, obj_annee_sortie, obj_fk_motif_suppression, obj_scanne, obj_nom, obj_prix) 
                VALUES (null,:aho_id,:annee_entree,null,1,FALSE,:nom,:prix);';

        // Préparation de la requête sur le serveur
        $stmt = $dbh->prepare($sql);

        $stmt->bindParam(':aho_id',$aho_id,PDO::PARAM_STR);
        $stmt->bindParam(':annee_entree',$entryYear,PDO::PARAM_INT);
        $stmt->bindParam(':nom',$name,PDO::PARAM_STR);
        $stmt->bindParam(':prix',$price,PDO::PARAM_STR);

        // Exécution de la requête
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();

        // Retourne le nombre de lignes affectés 
        return $stmt->rowCount();
    }

    catch(PDOException $e){
        echo $e->getMessage();
        die();
    }
}


/**
 * Insert un nouvel objet dans la base de données 
 * 
 * @return int Nombre de lignes affectés
 */
function updateObject ($aho_id, $name, $price) { 
    try{
        // Ouverture d'une connexion à la DB
        $dbh = conn_db(DB_NAME);

        // Requête
        $sql = "UPDATE tb_objet
                SET obj_nom = :new_name, obj_prix = :new_price
                WHERE obj_aho = :aho_id";

        // Préparation de la requête sur le serveur
        $stmt = $dbh->prepare($sql);

        $stmt->bindParam(':new_name',$name,PDO::PARAM_STR);
        $stmt->bindParam(':new_price',$price,PDO::PARAM_STR);
        $stmt->bindParam(':aho_id',$aho_id,PDO::PARAM_STR);

        // Exécution de la requête
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();

        // Retourne le nombre de lignes affectés 
        return $stmt->rowCount();
    }

    catch(PDOException $e){
        echo $e->getMessage();
        die();
    }
}


/**
 * Archive un objet, en lui donnant une année de sortie d'inventaire et une raison de sortie 
 * @param $aho_id string Identifiant AHO de l'objet à archiver
 * 
 * 
 * @return array|null Tableau contenant les informations de l'objet ou null si l'objet n'existe pas
 */
function archiveObject ($aho_id, $removalYear, $removalReason) { 
    try{
        // Ouverture d'une connexion à la DB
        $dbh = conn_db(DB_NAME);

        // Requête
        $sql = 'UPDATE tb_objet
                SET obj_annee_sortie = :removal_year, obj_fk_motif_suppression = :removal_reason
                WHERE obj_aho = :aho_id;';

        // Préparation de la requête sur le serveur
        $stmt = $dbh->prepare($sql);

        $stmt->bindParam(':aho_id',$aho_id,PDO::PARAM_STR);
        $stmt->bindParam(':removal_year',$removalYear,PDO::PARAM_STR);
        $stmt->bindParam(':removal_reason',$removalReason,PDO::PARAM_STR);

        // Exécution de la requête
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();

        // Retourne le nombre de lignes affectés 
        return $stmt->rowCount();
    }

    catch(PDOException $e){
        echo $e->getMessage();
        die();
    }
}


/* ******************************  SYSTEME  ****************************** */


/**
 * Retourne la liste de toutes les raisons de sortie d'inventaire 
 * @return array|null Tableau contenant les raisons de sortie d'inventaire 
 */
function getRemovalReason () { 
    try{
        // Ouverture d'une connexion à la DB
        $dbh = conn_db(DB_NAME);

        // Requête
        $sql = 'SELECT * 
                FROM tb_motif_suppression;';

        // Préparation de la requête sur le serveur
        $stmt = $dbh->prepare($sql);

        // Exécution de la requête
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();

        // Récupère la première ligne de données
        $data = $stmt->fetchAll();
    }

    catch(PDOException $e){
        echo $e->getMessage();
        die();
    }

    // Retourne un tableau d'enregistrement
    return $data;
}


/* ******************************  GESTION JSON  ****************************** */


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
        return -2;
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
    if (!empty($data['removalReason'])) {
        $removalReason = $data['removalReason'];
    } else {
        echo "removalReason ";
        $isValid = false;
    }

    // Si tous les paramètres sont valides, effectuer l'archivage
    if ($isValid)
        return archiveObject ($aho_id, $removalYear, $removalReason);
    else 
        return -2;
}


/* ******************************  FONCTIONS INTERNES  ****************************** */


/**
 * Vérifie si tous les objets ont étés trouvés 
 * Retourne True si tous les objets ont été trouvés, False sinon 
 */
function checkInventoryFinished() {
    
    $objectsNumbers = getNumberOfObjects()[0]['COUNT(*)'];
    $foundedObjectsNumber = getNumbersOfFoundedObjects()[0]['COUNT(*)'];

    if ($objectsNumbers === $foundedObjectsNumber)
        return true;
    else
        return false;
}


/* ******************************  FONCTIONS INTERNES BASIQUES  ****************************** */


/**
 * Retourne le nombre d'objets enregistrés
 */
function getNumberOfObjects() {
    try{
        // Ouverture d'une connexion à la DB
        $dbh = conn_db(DB_NAME);

        // Requête
        $sql = "SELECT COUNT(*) 
                FROM tb_objet;";

        // Préparation de la requête sur le serveur
        $stmt = $dbh->prepare($sql);

        // Exécution de la requête
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();

        // Récupère la première ligne de données
        return $stmt->fetchAll();
    }

    catch(PDOException $e){
        echo $e->getMessage();
        die();
    }
}


/**
 * Retourne le nombre d'objets trouvés
 */
function getNumbersOfFoundedObjects() {
    try{
        // Ouverture d'une connexion à la DB
        $dbh = conn_db(DB_NAME);

        // Requête
        $sql = "SELECT COUNT(*) 
                FROM tb_objet 
                WHERE obj_scanne = TRUE;";

        // Préparation de la requête sur le serveur
        $stmt = $dbh->prepare($sql);

        // Exécution de la requête
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();

        // Récupère la première ligne de données
        return $stmt->fetchAll();
    }

    catch(PDOException $e){
        echo $e->getMessage();
        die();
    }
}


/**
 * Paramètre tous les objets comme non trouvés 
 */
function setAllObjectsNotFound() {
    try{
        // Ouverture d'une connexion à la DB
        $dbh = conn_db(DB_NAME);

        // Requête
        $sql = "UPDATE tb_objet
                SET obj_scanne = FALSE;";

        // Préparation de la requête sur le serveur
        $stmt = $dbh->prepare($sql);

        // Exécution de la requête
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();

        // Retourne le nombre de lignes affectés 
        return $stmt->rowCount();
    }

    catch(PDOException $e){
        echo $e->getMessage();
        die();
    }
}


/**
 * Parametre un objet comme trouvé avec l'identifiant AHO passé en paramètre 
 */
function setObjectFound($aho_id) { 
    try{
        // Ouverture d'une connexion à la DB
        $dbh = conn_db(DB_NAME);

        // Requête
        $sql = "UPDATE tb_objet
                SET obj_scanne = TRUE
                WHERE obj_aho = :aho_id;";

        // Préparation de la requête sur le serveur
        $stmt = $dbh->prepare($sql);

        $stmt->bindParam(':aho_id',$aho_id,PDO::PARAM_STR);

        // Exécution de la requête
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();

        // Retourne le nombre de lignes affectés 
        return $stmt->rowCount();
    }

    catch(PDOException $e){
        echo $e->getMessage();
        die();
    }
}
