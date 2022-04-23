<?php declare(strict_types=1);

namespace Colossal\ORM;

class Model
{
    public static ?\PDO $connection = null;

    protected string $keyColumnName = "";
    protected string $keyPropertyName = "";
    protected string $keyPropertyType = "";

    protected array  $columns = [];

    /**
     * Initialize a PDO connection to the database and set some default settings for the connection.
     *
     * This connection will be shared between all models for efficiency.
     *
     * @param $dsn  (string) The DSN connection string.
     * @param $name (string) The username of the account to connect to the database with.
     * @param $pass (string) The password of the account to connect to the database with.
     */
    public static function connect(string $dsn, string $name, string $pass): void
    {
        if (is_null(self::$connection)) {
            self::$connection = new \PDO($dsn, $name, $pass);
            self::$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        }
    }

    /**
     * Get the PDO connection shared between all models.
     * @return (?PDO) The PDO connection shared between all models.
     */
    public static function getConnection(): ?\PDO { return self::$connection; }

    /**
     * Constructor.
     *
     * All properties marked with the attribute #[Field(column: <column-name>, ?key: true)]
     * will be registered as database column / class property pairs where:
     *  - <column-name> (string) Is the name of the database column that the property maps to.
     *  - The key property is optional and if provided should always be given a value of true.
     */
    public function __construct()
    {
        $reflectionClass = new \ReflectionClass(get_called_class());
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $reflectionAttribute = $reflectionProperty->getAttributes('Field');
            if (!empty($reflectionAttribute)) {
                $columnName = $reflectionAttribute[0]->getArguments()['column'];
                $propertyName = $reflectionProperty->getName();
                $propertyType = $reflectionProperty->getType()->getName();
                if (isset($reflectionAttribute[0]->getArguments()['key'])) {
                    $this->keyColumnName = $columnName;
                    $this->keyPropertyName = $propertyName;
                    $this->keyPropertyType = $propertyType;
                } else {
                    array_push(
                        $this->columns,
                        [
                            'columnName' => $columnName,
                            'propertyName' => $propertyName,
                            'propertyType' => $propertyType,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Load an instance of the model from the database with the given key.
     *
     * This operation will:
     *  - Fetch the entry of the database / table with a given key.
     *  - Populate the properties of this model instance with the values from
     *    the database using the dabase column / class property pairs created
     *    in the constructor.
     *
     * @param $key (mixed) The primary key for this model instance.
     */
    public function load(mixed $key): void
    {
        $table = static::TABLE;
        $query = "SELECT * FROM $table WHERE id = ?";

        $stmt = self::$connection->prepare($query);
        $stmt->execute([$key]);

        if ($stmt->rowCount() === 0) {
            throw new \Error('Model: ' . get_called_class() . ", with id: $id could not be located.");
        }

        $data = $stmt->fetch();

        $keyValue = $data[$this->keyColumnName];
        settype($keyValue, $this->keyPropertyType);
        $this->{$this->keyPropertyName} = $keyValue;

        foreach ($this->columns as $column) {
            $columnName = $column['columnName'];
            $propertyName = $column['propertyName'];
            $propertyType = $column['propertyType'];

            $columnValue = $data[$columnName];
            settype($columnValue, $propertyType);
            $this->$propertyName = $columnValue;
        }
    }

    /**
     * Insert this instance of the model in the database.
     *
     * This operation will:
     *  - Generate an INSERT SQL query using the database column / class property pairs created in the constructor.
     *  - Execute this query.
     */
    public function insert(): void
    {
        $cols = "";
        $vals = "";
        foreach ($this->columns as $column) {
            $cols = $cols . $column['columnName'] . ', ';
            $vals = $vals . '?, ';
        }
        $cols = '(' . rtrim($cols, ' ,') . ')';
        $vals = '(' . rtrim($vals, ' ,') . ')';

        $table = static::TABLE;
        $query = "INSERT INTO $table $cols VALUES $vals";

        $this->execute($query, $this->getPropertyValues(includeKey: false));
    }

    /**
     * Update this instance of the model in the database.
     *
     * This operation will:
     *  - Generate an UPDATE SQL query using the database column / class property pairs created in the constructor.
     *  - Execute this query.
     */
    public function update(): void
    {
        $set = "";
        foreach ($this->columns as $column) {
            $set = $set . $column['columnName'] . ' = ?, ';
        }
        $set = rtrim($set, ' ,');

        $table = static::TABLE;
        $query = "UPDATE $table SET $set WHERE $this->keyColumnName = ?";

        $this->execute($query, $this->getPropertyValues(includeKey: true));
    }

    /**
     * Delete the entry from the database / table with primary key matching that of this model instance.
     */
    public function delete(): void
    {
        $table = static::TABLE;
        $query = "DELETE FROM $table WHERE $this->keyColumnName = ?";

        $this->execute($query, [$this->{$this->keyPropertyName}]);
    }

    /**
     * Execute a query with the given query and values, this is done via a PDO prepared statement.
     * @param $query  (string) The SQL query for the PDO prepared statement.
     * @param $values (array)  The values for the PDO prepared statement.
     */
    protected function execute(string $query, array $values): void
    {
        $stmt = self::$connection->prepare($query);
        $stmt->execute($values);
    }

    /**
     * Get the values of the properties which correspond to database columns for this model.
     * @return (array) The values of the properties which correspond to the database columns for this mode.
     */
    protected function getPropertyValues(bool $includeKey): array
    {
        $propertyValues = [];
        foreach ($this->columns as $column) {
            array_push($propertyValues, $this->{$column['propertyName']});
        }
        if ($includeKey) {
            array_push($propertyValues, $this->{$this->keyPropertyName});
        }
        return $propertyValues;
    }
}