<?php
namespace SeanKndy\CSV\Mutators;

use SeanKndy\CSV\CSV;
use SeanKndy\CSV\Record;

/**
 * Joins data from another CSV ($that) into the Record
 * (similar to an SQL join where each CSV object ($this and $that) is like
 * an SQL table)
 */
class Join extends Mutator
{
    protected $thisKeyColumn;
    protected $thatKeyColumn;
    protected $that;
    protected $theseColumns, $thoseColumns;

    public function __construct(CSV $that, string $thisKeyColumn, string $thatKeyColumn,
        array $theseColumns, array $thoseColumns) {
        $this->thisKeyColumn = $thisKeyColumn;
        $this->thatKeyColumn = $thatKeyColumn;
        $this->that = $that;
        $this->theseColumns = $theseColumns;
        $this->thoseColumns = $thoseColumns;
    }

    public function mutate(Record $record) {
        $theseColumns = (!$this->theseColumns ? $this->thoseColumns : $this->theseColumns);
        foreach ($this->that->getRecords() as $thatRecord) {
            if ($record->get($this->thisKeyColumn) == $thatRecord->get($this->thatKeyColumn)) {
                foreach ($this->thoseColumns as $k => $thatCol) {
                    $record->set(
                        $theseColumns[$k],
                        $thatRecord->get($thatCol)
                    );
                }
            }
        }
        return $record;
    }

    public function mutateHeader(array $columns) {
        // if theseColumns not specified, then thoseColumns are added to record
        // so reflect that in header here.
        if (!$this->theseColumns) {
            foreach ($this->thoseColumns as $col) {
                $columns[] = $col;
            }
        }
        return $columns;
    }
}
