<?php declare(strict_types=1);

namespace Colossal\ORM;

class Model
{
    public static ?\PDO $connection = null;

    protected array $columns = [];

    public static function connect(string $dsn, string $name, string $pass): void
    {
        if (is_null(self::$connection)) {
            self::$connection = new \PDO($dsn, $name, $pass);
            self::$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        }
    }

    public static function getConnection(): \PDO { return self::$connection; }

    public function __construct()
    {
        $reflectionClass = new \ReflectionClass(get_called_class());
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $modelPropertyName   = $reflectionProperty->getName();
            $reflectionAttribute = $reflectionProperty->getAttributes('Field');
            if (!empty($reflectionAttribute)) {
                $attributeColumn = $reflectionAttribute[0]->getArguments()['column'];
                $attributeType   = $reflectionAttribute[0]->getArguments()['type'];
                array_push(
                    $this->columns,
                    [
                        'columnName' => $attributeColumn,
                        'fieldType'  => $attributeType,
                        'fieldName'  => $modelPropertyName,
                        'isKey'      => isset($reflectionAttribute[0]->getArguments()['key']),
                    ]
                );
            }
        }
    }

    public function load(int $id): void
    {
        $stmt = self::$connection->prepare('SELECT * FROM ' . static::TABLE . ' WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            throw new \Error('Model: ' . get_called_class() . ", with id: $id could not be located.");
        }

        $data = $stmt->fetch();

        foreach ($this->columns as $column) {
            $columnName = $column['columnName'];
            $fieldType  = $column['fieldType'];
            $fieldName  = $column['fieldName'];

            $columnValue = $data[$columnName];
            settype($columnValue, $fieldType);

            $this->$fieldName = $columnValue;
        }
    }

    public function insert(): void
    {
        $cols = "";
        $vals = "";
        $valsArr = [];
        foreach ($this->columns as $column) {
            if ($column['isKey']) {
                continue;
            }
            $cols = $cols . $column['columnName'] . ',';
            $vals = $vals . '?' . ',';
            array_push($valsArr, $this->{$column['fieldName']});
        }
        $cols = '(' . rtrim($cols, ',') . ')';
        $vals = '(' . rtrim($vals, ',') . ')';

        $stmt = self::$connection->prepare('INSERT INTO ' . static::TABLE . " $cols VALUES $vals");
        $stmt->execute($valsArr);
    }

    public function update(): void
    {
        $cols = "";
        $vals = "";
        $valsArr = [];
        foreach ($this->columns as $column) {
            $cols = $cols . $column['columnName'] . ',';
            $vals = $vals . '?' . ',';
            array_push($valsArr, $this->{$column['fieldName']});
        }
        $cols = '(' . rtrim($cols, ',') . ')';
        $vals = '(' . rtrim($vals, ',') . ')';

        $stmt = self::$connection->prepare('REPLACE INTO ' . static::TABLE . " $cols VALUES $vals");
        $stmt->execute($valsArr);
    }
}