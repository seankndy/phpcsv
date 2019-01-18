<?php
namespace SeanKndy\CSV;

class Record
{
    protected $csv;
    protected $data = [];

    /**
     * Constructor.
     *
     * @param CSV $csv CSV object for this Record
     * @param array $data Column data for this record
     *
     * @return $this;
     */
    public function __construct(CSV $csv, array $data = []) {
        $this->csv = $csv;
        if (!$data)
            $data = array_fill(0, count($this->csv->getColumns()), '');
        $this->setData($data);
        return $this;
    }

    /**
     * Set all data
     *
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data) {
        if (count($this->csv->getColumns(false)) != count($data)) {
            throw new \RuntimeException("\$data must be the same number of elements as there are columns in CSV.");
        }
        if ($this->csv->getOptions()['trim']) {
            array_walk($data, function (&$v,$k) { $v = trim($v); });
        }
        $this->data = array_combine($this->csv->getColumns(false), $data);
    }

    /**
     * Get data from column
     *
     * @param mixed $col String name of int index of column to get
     *
     * @return string
     */
    public function get($col) {
        //if (!$this->csv->columnExists($col)) {
        //    throw new \InvalidArgumentException("Invalid/unknown column '$col'\n");
        //}

        $v = isset($this->data[$col]) ? $this->data[$col] : '';

        if ($f = $this->csv->getFormatter($col)) {
            return $f($v);
        }

        return $v;
    }

    /**
     * Delete column data
     *
     * @param mixed $col String name of column to delete
     *
     * @return void
     */
    public function delete($col) {
        if (isset($this->data[$col])) {
            unset($this->data[$col]);
        }
    }

    /**
     * Get all data from columns
     *
     * @return array
     */
     public function getAll() {
         return $this->data;
     }

    /**
     * Set data for column $col
     *
     * @param mixed $col String name of column to set or array of columns to set
     * @param mixed $data Data to set for column or array of data if $col also array
     * @param boolean $overwrite If column $col already has data in it, do we
     *      want to overwrite it anyway?
     *
     * @return void
     */
    public function set($col, $data, $overwrite = true) {
        if (!is_array($col)) $col = [$col];
        if (!is_array($data)) $data = [$data];

        foreach ($col as $k => $c) {
            if (!$overwrite && isset($this->data[$c]) && $this->data[$c]) {
                continue;
            }

            $d = $data[$k];
            if ($formatter = $this->csv->getFormatter($c)) {
                $d = $formatter($data[$k]);
            }
            $this->data[$c] = $d;
        }
    }

    /**
     * Print record as CSV string
     *
     * @return string
     */
    public function __toString() {
        return CSV::arrayToString($this->getAll());
    }
}
