<?php

/**
 * Classe User : Gestion des utilisateurs.
 *
 * Cette classe permet :
 * - De récupérer les informations d'un utilisateur via son ID.
 * - De modifier son nom, email et mot de passe de manière sécurisée.
 * - D'obtenir ses informations d'accès et sa date d'inscription.
 */
class User {
    private static Database $DB; // Instance de la classe Database (statique pour être partagée par toutes les instances)
    private ?DateTime $dateRegistered; // Stocke la date d'inscription de l'utilisateur (nullable)

    private int $id;            // ID unique de l'utilisateur dans la base de données
    private string $username;   // Nom d'utilisateur
    private string $email;      // Adresse email
    private int $accessLevel;   // Niveau d'accès de l'utilisateur (ex: admin, utilisateur normal, etc.)

    private bool $initialized = false; // Indique si l'utilisateur a été correctement initialisé

    /**
     * Constructeur : Initialise un objet User en récupérant ses infos via son ID.
     *
     * @param Database $DB Instance de la base de données.
     * @param int $id Identifiant unique de l'utilisateur.
     */
    public function __construct(Database $DB, int $id) {
        self::$DB = $DB; // Stocke l'instance de la base de données (statique, donc partagée)

        // Vérifie que la connexion à la base est active et que l'ID utilisateur est valide
        if (self::$DB->getConnection() && $id > 0) {
            $this->id = $id; // Stocke l'ID utilisateur
            $this->initialize(); // Charge les données de l'utilisateur depuis la base
        }
    }

    /** REGION METHODS **/

    /**
     * Initialise l'utilisateur en récupérant ses données depuis la base de données.
     */
    private function initialize(): void {
        // Récupère les informations de l'utilisateur depuis la base
        if ($results = $this->getUserData()) {
            $this->username = $results['Username']; // Assigne le nom d'utilisateur
            $this->email = $results['Email']; // Assigne l'email
            $this->accessLevel = $results['Rank_Level']; // Assigne le niveau d'accès
            $this->initialized = true; // Marque l'utilisateur comme initialisé

            try {
                // Convertit la date d'inscription en objet DateTime
                $this->dateRegistered = new DateTime($results['Date_Registration']);
            } catch (Exception) {
                $this->dateRegistered = null; // Si erreur, met la date à null
            }
        }
    }

    /** END REGION **/

    /** REGION SETTERS **/

    /**
     * Met à jour le nom d'utilisateur.
     */
    public function setUsername(string $newUsername): bool {
        if (!self::$DB->getConnection()) return false; // Vérifie que la connexion est active

        // Requête SQL de mise à jour
        $query = "UPDATE `Users` SET Username = :username WHERE ID_User = :id_user;";
        $args = [
            ':username' => [$newUsername, PDO::PARAM_STR], // Paramètre sécurisé pour le nouveau nom
            ':id_user' => [$this->id, PDO::PARAM_INT] // Paramètre sécurisé pour l'ID utilisateur
        ];

        // Exécute la requête et met à jour l'attribut local en cas de succès
        if (self::$DB->update($query, $args)) {
            $this->username = $newUsername;
            return true;
        }
        return false;
    }

    /**
     * Met à jour l'adresse email de l'utilisateur.
     */
    public function setEmail(string $newEmail): bool {
        if (!self::$DB->getConnection()) return false; // Vérifie que la connexion est active

        // Requête SQL de mise à jour
        $query = "UPDATE `Users` SET Email = :email WHERE ID_User = :id_user;";
        $args = [
            ':email' => [$newEmail, PDO::PARAM_STR], // Paramètre sécurisé pour le nouvel email
            ':id_user' => [$this->id, PDO::PARAM_INT] // Paramètre sécurisé pour l'ID utilisateur
        ];

        // Exécute la requête et met à jour l'attribut local en cas de succès
        if (self::$DB->update($query, $args)) {
            $this->email = $newEmail;
            return true;
        }
        return false;
    }

    /**
     * Change le mot de passe après vérification de l'ancien.
     */
    public function setPassword(string $oldPassword, string $newPassword): bool {
        if (!self::$DB->getConnection()) return false; // Vérifie que la connexion est active

        // Récupère le mot de passe actuel depuis la base
        $query = "SELECT Password FROM `Users` WHERE ID_User = :id_user;";
        $args = [':id_user' => [$this->id, PDO::PARAM_INT]];
        $result = self::$DB->readOne($query, $args, PDO::FETCH_NUM);

        // Vérifie si l'ancien mot de passe est correct
        if ($result && password_verify($oldPassword, $result[0])) {
            // Hashe le nouveau mot de passe
            $query = "UPDATE `Users` SET Password = :password WHERE ID_User = :id_user;";
            $args = [
                ':password' => [password_hash($newPassword, PASSWORD_ARGON2ID), PDO::PARAM_STR],
                ':id_user' => [$this->id, PDO::PARAM_INT]
            ];

            return self::$DB->update($query, $args);
        }
        return false;
    }

    /** END REGION **/

    /** REGION GETTERS **/

    /**
     * Récupère les informations de l'utilisateur depuis la base.
     */
    private function getUserData(): array {
        // Requête SQL avec jointure pour récupérer les infos utilisateur + accès
        $query = "
            SELECT `Users`.Username, `Users`.Email, `Users`.Date_Registration, `Access`.Rank_Level
            FROM `Users`
            INNER JOIN `Access` ON `Users`.ID_User = `Access`.ID_User
            WHERE `Users`.ID_User = :id_user;
        ";
        $args = [':id_user' => [$this->id, PDO::PARAM_INT]]; // Paramètre sécurisé
        return self::$DB->readOne($query, $args); // Exécute la requête et retourne les résultats
    }

    /**
     * Vérifie si l'utilisateur est initialisé.
     */
    public function getInitialized(): bool {
        return $this->initialized;
    }

    /**
     * Retourne l'ID utilisateur.
     */
    public function getID(): int {
        return $this->id;
    }

    /**
     * Retourne le nom d'utilisateur.
     */
    public function getUsername(): string {
        return $this->username;
    }

    /**
     * Retourne l'adresse email.
     */
    public function getEmail(): string {
        return $this->email;
    }

    /**
     * Retourne la date d'inscription formatée.
     */
    public function getDateRegistered(string $format = 'd/m/Y'): string {
        return $this->dateRegistered->format($format);
    }

    /**
     * Retourne le niveau d'accès.
     */
    public function getAccessLevel(): int {
        return $this->accessLevel;
    }

    /** END REGION **/
}







