<?php
/**
 * Fonctions de base de données réutilisables
 */

/**
 * Exécute une requête préparée et retourne le résultat
 * 
 * @param string $query La requête SQL avec placeholders
 * @param array $params Les paramètres pour la requête préparée
 * @param string $types Les types de données (i: integer, s: string, d: double, b: blob)
 * @return mixed Résultat de la requête ou false en cas d'erreur
 */
function executeQuery($query, $params = [], $types = null) {
    global $conn;
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Erreur de préparation de requête: " . $conn->error);
        return false;
    }
    
    if (!empty($params)) {
        if ($types === null) {
            $types = str_repeat('s', count($params));
        }
        
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Erreur d'exécution de requête: " . $stmt->error);
        return false;
    }
    
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}

/**
 * Récupère une seule ligne de résultat
 * 
 * @param string $query La requête SQL
 * @param array $params Les paramètres
 * @param string $types Les types de données
 * @return array|null La ligne de résultat ou null
 */
function fetchRow($query, $params = [], $types = null) {
    $result = executeQuery($query, $params, $types);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Récupère toutes les lignes de résultat
 * 
 * @param string $query La requête SQL
 * @param array $params Les paramètres
 * @param string $types Les types de données
 * @return array Les lignes de résultat
 */
function fetchAll($query, $params = [], $types = null) {
    $result = executeQuery($query, $params, $types);
    $rows = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

/**
 * Insère des données dans une table
 * 
 * @param string $table Nom de la table
 * @param array $data Données à insérer (clé => valeur)
 * @return int|bool ID de la dernière insertion ou false
 */
function insert($table, $data) {
    global $conn;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $params = array_values($data);
    
    $result = executeQuery($query, $params);
    
    if ($result) {
        return $conn->insert_id;
    }
    
    return false;
}

/**
 * Met à jour des données dans une table
 * 
 * @param string $table Nom de la table
 * @param array $data Données à mettre à jour (clé => valeur)
 * @param string $condition Condition WHERE
 * @param array $conditionParams Paramètres pour la condition
 * @return bool Succès ou échec
 */
function update($table, $data, $condition, $conditionParams = []) {
    $setClause = implode(' = ?, ', array_keys($data)) . ' = ?';
    
    $query = "UPDATE $table SET $setClause WHERE $condition";
    $params = array_merge(array_values($data), $conditionParams);
    
    $result = executeQuery($query, $params);
    
    return $result !== false;
}

/**
 * Supprime des données d'une table
 * 
 * @param string $table Nom de la table
 * @param string $condition Condition WHERE
 * @param array $params Paramètres pour la condition
 * @return bool Succès ou échec
 */
function delete($table, $condition, $params = []) {
    $query = "DELETE FROM $table WHERE $condition";
    
    $result = executeQuery($query, $params);
    
    return $result !== false;
}

/**
 * Vérifie si une valeur existe déjà dans une table
 * 
 * @param string $table Nom de la table
 * @param string $column Nom de la colonne
 * @param mixed $value Valeur à vérifier
 * @param string $excludeIdColumn Colonne d'ID à exclure (pour les mises à jour)
 * @param mixed $excludeIdValue Valeur d'ID à exclure
 * @return bool True si la valeur existe, false sinon
 */
function valueExists($table, $column, $value, $excludeIdColumn = null, $excludeIdValue = null) {
    $query = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
    $params = [$value];
    
    if ($excludeIdColumn !== null && $excludeIdValue !== null) {
        $query .= " AND $excludeIdColumn != ?";
        $params[] = $excludeIdValue;
    }
    
    $result = fetchRow($query, $params);
    
    return $result && $result['count'] > 0;
}

/**
 * Vérifie si une entité peut être supprimée en vérifiant les dépendances
 * 
 * @param string $table Table à vérifier
 * @param string $idColumn Colonne d'ID
 * @param mixed $idValue Valeur d'ID
 * @param array $dependencies Tableau de dépendances [table => colonne]
 * @return array Résultat [canDelete => bool, dependencies => array]
 */
function canDeleteEntity($table, $idColumn, $idValue, $dependencies) {
    $canDelete = true;
    $usedIn = [];
    
    foreach ($dependencies as $depTable => $depColumn) {
        $query = "SELECT COUNT(*) as count FROM $depTable WHERE $depColumn = ?";
        $result = fetchRow($query, [$idValue]);
        
        if ($result && $result['count'] > 0) {
            $canDelete = false;
            $usedIn[] = $depTable;
        }
    }
    
    return [
        'canDelete' => $canDelete,
        'dependencies' => $usedIn
    ];
}