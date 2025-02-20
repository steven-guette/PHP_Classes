<?php

/**
 * Classe finale ToolBox : Fournit des outils utiles pour la gestion des chaînes et des requêtes HTTP.
 *
 * Cette classe contient :
 * - Une méthode pour mettre la première lettre en majuscule (`UpFirstLetter`).
 * - Des méthodes pour récupérer et filtrer les requêtes HTTP (`getHTTPRequests`, `getHTTPRequestByTag`).
 */
final class ToolBox {

    /**
     * Met en majuscule la première lettre d'une chaîne de caractères.
     *
     * @param string $value La chaîne de caractères à modifier.
     * @param bool $allWords Si `true`, met en majuscule la première lettre de chaque mot (sinon seulement la première lettre de la phrase).
     * @return string La chaîne modifiée avec la première lettre en majuscule.
     */
    public static function UpFirstLetter(string $value, bool $allWords = false): string {
        return ($allWords)
            ? ucwords(strtolower($value)) // Met en minuscule toute la chaîne, puis met en majuscule chaque mot.
            : ucfirst(strtolower($value)); // Met en minuscule toute la chaîne, puis met en majuscule uniquement la première lettre.
    }

    /**
     * Récupère les données d'une requête HTTP (GET ou POST), en excluant certaines clés.
     *
     * @param array $excludes Liste des clés à exclure du résultat.
     * @return array Retourne un tableau contenant les paramètres de la requête HTTP.
     */
    public static function getHTTPRequests(array $excludes = []): array {
        $results = []; // Initialise un tableau vide pour stocker les résultats.

        // Vérifie si `$_GET` contient des données valides et sécurisées.
        if (WShield::IsValidArray($_GET))
            $results = $_GET; // Si oui, utilise `$_GET`.
        // Sinon, vérifie si `$_POST` contient des données valides et sécurisées.
        else if (WShield::IsValidArray($_POST))
            $results = $_POST; // Si oui, utilise `$_POST`.

        // Vérifie que les exclusions et les résultats sont des tableaux valides avant de filtrer.
        if (WShield::IsValidSeveralArrays([$excludes, $results])) {
            foreach ($excludes as $exclude)
                if (isset($results[$exclude])) unset($results[$exclude]); // Supprime les clés à exclure.
        }

        return $results; // Retourne le tableau filtré.
    }

    /**
     * Récupère la valeur d'un paramètre HTTP (GET ou POST) de manière sécurisée.
     *
     * @param string $key Nom du paramètre à récupérer.
     * @param mixed $defaultValue Valeur par défaut si la clé n'existe pas.
     * @return string Valeur du paramètre nettoyée, ou la valeur par défaut si absente.
     */
    public static function getHTTPRequestByTag(string $key, mixed $defaultValue = null): string {
        $result = $defaultValue; // Initialise la variable avec la valeur par défaut.

        // Vérifie si le paramètre existe dans `$_GET`, le nettoie et l'assigne à `$result`.
        if (isset($_GET[$key]))
            $result = htmlspecialchars(trim($_GET[$key]), ENT_QUOTES, 'UTF-8'); // Évite les injections XSS.
        // Si absent dans `$_GET`, vérifie s'il existe dans `$_POST`, puis applique la même sécurisation.
        else if (isset($_POST[$key]))
            $result = htmlspecialchars(trim($_POST[$key]), ENT_QUOTES, 'UTF-8');

        return $result ?: $defaultValue; // Retourne la valeur nettoyée ou la valeur par défaut si vide.
    }
}

