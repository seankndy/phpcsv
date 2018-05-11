<?php
namespace SeanKndy\CSV\Mutators;

use SeanKndy\CSV\Record;

/**
 * Combines two or more columns in a record into one
 */
class MergeColumns implements Mutator
{
    protected $columns;
    protected $newColumn;
    protected $delimiter;

    public function __construct(array $columns, string $newColumn, string $delimiter = ' ') {
        $this->columns = $columns;
        $this->newColumn = $newColumn;
        $this->delimiter = $delimiter;
    }

    public function mutate(Record $record) {
        $newVals = [];
        foreach ($this->columns as $col) {
            $newVals[] = $record->get($col);
            $record->delete($col);
        }
        //echo "adding {$this->newColumn} with " . implode($this->delimiter, $newVals) . "\n";
        $record->set($this->newColumn, implode($this->delimiter, $newVals));

        return $record;
    }

    public function mutateHeader(array $columns) {
        foreach ($this->columns as $col) {
            if (($k = array_search($col, $columns)) !== false) {
                unset($columns[$k]);
            }
        }
        $columns[] = $this->newColumn;
        return array_values($columns);
    }
}
