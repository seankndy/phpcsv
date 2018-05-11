<?php
namespace SeanKndy\CSV\Mutators;

use SeanKndy\CSV\Record;

/**
 * Create and append new column
 */
class NewColumn extends Mutator
{
    protected $newColumn;
    protected $fill;

    public function __construct($newColumn, $fill = '') {
        $this->newColumn = $newColumn;
        $this->fill = $fill;
    }

    public function mutate(Record $record) {
        $record->set($this->newColumn, $this->fill);
        return $record;
    }

    public function mutateHeader(array $columns) {
        $columns[] = $this->newColumn;
        return $columns;
    }
}
