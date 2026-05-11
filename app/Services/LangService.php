<?php

namespace App\Services;

class LangService
{
    /**
     * Cache en mémoire pour éviter de recharger le même fichier plusieurs fois
     * dans la même requête.
     */
    private static array $cache = [];

    /**
     * Charge un fichier de langue depuis resources/lang/{group}/{lang}.php
     * Retourne un tableau vide si le fichier n'existe pas.
     */
    public static function load(string $group, string $lang): array
    {
        $key = "{$group}.{$lang}";

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $path = resource_path("lang/{$group}/{$lang}.php");

        if (! file_exists($path)) {
            return self::$cache[$key] = [];
        }

        return self::$cache[$key] = require $path;
    }

    /**
     * Raccourci pour récupérer une clé précise dans un groupe/langue.
     */
    public static function get(string $group, string $lang, string $key): mixed
    {
        $data = self::load($group, $lang);

        return $data[$key] ?? null;
    }
}
