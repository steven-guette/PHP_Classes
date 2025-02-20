<?php
/**
 * Classe `WShield`
 *
 * 🔥 Objectif :
 * - Fournir des méthodes utilitaires pour **valider des chaînes, des tableaux et des nombres**.
 * - Vérifier si une **chaîne est vide**, si un **tableau est vide**, ou si un **nombre est dans une plage donnée**.
 */
final class WShield {
    private const REGEX_IPV4 = '/^(?:(?:25[0-5]|2[0-4][0-9]|1?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|1?[0-9][0-9]?)$/';
    private const REGEX_IPV6 = '/^(
        ([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:)|
        ([0-9A-Fa-f]{1,4}:){1,7}:|
        ([0-9A-Fa-f]{1,4}:){1,6}:[0-9A-Fa-f]{1,4}|
        ([0-9A-Fa-f]{1,4}:){1,5}(:[0-9A-Fa-f]{1,4}){1,2}|
        ([0-9A-Fa-f]{1,4}:){1,4}(:[0-9A-Fa-f]{1,4}){1,3}|
        ([0-9A-Fa-f]{1,4}:){1,3}(:[0-9A-Fa-f]{1,4}){1,4}|
        ([0-9A-Fa-f]{1,4}:){1,2}(:[0-9A-Fa-f]{1,4}){1,5}|
        [0-9A-Fa-f]{1,4}:(:[0-9A-Fa-f]{1,4}){1,6}|
        :((:[0-9A-Fa-f]{1,4}){1,7}|:)|
        fe80:(:[0-9A-Fa-f]{0,4}){0,4}%[0-9a-zA-Z]+|
        ::(ffff(:0{1,4})?:)?
        ((25[0-5]|(2[0-4]|1?[0-9])?[0-9])\.){3}
        (25[0-5]|(2[0-4]|1?[0-9])?[0-9])|
        ([0-9A-Fa-f]{1,4}:){1,4}:
        ((25[0-5]|(2[0-4]|1?[0-9])?[0-9])\.){3}
        (25[0-5]|(2[0-4]|1?[0-9])?[0-9])
    )$/x';


    /**
     * Vérifie si une chaîne est **valide** selon des critères spécifiques.
     *
     * @param string $value La chaîne à vérifier.
     * @param int $minChars (optionnel) Nombre minimal de caractères requis.
     * @param int $maxChars (optionnel) Nombre maximal de caractères autorisés.
     * @param ?string $regex (optionnel) Expression régulière à appliquer.
     * @return bool `true` si la chaîne est valide, `false` sinon.
     *
     * 🔥 Exemples :
     * ```php
     * WShield::IsValidString("");         // ❌ false (vide)
     * WShield::IsValidString("  ");       // ❌ false (uniquement des espaces)
     * WShield::IsValidString("Hello");    // ✅ true (rempli)
     * WShield::IsValidString("1234", 5);  // ❌ false (5 caractères attendus)
     * WShield::IsValidString("12345", 5); // ✅ true (bonne longueur)
     * WShield::IsValidString("A12B", 4, "/^[A-Z0-9]+$/"); // ✅ true (match regex)
     * WShield::IsValidString("A12B", 4, "/^[0-9]+$/");    // ❌ false (ne match pas regex)
     * ```
     */
    public static function IsValidString(
        string &$value,     // 🔹 Ajout du `&` pour modifier directement la variable
        int $minChars = 0,
        int $maxChars = 0,
        ?string $regex = null
    ) : bool {
        $value = trim($value);  // 🔹 Trim appliqué directement à la variable originale
        if ($value == '') return false; // 🔹 Vérifie si la chaîne est vide après suppression des espaces

        return !(
            (($minChars > 0 || $maxChars > 0) && !self::RangeOf($value, $minChars, $maxChars)) // 🔹 Vérifie la longueur si spécifiée
            || (!is_null($regex) && @!preg_match($regex, $value)) // 🔹 Vérifie la regex si spécifiée
        );
    }

    /**
     * Vérifie si un tableau est **valide** selon sa taille.
     *
     * @param array $value Le tableau à vérifier.
     * @param int $strictValue (optionnel) Nombre exact d'éléments attendu (si > 0).
     * @return bool `true` si le tableau est valide (rempli et, si précisé, de la bonne taille).
     *
     * 🔥 Exemples :
     * ```php
     * WShield::IsValidArray([]);         // ❌ false (tableau vide)
     * WShield::IsValidArray([1, 2, 3]);  // ✅ true (rempli)
     * WShield::IsValidArray([1, 2], 2);  // ✅ true (exactement 2 éléments)
     * WShield::IsValidArray([1, 2], 3);  // ❌ false (3 attendus, mais seulement 2 présents)
     * ```
     */
    public static function IsValidArray(array $value, int $strictValue = 0): bool {
        $arraySize = count($value);
        return ($strictValue > 0) ? ($arraySize == $strictValue) : ($arraySize > 0);
    }

    /**
     * Vérifie si **tous** les tableaux donnés sont valides.
     *
     * @param array $values Liste des tableaux à vérifier.
     * @param array $strictValues (optionnel) Liste des tailles exactes attendues pour chaque tableau.
     * @return bool `true` si tous les tableaux sont valides, `false` sinon.
     *
     * 🔥 Exemples :
     * ```php
     * WShield::IsValidSeveralArrays([[], []]); // ❌ false (tous vides)
     * WShield::IsValidSeveralArrays([[1], []]); // ❌ false (un tableau est vide)
     * WShield::IsValidSeveralArrays([[1, 2], [3, 4]], [2, 2]); // ✅ true (bons nombres d'éléments)
     * WShield::IsValidSeveralArrays([[1, 2], [3]], [2, 2]); // ❌ false (le 2e tableau est trop court)
     * ```
     */
    public static function IsValidSeveralArrays(array $values, array $strictValues = []): bool {
        $nbArrays = count($values);
        for ($i = 0; $i < $nbArrays; $i++) {
            $result = (isset($strictValues[$i]))
                ? self::IsValidArray($values[$i], $strictValues[$i])
                : self::IsValidArray($values[$i]);

            if (!$result) return false; // 🔹 Si un tableau est invalide, on arrête et retourne `false`
        }
        return true; // 🔹 Si tous sont valides, retourne `true`
    }

    /**
     * Vérifie si une valeur est dans une plage spécifique.
     *
     * @param int|float|string $value La valeur à tester (une chaîne sera évaluée par sa longueur).
     * @param int|float|null $minRange (optionnel) Valeur minimale acceptée.
     * @param int|float|null $maxRange (optionnel) Valeur maximale acceptée.
     * @return bool `true` si la valeur est dans la plage, `false` sinon.
     *
     * 🔥 Exemples :
     * ```php
     * WShield::RangeOf(10, 5, 15); // ✅ true (10 est entre 5 et 15)
     * WShield::RangeOf(20, 5, 15); // ❌ false (20 est trop grand)
     * WShield::RangeOf(4, 5); // ❌ false (4 est trop petit)
     * WShield::RangeOf(8, null, 10); // ✅ true (max 10 autorisé)
     * WShield::RangeOf("Hello", 3, 5); // ✅ true (5 caractères dans la plage)
     * WShield::RangeOf("Hi", 3, 5); // ❌ false (2 caractères, trop court)
     * ```
     */
    public static function RangeOf(
        int|float|string $value,
        int|float|null $minRange = null,
        int|float|null $maxRange = null
    ): bool {
        if (is_string($value)) $value = strlen(trim($value));

        if (!is_null($minRange)) {
            return (!is_null($maxRange))
                ? ($value <= $maxRange && $value >= $minRange)
                : ($value >= $minRange);
        } else if (!is_null($maxRange)) {
            return ($value <= $maxRange);
        }

        return true;
    }

    /**
     * Vérifie si une valeur est un **nombre flottant** et si elle est dans une plage donnée.
     *
     * @param mixed $value La valeur à tester.
     * @param int|float|null $minRange (optionnel) Valeur minimale acceptée.
     * @param int|float|null $maxRange (optionnel) Valeur maximale acceptée.
     * @return bool `true` si la valeur est un float et est dans la plage.
     *
     * 🔥 Exemples :
     * ```php
     * WShield::IsFloat(3.14); // ✅ true
     * WShield::IsFloat(10, 5, 15); // ✅ true (entre 5 et 15)
     * WShield::IsFloat(20.5, 5, 15); // ❌ false (trop grand)
     * WShield::IsFloat("3.14"); // ❌ false (c'est une string)
     * ```
     */
    public static function IsFloat(
        mixed $value,
        int|float|null $minRange = null,
        int|float|null $maxRange = null
    ): bool {
        return (is_float($value)) && self::RangeOf($value, $minRange, $maxRange);
    }

    /**
     * Vérifie si une valeur est un **entier** et si elle est dans une plage donnée.
     *
     * @param mixed $value La valeur à tester.
     * @param ?int $minRange (optionnel) Valeur minimale acceptée.
     * @param ?int $maxRange (optionnel) Valeur maximale acceptée.
     * @return bool `true` si la valeur est un entier et est dans la plage.
     *
     * 🔥 Exemples :
     * ```php
     * WShield::IsInt(10); // ✅ true
     * WShield::IsInt(5, 5, 10); // ✅ true (dans la plage)
     * WShield::IsInt(3.14); // ❌ false (c'est un float)
     * ```
     */
    public static function IsInt(mixed $value, ?int $minRange = null, ?int $maxRange = null ): bool {
        return (is_int($value)) && self::RangeOf($value, $minRange, $maxRange);
    }

    public static function IsValidIP(string $ip): bool {
        return (self::IsIPv4($ip) || self::IsIPv6($ip));
    }

    public static function IsIPv4(string $ip): bool {
        return (bool) preg_match(self::REGEX_IPV4, $ip);
    }

    public static function IsIPv6(string $ip): bool {
        return (bool) preg_match(self::REGEX_IPV6, $ip);
    }
}