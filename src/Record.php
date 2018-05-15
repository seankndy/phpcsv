<?php
namespace SeanKndy\CSV;

class Record
{
    protected $csv;
    protected $data = [];

    public function __construct(CSV $csv, array $data = []) {
        $this->csv = $csv;
        if (!$data)
            $data = array_fill(0, count($this->csv->getColumns()), '');
        $this->setData($data);
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
         $data = [];
         foreach ($this->csv->getColumns() as $col) {
             $v = isset($this->data[$col]) ? $this->data[$col] : '';
             if ($f = $this->csv->getFormatter($col)) {
                 $data[$col] = $f($v);
             } else {
                 $data[$col] = $v;
             }
         }
         return $data;
     }

    /**
     * Set data for column $col
     *
     * @param mixed $col String name of int index of column to set
     * @param mixed $data Data to set for column
     *
     * @return void
     */
    public function set($col, $data) {
        //if (!$this->csv->columnExists($col)) {
        //    throw new \InvalidArgumentException("Invalid/unknown column '$col'\n");
        //}
        if ($formatter = $this->csv->getFormatter($col)) {
            $data = $formatter($data);
        }
        $this->data[$col] = $data;
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
