<?php declare(strict_types=1);

namespace Colossal\ORM;

class Model
{
    public static ?\PDO $connection = null;

    public static function connect(string $dsn, string $name, string $pass): void
    {
        if (is_null(self::$connection)) {
            self::$connection = new \PDO($dsn, $name, $pass);
            self::$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        }
    }

    public static function getConnection(): \PDO { return self::$connection; }

    public function load(int $id): void
    {
        $stmt = self::$connection->prepare('SELECT * FROM ' . static::TABLE . ' WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            throw new \Error('Model: ' . get_called_class() . ", with id: $id could not be located.");
        }

        $data = $stmt->fetch();

        $reflectionClass = new \ReflectionClass(get_called_class());
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $modelPropertyName   = $reflectionProperty->getName();            
            $reflectionAttribute = $reflectionProperty->getAttributes('Field');
            if (!empty($reflectionAttribute)) {
                $attributeColumn = $reflectionAttribute[0]->getArguments()['column'];
                $attributeType   = $reflectionAttribute[0]->getArguments()['type'];
                settype($data[$attributeColumn], $attributeType);
                $this->$modelPropertyName = $data[$attributeColumn];
            }
        }
    }

    public function insert(): void
    {
        $cols = "";
        $vals = "";
        $reflectionClass = new \ReflectionClass(get_called_class());
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $modelPropertyName   = $reflectionProperty->getName();
            $reflectionAttribute = $reflectionProperty->getAttributes('Field');
            if (!empty($reflectionAttribute)) {
                $attributeColumn = $reflectionAttribute[0]->getArguments()['column'];
                if ($attributeColumn === 'id') {
                    continue;
                }
                $cols = $cols . $attributeColumn . ',';
                $vals = $vals . "'{$this->$modelPropertyName}'" . ',';
            }
        }
        $cols = '(' . rtrim($cols, ',') . ')';
        $vals = '(' . rtrim($vals, ',') . ')';

        $stmt = self::$connection->prepare('INSERT INTO ' . static::TABLE . " $cols VALUES $vals");
        $stmt->execute();
    }

    public function update(): void
    {
        $cols = "";
        $vals = "";
        $reflectionClass = new \ReflectionClass(get_called_class());
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $modelPropertyName   = $reflectionProperty->getName();
            $reflectionAttribute = $reflectionProperty->getAttributes('Field');
            if (!empty($reflectionAttribute)) {
                $attributeColumn = $reflectionAttribute[0]->getArguments()['column'];
                $cols = $cols . $attributeColumn . ',';
                $vals = $vals . "'{$this->$modelPropertyName}'" . ',';
            }
        }
        $cols = '(' . rtrim($cols, ',') . ')';
        $vals = '(' . rtrim($vals, ',') . ')';

        $stmt = self::$connection->prepare('REPLACE INTO ' . static::TABLE . " $cols VALUES $vals");
        $stmt->execute();
    }
}