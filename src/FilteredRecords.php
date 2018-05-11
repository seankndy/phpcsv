<?php
namespace SeanKndy\CSV;

class FilteredRecords extends \FilterIterator
{
    use RecordsTrait;

    protected $csv;
    protected $filter = [];

    public function __construct(Records $iterator, array $filter) {
        parent::__construct($iterator);

        foreach ($filter as $col => $f) {
            if (!is_array($f)) {
                $filter[$col] = [$f];
            }
        }
        $this->csv = $iterator->getCsv();
        $this->filter = $filter;
    }

    public function accept() {
        $record = parent::current();
        $filtered = false;
        foreach ($this->filter as $col => $f) {
            if (!in_array($record->get($col), $f)) {
                return false;
            }
        }
        return true;
    }
}
