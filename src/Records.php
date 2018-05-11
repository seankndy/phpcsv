<?php
namespace SeanKndy\CSV;

class Records implements \Iterator
{
    protected $csv;
    protected $position = 0;
    protected $filter = [];

    public function __construct(CSV $csv) {
        $this->csv = $csv;
        return $this;
    }

    /**
     * Filter records from CSV based on data from column(s)
     *
     * @param array $filter Filter array, format: ['col_name' => ['1','2']]
     *   the above example would remove any row in CSV where col_name is not
     *   1 or 2
     *
     * @return void
     */
    public function filter(array $filter) {
        foreach ($filter as $col => $f) {
            if (!is_array($f)) {
                $filter[$col] = [$f];
            }
        }
        $this->filter = $filter;
        return $this;
    }

    /**
     * Determine if $value is set for $col any record
     *
     * @param string $value Value to check for
     * @param string $col Column of interest
     *
     * @return boolean
     */
    public function isInRecord($col, $value) {
        foreach ($this as $record) {
            if ($record->get($col) == $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Dump CSV to stdout
     *
     * @param boolean $includeHeader Include headers first or not
     *
     * @return void
     */
    public function dump($includeHeader = true) {
        if ($includeHeader) {
            CSV::printLine($this->csv->getColumns());
        }
        foreach ($this as $record) {
            CSV::printLine($record->getAll());
        }
    }

    /**
     * Dump only certain columns of CSV to stdout
     *
     * @param array $include Columns to dump, mutually exclusive to $exclude
     * @param array $exclude Columns not to dump, mutually exclusive to $include
     *
     * @return void
     */
    public function pickyDump(array $include, array $exclude = [], $includeHeader = true) {
        $columns = [];
        if ($include) {
            $columns = $include;
        } else if ($exclude) {
            $columns = array_filter($this->getColumns(), function ($v) use ($exclude) {
                return !in_array($v, $exclude);
            });
        }
        if ($includeHeader) {
            CSV::printLine($columns);
        }

	    $first = true;
        foreach ($this as $record) {
            $data = [];
            foreach ($columns as $col) {
                $data[] = $record->get($col);
            }
            CSV::printLine($data);
        }
    }

    /**
     * Get current CSV line from either local memory or file
     *
     * @return mixed
     */
    public function current() {
        return $this->csv->get($this->position);
    }

    /**
     * Get current key (position)
     *
     * @return int
     */
    public function key() {
        return $this->position;
    }

    /**
     * Move to next line in CSV
     *
     * @return void
     */
    public function next() {
        $this->position++;
    }

    /**
     * Rewind to beginning of CSV
     *
     * @return void
     */
    public function rewind() {
        $this->position = 0;
    }

    /**
     * Return true/false depending on if current record is valid
     *
     * @return boolean
     */
    public function valid() {
        if (!$this->filter) {
            return $this->position >= 0 && $this->position < $this->csv->getNumRecords();
        }
        for (; $this->position < $this->csv->getNumRecords(); $this->position++) {
            $record = $this->csv->get($this->position);
            $filtered = false;
            foreach ($this->filter as $col => $f) {
                if (!in_array($record->get($col), $f)) {
                    $filtered = true;
                    break;
                }
            }
            if ($filtered) continue;

            return true;
        }
        return false;
    }
}
