<?php
// Inclusion des dépendances nécessaires pour la classe
require_once __DIR__ . '/WShield.php'; // Sécurisation des entrées (WShield)
require_once __DIR__ . '/ToolBox.php'; // Outils divers (ToolBox)

/**
 * Classe Navigation : Gestion des chemins et fichiers principaux de l'application.
 *
 * Cette classe permet :
 * - De définir et récupérer le fichier principal à exécuter.
 * - De déterminer le répertoire contenant ces fichiers.
 * - De localiser dynamiquement le chemin racine du projet.
 */
class Navigation {

    private ?string $filesDir = null; // Dossier contenant les fichiers de navigation (ex: 'pages/')
    private ?string $mainFilename = null; // Nom du fichier principal à charger (ex: 'login.php')
    private string $mainFilepath; // Chemin complet du fichier principal
    private string $defaultMainFileName; // Chemin complet par défaut du fichier principal

    private static string $rootpath; // Chemin racine du projet (statique, partagé entre toutes les instances)

    /**
     * Constructeur de la classe Navigation.
     *
     * @param string $mainFilenameKey Clé utilisée pour récupérer le nom du fichier via la requête HTTP.
     * @param string $filesDir Dossier contenant les fichiers de navigation.
     * @param string $defaultMainFilename Nom du fichier principal par défaut (ex: 'index').
     */
    public function __construct(string $mainFilenameKey, string $filesDir, string $defaultMainFilename) {
        $this->setDefaultMainFilename($defaultMainFilename); // Définit le fichier principal par défaut
        $this->setMainFilename($mainFilenameKey); // Détermine le fichier principal en fonction de la requête
        $this->setFilesDir($filesDir); // Définit le dossier contenant les fichiers
        $this->setMainFilepath(); // Construit le chemin complet du fichier principal
    }

    /** REGION SETTERS **/

    /**
     * Définit le nom du fichier principal à charger.
     *
     * @param string $mainFilenameKey Clé permettant d'obtenir le fichier via la requête HTTP.
     */
    private function setMainFilename(string $mainFilenameKey): void {
        // Vérifie si la clé est valide avec WShield (ex: éviter les caractères interdits)
        if (WShield::IsValidString($mainFilenameKey)) {
            // Récupère le nom du fichier à partir de la requête HTTP (GET ou POST)
            // Si la clé n'existe pas, on utilise le fichier par défaut
            $this->mainFilename = ToolBox::getHTTPRequestByTag($mainFilenameKey, $this->defaultMainFileName);
        }
    }

    /**
     * Définit le dossier contenant les fichiers de navigation.
     *
     * @param string $filesDir Nom du dossier (ex: 'pages').
     */
    private function setFilesDir(string $filesDir): void {
        // Vérifie si le nom du dossier est valide avant de l'affecter
        if (WShield::IsValidString($filesDir))
            $this->filesDir = $filesDir;
    }

    /**
     * Construit le chemin absolu du fichier par défaut à exécuter
     *
     * @param string $defaultMainFileName Nom du fichier par défaut (ex: 'home').
     */
    private function setDefaultMainFilename(string $defaultMainFileName): void {
        // Vérifie si le nom est valide avant de l'affecter
        if (WShield::IsValidString($defaultMainFileName))
            $this->defaultMainFileName = $defaultMainFileName;
    }

    /**
     * Construit le chemin absolu du fichier principal à exécuter.
     */
    private function setMainFilepath(): void {
        $filepath = self::getRootPath(); // Initialise une variable vide pour stocker le chemin

        // Vérifie si le dossier et le nom de fichier sont définis
        if (!is_null($this->filesDir))
            $filepath .= "/$this->filesDir";

        if (!is_null($this->mainFilename))
            $filepath .= "/$this->mainFilename.php";
        else
            $filepath .= "/$this->defaultMainFileName.php";

        // Vérifie si le fichier existe, sinon génère une erreur critique
        if (empty($filepath) || !file_exists($filepath))
            trigger_error("Ce chemin n'existe pas : $filepath", E_USER_ERROR);

        $this->mainFilepath = $filepath; // Stocke le chemin du fichier
    }

    /**
     * Définit dynamiquement le chemin racine du projet.
     *
     * Cette méthode parcourt les répertoires parent jusqu'à trouver `index.php`.
     */
    private static function setRootPath(): void {
        // Vérifie si le chemin racine a déjà été défini
        if (!isset(self::$rootpath)) {
            $currentPath = __DIR__; // Commence la recherche à partir du dossier actuel

            // Boucle pour remonter les répertoires jusqu'à trouver `index.php`
            while (!file_exists($currentPath . '/index.php')) {
                $parent = dirname($currentPath); // Obtient le dossier parent

                // Si on atteint la racine du système (impossible de remonter plus haut), on stoppe avec une alerte
                if ($parent == $currentPath) {
                    trigger_error("Impossible de trouver index.php", E_USER_WARNING);
                    return;
                }

                $currentPath = $parent; // Continue la remontée dans l'arborescence
            }

            self::$rootpath = $currentPath; // Stocke le chemin racine
        }
    }

    /** END REGION **/

    /** REGION GETTERS **/

    /**
     * Retourne le nom du fichier principal.
     *
     * @return string|null Nom du fichier ou `null` s'il n'est pas défini.
     */
    public function getMainFileName(): ?string {
        return $this->mainFilename;
    }

    /**
     * Retourne le nom du dossier contenant les fichiers de navigation.
     *
     * @return string|null Nom du dossier ou `null` s'il n'est pas défini.
     */
    public function getFilesDir(): ?string {
        return $this->filesDir;
    }

    /**
     * Retourne le nom du fichier principal par défaut.
     *
     * @return string Nom du fichier par défaut.
     */
    public function getDefaultMainFileName(): string {
        return $this->defaultMainFileName;
    }

    /**
     * Retourne le chemin absolu du fichier principal.
     *
     * @return string|null Chemin du fichier ou `null` s'il n'est pas défini.
     */
    public function getMainFilePath(): ?string {
        return $this->mainFilepath;
    }

    /**
     * Retourne le chemin racine du projet.
     *
     * @return string Chemin racine du projet.
     */
    public static function getRootPath(): string {
        self::setRootPath(); // Vérifie si le chemin racine est défini, sinon le définit
        return self::$rootpath;
    }

    /** END REGION **/
}