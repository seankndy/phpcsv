<?php
namespace SeanKndy\CSV\Mutators;

use SeanKndy\CSV\Record;

/**
 * Remove a column
 */
class DeleteColumn extends Mutator
{
    protected $column;

    public function __construct($column) {
        $this->column = $column;
    }

    public function mutate(Record $record) {
        $record->delete($this->column);
        return $record;
    }

    public function mutateHeader(array $columns) {
        if (($k = array_search($this->column, $columns)) !== false) {
            unset($columns[$k]);
        }
        return array_values($columns);
    }
}
