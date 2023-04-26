<?php

require("vendor/autoload.php");
require_once("config.php");

// Generate enum with table names
if (!file_exists("tableNames.php")) {
    
    $dbname = DBNAME;
    $sql = "SHOW TABLES FROM $dbname";
    $results = $db->createCommand($sql)->queryAll();

    $cases = "";
    $ths = '$this';
    $toStringContent = "";

    foreach ($results as $tables) {
        foreach ($tables as $key => $value) {
            $cases .= "    case $value;\n";
            $toStringContent .= '    self::' . $value . ' => \'' . $value . "',\n";
        }
    }

    $tableNames = fopen("tableNames.php", "w");
    $content = <<<CONTENT
    <?php

    enum TablesName
    {
        $cases
    
        public function toString(): string
        {
            return match ($ths) {
                $toStringContent
            };
        }
    }        
    CONTENT;

    fwrite($tableNames, $content);
    fclose($tableNames);
    header("location: ./");
} else {
    header("location: ./");
}