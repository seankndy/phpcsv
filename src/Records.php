<?php
namespace SeanKndy\CSV;

class Records implements \Iterator, RecordsInterface
{
    use RecordsTrait;

    protected $position = 0;
    protected $filter = [];

    public function __construct(CSV $csv) {
        $this->csv = $csv;
        return $this;
    }

    /**
     * Set CSV object for this Records object
     *
     * @param CSV $csv
     *
     * @return $this
     */
    public function setCsv(CSV $csv) {
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
     * @return FilteredRecords
     */
    public function filter(array $filter) {
        return new FilteredRecords($this, $filter);
    }

    /**
     * Filter records (inverse) from CSV based on data from column(s)
     *
     * @param array $filter Filter array, format: ['col_name' => ['1','2']]
     *   the above example would remove any row in CSV where col_name is
     *   1 or 2
     *
     * @return FilteredRecords
     */
    public function notFilter(array $filter) {
        return new FilteredRecords($this, $filter, true);
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
        return $this->position >= 0 && $this->position < $this->csv->getNumRecords();
    }
}
