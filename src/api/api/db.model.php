<?php
/**
 * @Author: Hugo Dupoux
 * @folder : IoT/Site
 * @filename : db.model.php
 * @creation : 09/06/2021
 * @last_modification : 22/06/2021
 */


/* ******************************  INVENTAIRE  ****************************** */


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
        // SI l'objet existe déjà, retourner -1 
        return -1;
    }
}


/**
 * Modifie un objet dans la base de données 
 * @param aho_id ID AHO de l'objet à modifier
 * @param objectScanned True si l'objet est scanné, false sinon, null pour aucun changement
 * @param name Nom de l'objet, null pour aucun changement 
 * @param price Prix de l'objet, null pour aucun changement
 * @param removalReason FK de raison de sortie d'inventaire, null pour aucun changement, si spécifié paramètre removalYear obligatoire
 * @param removalYear Année de sortie d'inventaire, null pour aucun changement, si spécifié paramètre removalReason obligatoire
 * @return int Nombre de lignes affectés, ou -1 si il n'y aucun paramètre 
 */
function updateObject ($aho_id, $objectScanned, $name, $price, $removalReason, $removalYear) { 
    try{
        // Ouverture d'une connexion à la DB
        $dbh = conn_db(DB_NAME);

        // Assigne les champs de la requête SQL si les paramètres ne sont pas nuls
        $fieldObjectScanned = is_null($objectScanned) ? "" : "obj_scanne = :object_scanned";
        $fieldName = empty($name) ? "" : "obj_nom = :new_name";
        $fieldPrice = is_null($price) ? "" : "obj_prix = :new_price";
        $fieldRemovalReason = is_null($removalReason) ? "" : "obj_fk_motif_suppression = :removal_reason";
        $fieldRemovalYear = empty($removalYear) ? "" : "obj_annee_sortie = :removal_year";

        // Création d'un tableau de tous les paramètres de la requête
        $fields = array ($fieldObjectScanned, $fieldName, $fieldPrice, $fieldRemovalReason, $fieldRemovalYear);

        $firstField = true;
        $sqlParameter = "";

        // Concaténer tous les paramètres de la requête SQL avec des virgules
        foreach ($fields as &$field) { 
            if (!empty($field)) { 
                // Ajoute une virgule, sauf si c'est le premier champs 
                if (!$firstField) {
                    $sqlParameter = $sqlParameter . ", ";
                }
                $sqlParameter = $sqlParameter . $field;
                $firstField = false;
            }
        }

        // Si tous les paramètres sont à null, retourner -1
        if (empty($sqlParameter)) { 
            return -2;
        }

        // Concaténer la requête finale
        $sql = "UPDATE tb_objet SET " . $sqlParameter . " WHERE obj_aho = :aho_id";

        // Préparation de la requête sur le serveur
        $stmt = $dbh->prepare($sql);

        // Lier les paramètres si ils ne sont pas à null 
        $stmt->bindParam(':aho_id',$aho_id,PDO::PARAM_STR);
        if (!is_null($objectScanned))
            $stmt->bindParam(':object_scanned',$objectScanned,PDO::PARAM_STR);
        if (!empty($name))    
            $stmt->bindParam(':new_name',$name,PDO::PARAM_STR);
        if (!is_null($price))    
            $stmt->bindParam(':new_price',$price,PDO::PARAM_STR);
        if (!is_null($removalReason))    
            $stmt->bindParam(':removal_reason',$removalReason,PDO::PARAM_STR);
        if (!empty($removalYear))    
            $stmt->bindParam(':removal_year',$removalYear,PDO::PARAM_STR);

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
        return ($stmt->fetchAll())[0]['COUNT(*)'];
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
        return ($stmt->fetchAll())[0]['COUNT(*)'];
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

