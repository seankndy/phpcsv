<?php
namespace SeanKndy\CSV;

class FilteredRecords extends \FilterIterator implements RecordsInterface
{
    use RecordsTrait;

    protected $csv;
    protected $filter = [];
    protected $inverse;

    public function __construct(Records $iterator, array $filter, $inverse = false) {
        parent::__construct($iterator);

        foreach ($filter as $col => $f) {
            if (!is_array($f)) {
                $filter[$col] = [$f];
            }
        }
        $this->csv = $iterator->getCsv();
        $this->filter = $filter;
        $this->inverse = $inverse;
    }

    public function accept() {
        $record = parent::current();
        $filtered = false;
        foreach ($this->filter as $col => $f) {
            $isIn = in_array($record->get($col), $f);
            if (!$this->inverse && !$isIn) {
                return false;
            } else if ($this->inverse && $isIn) {
                return false;
            }
        }
        return true;
    }
}
