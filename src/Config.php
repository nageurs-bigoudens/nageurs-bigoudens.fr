<?php
// src/controller/Config.php

declare(strict_types=1);

class Config
{
    // BDD
    static public string $db_host = 'localhost';
    static public string $database = 'nageurs';
    static public string $db_driver = 'pdo_mysql';
    static public string $user = 'root';
    static public string $password = '';
    static public string $table_prefix = '';

    // classe URL
    static public string $protocol = 'http';
    static public string $host = 'nageurs.localhost';
    static public string $port = '80';

    // copier dans ce tableau les variables contenant des chemins
    static private array $path_vars = [];
    
    static public function load(string $file_path): void
    {
        if(file_exists($file_path))
        {
            // ce serait bien de gérer aussi les fichiers corrompus?
            $raw_data = parse_ini_file($file_path);
            self::hydrate($raw_data);
        }
        else
        {
            echo "le fichier config.ini n'existe pas ou n'est pas lisible";
        }
        define('TABLE_PREFIX', self::$table_prefix);
    }
    
    // renseigner les variables internes de Config
    static private function hydrate(array $raw_data): void
    {
        foreach($raw_data as $field => $value)
        {
            if($value != '') // valeur par défaut
            {
                if(isset(self::$$field)) // le champ existe dans Config
                {
                    // problème du slash à la fin du nom d'un dossier
                    $value = self::slashAtEndOfPath($field, $value);
                    self::$$field = $value;
                }
                else
                {
                    echo "debug: le fichier config.ini comporte une erreur, le champ: " . $field . " est incorrect,\nl'information contenue sur cette ligne ne sera pas utilisée\n";
                }
            }
            /*else
            {
                echo "debug: le champ " . $field . " est vide, la valeur par défaut " . self::$$field . " sera utilisée.\n";
            }*/
        }
    }
    

    // pour que les chemins finissent toujours par un /
    static private function slashAtEndOfPath(string $field, string $value): string
    {
        foreach(self::$path_vars as $item)
        {
            if($field === $item){
                return !str_ends_with($value, '/') ? $value . '/' : $value;
            }
        }
        return $value;
    }
}
