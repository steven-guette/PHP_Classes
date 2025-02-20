<?php

/**
 * Classe Database : Gestion simplifiée des interactions avec une base de données MySQL en PDO.
 *
 * Cette classe permet d'effectuer des opérations courantes sur une base de données, telles que :
 * - Connexion et déconnexion à la base.
 * - Exécution de requêtes SQL sécurisées (SELECT, INSERT, UPDATE, DELETE).
 * - Gestion des résultats sous plusieurs formats (une ligne, plusieurs lignes, colonne spécifique).
 *
 * Exemple d'utilisation :
 * ```php
 * $db = new Database('localhost', 'nom_de_la_base', 'utilisateur', 'mot_de_passe');
 * $result = $db->readOne("SELECT * FROM users WHERE id = :id", [':id' => [1, PDO::PARAM_INT]]);
 * ```
 */
class Database {
    private string $hostname;   // Adresse du serveur de base de données
    private string $dbName;     // Nom de la base de données
    private string $username;   // Nom d'utilisateur pour la connexion
    private string $password;   // Mot de passe pour la connexion

    private ?object $connection = null; // Instance PDO pour la connexion
    protected ?object $cursor = null;   // Curseur pour stocker le résultat des requêtes

    protected bool $is_connected = false; // Indique si la connexion est active
    protected int $nbResults = 0;         // Nombre de résultats retournés par la dernière requête

    /**
     * Constructeur de la classe
     * Initialise les paramètres de connexion et établit la connexion à la base de données.
     */
    public function __construct(string $hostname, string $dbName, string $username, string $password) {
        $this->hostname = $hostname;
        $this->dbName   = $dbName;
        $this->username = $username;
        $this->password = $password;
        $this->connect();
    }

    /**
     * Destructeur de la classe
     * Ferme automatiquement la connexion à la base de données lorsqu'un objet Database est détruit.
     */
    public function __destruct() {
        $this->disconnect();
    }

    /** REGION METHODS **/

    /**
     * Ferme le curseur de la dernière requête exécutée.
     */
    private function closeCursor(): void {
        if (!is_null($this->cursor)) {
            $this->cursor->closeCursor(); // Libère le jeu de résultats
            $this->cursor = null; // Réinitialise le curseur
        }
    }

    /**
     * Exécute une requête SQL avec des paramètres sécurisés.
     *
     * @param string $query Requête SQL avec des paramètres nommés.
     * @param array $args Tableau associatif des valeurs à lier à la requête.
     * @param bool $closeCursor Indique s'il faut fermer le curseur après l'exécution.
     * @return bool Retourne true si la requête s'exécute avec succès, sinon false.
     */
    private function query(string $query, array $args, bool $closeCursor = true): bool {
        if (!$this->getConnection() || !$this->cursor = $this->getCursor($query))
            return false;

        // Lier les paramètres à la requête SQL
        foreach ($args as $marker => $values) {
            if (!is_array($values) || count($values) !== 2) {
                trigger_error("Le paramètre '$marker' doit être un tableau avec [valeur, type].", E_USER_WARNING);
                return false;
            }
            $this->cursor->bindValue($marker, $values[0], $values[1]);
        }

        $result = $this->cursor->execute(); // Exécute la requête
        $this->nbResults = $this->cursor->rowCount(); // Nombre de lignes affectées

        if ($closeCursor) $this->closeCursor();

        return $result;
    }

    /**
     * Lit les résultats d'une requête SELECT.
     *
     * @param string $query Requête SQL.
     * @param array $args Paramètres de la requête.
     * @param string $readType Mode de récupération ('one', 'all', 'column').
     * @param int|null $fetchType Mode de récupération des données (par défaut FETCH_ASSOC).
     * @return array|bool Retourne les résultats ou false en cas d'erreur.
     */
    private function read(string $query, array $args, string $readType, ?int $fetchType = null): bool|array {
        if (!$this->query($query, $args, false)) return false;

        switch ($readType) {
            case 'one':
                return $this->cursor->fetch($fetchType);
            case 'all':
                return $this->cursor->fetchAll($fetchType);
            case 'column':
                return $this->cursor->fetchColumn();
            default:
                trigger_error("Type de lecture '$readType' non valide.", E_USER_WARNING);
                return false;
        }
    }

    /**
     * Établit une connexion à la base de données avec PDO.
     */
    public function connect(): void {
        if ($this->getConnection()) {
            try {
                $this->connection = new PDO(
                    "mysql:host={$this->hostname};dbname={$this->dbName};charset=utf8",
                    $this->username, $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_PERSISTENT => true
                    ]
                );
                $this->is_connected = true;
            } catch (PDOException $e) {
                die("Erreur de connexion : {$e->getMessage()}");
            }
        }
    }

    /**
     * Ferme la connexion à la base de données.
     */
    public function disconnect(): void {
        $this->closeCursor();
        if ($this->isConnected()) {
            $this->connection = null;
            $this->is_connected = false;
        }
    }

    /**
     * Vérifie si la connexion est active.
     */
    public function isConnected(): bool {
        return $this->is_connected;
    }

    /**
     * Exécute une requête INSERT.
     * Retourne l'ID du dernier enregistrement si $lastIDNeeded est vrai.
     */
    public function create(string $query, array $args = [], bool $lastIDNeeded = false): bool|int {
        if ($this->query($query, $args))
            return ($lastIDNeeded) ? (int) $this->connection->lastInsertId() : true;
        return false;
    }

    /**
     * Lit une seule ligne d'un résultat SQL.
     */
    public function readOne(string $query, array $args = [], int $fetchType = PDO::FETCH_ASSOC): bool|array {
        $result = $this->read($query, $args, 'one', $fetchType);
        $this->closeCursor();
        return $result;
    }

    /**
     * Lit toutes les lignes d'un résultat SQL.
     */
    public function readAll(string $query, array $args, int $fetchType = PDO::FETCH_ASSOC): bool|array {
        $result = $this->read($query, $args, 'all', $fetchType);
        $this->closeCursor();
        return $result;
    }

    /**
     * Lit une colonne spécifique d'un résultat SQL.
     */
    public function readColumn(string $query, array $args): bool|array {
        $result = $this->read($query, $args, 'column');
        $this->closeCursor();
        return $result;
    }

    /**
     * Exécute une requête UPDATE.
     */
    public function update(string $query, array $args): bool {
        return $this->query($query, $args);
    }

    /**
     * Exécute une requête DELETE.
     */
    public function delete(string $query, array $args): bool {
        return $this->query($query, $args);
    }

    /** END REGION **/

    /** REGION GETTERS **/

    /**
     * Vérifie et active la connexion si nécessaire.
     */
    public function getConnection(): bool {
        $this->connect();
        return $this->isConnected();
    }

    /**
     * Prépare une requête SQL et retourne un objet PDOStatement.
     */
    public function getCursor(string $query): PDOStatement {
        return $this->connection->prepare($query);
    }

    /**
     * Retourne le nombre de résultats affectés par la dernière requête.
     */
    public function getNbResults(): int {
        return $this->nbResults;
    }

    /** END REGION **/
}


