<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DummyModel extends Colossal\ORM\Model
{
    const TABLE = 'DummyTable';

    #[Field(column: 'column_1', key: true)]
    public string $property1;

    #[Field(column: 'column_2')]
    public string $property2;

    #[Field(column: 'column_3')]
    public string $property3;

    /**
     * We need to override the execute function for the tests so that we are
     * not actually trying to make any requests to a database. Instead we
     * save $query and $values and use these to test that the expected
     * requests would have been executed.
     */

    public string $query  = "";
    public array  $values = [];

    protected function execute(string $query, array $values): void
    {
        $this->query  = $query;
        $this->values = $values;
    }
}

final class ModelTest extends TestCase
{
    private DummyModel $model;

    public function setUp(): void
    {
        $this->model = new DummyModel;
        $this->model->property1 = "prop1";
        $this->model->property2 = "prop2";
        $this->model->property3 = "prop3";
    }

    public function testInsert()
    {
        $this->model->insert();

        $this->assertEquals('INSERT INTO DummyTable (column_2, column_3) VALUES (?, ?)', $this->model->query);
        $this->assertEquals(['prop2', 'prop3'], $this->model->values);
    }

    public function testUpdate()
    {
        $this->model->update();

        $this->assertEquals('UPDATE DummyTable SET column_2 = ?, column_3 = ? WHERE column_1 = ?', $this->model->query);
        $this->assertEquals(['prop2', 'prop3', 'prop1'], $this->model->values);
    }

    public function testDelete()
    {
        $this->model->delete();

        $this->assertEquals('DELETE FROM DummyTable WHERE column_1 = ?', $this->model->query);
        $this->assertEquals(['prop1'], $this->model->values);
    }
}