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
    public array $updates;
    public array $conditions;
    public TableNames $tablename;
    public int $erkenfiyat;
    public int $zamlifiyat;
    public int $ilgilibutce;
    public string $conditionalQuery = "";
    public bool $result = false;

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
     * @return bool
     */
    public function insert_into(TableNames $tablename, array $values): bool
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
                    if ($db
                        ->createCommand($sql)
                        ->bindValues($bindValues)
                        ->execute()
                    ) {
                        $this->result = true;
                    }
                } catch (Exception $e) {
                    // var_dump($e->getMessage()); /* uncomment this line to see the errors in local dev env */
                    $this->result = false;
                }
            }
        );

        return $this->result;
    }
    /**
     * This method updates the table with the given conditions. if no conditions are set,
     * all of the rows will be updated. If you have more than one condition,
     * put them in array as arrays.
     *
     * @param TableNames $tablename
     * @param array $updates
     * @param array $conditions (optional) ["id", ">", "3] or [["name", "=", "name"], ["email", "=", "email"]]
     * * @return boolean
     */
    public function update(TableNames $tablename, array $updates, array $conditions = [])
    {
        $this->tablename = $tablename;
        $this->updates = $updates;
        $this->conditions = $conditions;

        $sql = $this->update_sql($this->tablename, $this->updates, $this->conditions);
                
        $bindValues = $this->parse_bindValues($this->updates, $this->conditions);

        try {
            $updateTable = $this->db->createCommand($sql);
            $updateTable->bindValues($bindValues);
            $updateTable->execute() ? $this->result = true : $this->result = false;
        } catch (Exception $e) {
            // var_dump($e->getMessage()); /* uncomment this line to see the errors in local dev env */
            $this->result = false;
        }

        return $this->result;
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

    /**
     * This runs an update query and returns true or false based on result. 
     * Conditions should be given as array in an array. If there are more than one conditions they will be concatenated
     * with AND operator. OR and other operators will be added later on.
     * --TODO --
     * OR and other operators will be added
     *
     * @param TableNames $tablename
     * @param array $updates
     * @param array $conditions
     * @return string
     */
    private function update_sql(TableNames $tablename, array $updates, array $conditions = []): string
    {
        $this->tablename = $tablename;

        $sql = 'UPDATE {{%' . $this->tablename->toString() . '}} SET ';

        foreach ($updates as $key => $value) {
            $sql .= "[[$key]] = :$key, ";
        }
        $sql = rtrim($sql, ", ");

        if ($conditions !== []) {

            $count = count($conditions);

            $sql .= " WHERE ";

            if (!is_array($conditions[0])) {
                $sql .= "[[$conditions[0]]] $conditions[1] :$conditions[0]_w AND ";
            } elseif (is_array($conditions[0])) {
                foreach ($conditions as $condition) {
                    $sql .= "[[$condition[0]]] $condition[1] :$condition[0]_w AND ";
                }
            }
        }
        $sql = rtrim($sql, "AND ");

        return $sql;
    }

    private function parse_sql(TableNames $tablename, array $values): string
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

    private function parse_bindValues(array $values, array $conditions = []): array
    {
        $this->values = $values;

        $bindValues = [];

        foreach ($this->values as $key => $value) {
            $newKey = ":$key";
            $bindValues[$newKey] = $value;
        }

        if ($conditions !== []) {

            if (is_array($conditions[0])) {

                foreach ($conditions as $condition) {
                    $key = ":$condition[0]_w";
                    $bindValues[$key] = $condition[count($condition) - 1];
                }
            } elseif (!is_array($conditions[0])) {
                $key = ":$conditions[0]_w";
                $bindValues[$key] = $conditions[count($conditions) - 1];
            }
        }

        return $bindValues;
    }

}
