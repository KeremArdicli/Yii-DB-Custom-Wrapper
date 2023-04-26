<?php

require_once "vendor/autoload.php";
require_once "config.php";
include "tableNames.php";

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Database işlemleri yönetim sınıfı
 */
class DataBase
{
    public string $adi;
    public string $gecistarihi;
    public string $aciklama;
    public array $values;
    public TableNames $tablename;
    public int $erkenfiyat;
    public int $zamlifiyat;
    public int $ilgilibutce;
    public string $conditionalQuery = "";

    /**
     * Retrieve DB isntance with the start of the Class
     *
     * @param ConnectionInterface $db
     */
    public function __construct(private ConnectionInterface $db)
    {
    }

    /**
     * Enter table name, and the values along with the associated column name in an array
     * This method will populate the query itself and execute the query.
     *
     * @param TableNames $tablename
     * @param array $values ["columname" => "value"]
     * @return void
     */
    public function insert_into(TableNames $tablename, array $values)
    {
        $this->values = $values;
        $this->tablename = $tablename;

        $this->db->transaction(
            function (ConnectionInterface $db) {

                $sql = $this->parse_sql($this->tablename, $this->values);

                $bindValues = $this->parse_bindValues($this->values);

                foreach ($this->values as $key => $value) {
                    $newKey = ":$key";
                    $bindValues[$newKey] = $value;
                }
                try {
                    $db
                        ->createCommand($sql)
                        ->bindValues($bindValues)
                        ->execute();
                } catch (Exception $e) {
                    // var_dump($e->getMessage()); /* uncomment this line to se ethe errorsin local dev env */
                    echo "An error has been occured!\n";
                }
            }
        );
    }

    /**
     * Belirtilen tablodaki ilk satırı ya da tüm satırları çeker.
     *
     * @param TableNames $tablename
     * @param string $type "all" girilirse tüm satırları, "one" girilirse sadece ilk satırı döner
     *  (optional - default 'one')
     * @return array|null
     */
    public function query_anytable(TableNames $tablename, string $type = "one"): array|null
    {
        $this->tablename = $tablename;
        return match ($type) {
            "one" => $this->db->createCommand('SELECT * FROM {{%' . $this->tablename->toString() . '}}')->queryOne(),
            "all" => $this->db->createCommand('SELECT * FROM {{%' . $this->tablename->toString() . '}}')->queryAll()
        };
    }

    /**
     * Tablolardan şartlı veri çekmek için kullanılır
     * Tekli ya da çoklu şart girilebilir. 
     *
     * @param TableNames $tablename
     * @param array $conditions [$key: columnName => $value: columnValue] şeklinde girilmelidir!
     * @return array|null
     */
    public function conditional_query(TableNames $tablename, array $conditions): array|null
    {
        $this->tablename = $tablename;
        $conditionCount = count($conditions);

        if ($conditionCount === 1) {

            $columnName = key($conditions);
            $columnValue = reset($conditions);
            $sql = "SELECT * FROM {{%" . $this->tablename->toString() . "}} WHERE [[" . $columnName . "]] = :" . $columnName;
            $command = $this->db->createCommand($sql);
            $command->bindParam(':' . $columnName . '', $columnValue);
            return $command->queryAll();
        } else {

            $whereClause = " WHERE ";
            $i = 0;
            foreach ($conditions as $key => $value) {
                $whereClause .= "[[" . $key . "]] = :" . $key;

                if (++$i < $conditionCount) {
                    $whereClause .= " AND ";
                }
            }
            $this->conditionalQuery .= $whereClause;
            // Prepared statement with multiple bindings
            $sql = 'SELECT * FROM {{%' . $this->tablename->toString() . '}} ' . $this->conditionalQuery . '';
            $multiBind = $this->db->createCommand($sql);
            $multiBind->bindValues($conditions);
            return $multiBind->queryAll();
        }
    }

    private function parse_sql(TableNames $tablename, array $values) :string
    {
        $this->tablename = $tablename;

        $sql = 'INSERT INTO {{%' . $this->tablename->toString() . '}} (';

        foreach ($values as $key => $value) {
            $sql .= "[[$key]], ";
        }
        $sql = rtrim($sql, ", ");
        $sql .= ") VALUES (";

        foreach ($values as $key => $value) {
            $sql .= ":$key, ";
        }
        $sql = rtrim($sql, ", ");
        $sql .= ")";

        return $sql;
    }

    private function parse_bindValues(array $values) :array
    {
        $this->values = $values;

        $bindValues = [];

        foreach ($this->values as $key => $value) {
            $newKey = ":$key";
            $bindValues[$newKey] = $value;
        }

        return $bindValues;
    }

}
