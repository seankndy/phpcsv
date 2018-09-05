<?php
namespace SeanKndy\CSV;

class CSV
{
    /**
     * @var array $csv Array containing either each Record of CSV or an int
     *                 holding position of each row in file.
     */
    protected $csv = [];

    /**
     * @var array $columns Array containing header values.
     */
    protected $columns = [];

    /**
     * @var array $options Array for options.
     */
    protected $options = [];

    /**
     * @var array $formatters Array of colName => callable
     */
    protected $formatters = [];

    /**
     * @var array $columns Array containing Mutators to call when fetching
     *                     Record.
     */
    protected $mutators = [];

    /**
     * @var string $file Filename of CSV
     */
    protected $file;

    /**
     * @var resource $fp File resource pointer
     */
    protected $fp;

    /**
     * Constructor.  Specify CSV filename to load (optional) and whether or
     * not to cache the file in memory.
     *
     * @param string $file Path to CSV file
     * @param array $options ['preload' => boolean, 'trim' => boolean,
     *     'hasHeader' => boolean]
     *
     * @return CSV
     */
    public function __construct($file = null, array $options = []) {
        $this->file = $file;

        $this->options['trim'] = !isset($options['trim']) || $options['trim'];
        $this->options['preload'] = (!isset($options['preload']) || $options['preload']);
        $this->options['hasHeader'] = ((!isset($options['hasHeader']) && $file) || $options['hasHeader']);

        if ($this->file) {
            if (!($this->fp = @\fopen($this->file, 'r'))) {
                throw new \Exception("Failed to open file for reading: $file");
            }

            if ($this->options['hasHeader']) {
                $this->setColumnsFromHeaderInFile();
            } else {
                $this->options['fileDataStartPos'] = 0;
            }

            if ($this->options['preload']) {
            	$this->loadFile();
            } else {
                $this->indexFile();
            }
        }

        return $this;
    }


    /**
     * Set columns from the first line in CSV
     *
     * @return void
     */
    public function setColumnsFromHeaderInFile() {
        fseek($this->fp, 0);
        $this->columns = fgetcsv($this->fp);
        $this->options['fileDataStartPos'] = ftell($this->fp);
        array_walk($this->columns, function (&$v,$k) { $v = trim($v); });
    }

    /**
     * Load CSV from file into memory
     *
     * @return void
     */
    public function loadFile() {
        $first = true;
        fseek($this->fp, $this->options['fileDataStartPos']);
        while ($data = fgetcsv($this->fp)) {
            $this->csv[] = new Record($this, $data);
        }
        fclose($this->fp);
        $this->loaded = true;
    }

    /**
     * Index file at $this->fp (record each start line position)
     *
     * @return void
     */
    protected function indexFile() {
        $this->csv = [];
        fseek($this->fp, $this->options['fileDataStartPos']);
        for ($pos = $this->options['fileDataStartPos']; $buf = fgets($this->fp); $pos += strlen($buf)) {
            $this->csv[] = $pos;
        }
    }

    /**
     * Add column to CSV
     *
     * @param string $col Column name
     * @param mixed $fill Data to fill into column, if desired.
     *
     * @return $this
     */
    public function addColumn($col, $fill = '') {
        $this->mutators[] = new Mutators\NewColumn($col, $fill);
        return $this;
    }

    /**
     * Delete column from CSV
     *
     * @param string $col Column name
     *
     * @return $this
     */
    public function deleteColumn($col) {
        $this->mutators[] = new Mutators\DeleteColumn($col);
        return $this;
    }

    /**
     * Define all columns in CSV
     *
     * @param array $cols Column names
     *
     * @return $this
     */
    public function setColumns(array $cols) {
        if (count($cols) != count(array_unique($cols))) {
            throw new \RuntimeException("\$cols must have unique elements.");
        }
        $this->columns = array_values($cols);
        return $this;
    }

    /**
     * Does column exist?
     *
     * @param string $col Column to check
     *
     * @return boolean
     */
    public function columnExists(string $col) {
        return in_array($col, $this->getColumns());
    }

    /**
     * Set data formatter for column $col
     *
     * @param string $col Column name
     * @param callable Function to format data in column $col
     *
     * @return $this
     */
    public function setFormatter(string $col, callable $formatter) {
        $this->formatters[$col] = $formatter;
        return $this;
    }

    /**
     * Return callable formatter for column $col
     *
     * @param string $col Column name
     *
     * @return callable
     */
    public function getFormatter($col) {
        return isset($this->formatters[$col]) ? $this->formatters[$col] : null;
    }

    /**
     * Get columns defined.
     *
     * @return array
     */
    public function getColumns($mutate = true) {
        return $mutate ? $this->mutateHeader($this->columns) : $this->columns;
    }

    /**
     * Add a custom Mutator
     *
     * @return $this
     */
    public function addMutator(Mutators\Mutator $mutator) {
        $this->mutators[] = $mutator;
        return $this;
    }

    /**
     * Create new Record and insert it into an arbitrary position.
     *
     * @param array $data Data to populate the Record with
     *
     * @return Record
     */
    public function insertRecord(array $data = [], $position = -1) {
        $record = new Record($this, $data);
        if ($position < 0) {
            $this->csv[] = $record;
        } else {
            array_splice($this->csv, $position, 0, [$record]);
        }
        return $record;
    }

    /**
     * Delete Record at a position.
     *
     * @param int $position
     *
     * @return void
     */
    public function deleteRecord($position) {
        if (!isset($this->csv[$position])) {
            throw new \OutOfBoundsException("Position $position is out of bounds!");
        }
        unset($this->csv[$position]);
        $this->csv = array_values($this->csv);
    }

    /**
     * Fetch Record at position
     * If $position < 0, then fetch last record
     *
     * @param int $position Position of Record to fetch or -1 to fetch last record.
     *
     * @return Record
     */
    public function get($position = -1) {
        $record = null;
        if ($position < 0) {
            $position = count($this->csv)-1;
        }
        if (!isset($this->csv[$position])) {
            throw new \OutOfBoundsException("Position $position is out of bounds!");
        }
        if ($this->csv[$position] instanceof Record) {
            $record = $this->csv[$position];
        } else if (is_int($this->csv[$position])) { // offset position in file
            fseek($this->fp, $this->csv[$position]);
            $record = new Record($this, fgetcsv($this->fp));
        }

        return $this->mutateRecord($record);
    }

    /**
     * Apply all mutations to a copy of Record
     *
     * @param Record $record
     *
     * @return Record
     */
    protected function mutateRecord(Record $record) {
        if (!$this->mutators) {
            return $record;
        }
        
        $r = clone $record;
        foreach ($this->mutators as $mutator) {
            $r = $mutator->mutate($r);
        }
        return $r;
    }

    /**
     * Apply all mutations to header columns
     *
     * @param array $columns
     *
     * @return array
     */
    protected function mutateHeader(array $header) {
        foreach ($this->mutators as $mutator) {
            $header = $mutator->mutateHeader($header);
        }
        return $header;
    }

    /**
     * Combine two or more columns with optional delimiter
     *
     * @param string $delimiter Delimit each column with string
     * @param array $columns Columns to combine
     * @param string $newColumn Name of the new resulting column
     *
     * @return void
     */
    public function combineColumns(array $columns, string $newColumn, string $delimiter = ' ') {
        $this->mutators[] = new Mutators\MergeColumns($columns, $newColumn, $delimiter);
    }

    /**
     * Get a new Records iterator object
     *
     * @return Records
     */
    public function getRecords() {
        return new Records($this);
    }

    /**
     * Fill $theseColumns of $this CSV with $thoseColumns of another CSV.
     * Key/filter the two CSVs together by running comparator function.
     * If $theseColumns is empty, then then the values from $that will be
     * appended as new columns.
     *
     * @param CSV $that CSV object we're filling data from
     * @param callable $comparator Callable that is passed 2 args: a Record
     *    from $this and from $that.  Return boolean.
     * @param array $theseColumns Columns we are over-writing in $this CSV
     * @param array $thoseColumns Columns we are getting data from (in $that CSV)
     *
     * @return void
     */
    public function join(CSV $that, callable $comparator,
        array $theseColumns, array $thoseColumns) {
        if ($theseColumns && count($theseColumns) != count($thoseColumns)) {
            throw new \InvalidArgumentException("\$theseColumns must be same length as \$thoseColumns");
        }
        if (count(array_intersect($this->getColumns(), $theseColumns)) != count($theseColumns)) {
            throw new \InvalidArgumentException("\$theseColumns contains undefined columns!");
        }
        if (count(array_intersect($that->getColumns(), $thoseColumns)) != count($thoseColumns)) {
            throw new \InvalidArgumentException("\$thoseColumns contains undefined columns!");
        }

        $this->mutators[] = new Mutators\Join(
            $that,
            $comparator,
            $theseColumns, $thoseColumns
        );
    }

    /**
     * Return options array
     *
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * Return number of records
     *
     * @return int
     */
    public function getNumRecords() {
        return count($this->csv);
    }

    /**
     * Static method to print an array as a CSV line
     *
     * @param array $data One-dimensional array to print in CSV format
     *
     * @return $this
     */
    static public function printLine(array $data) {
        echo self::arrayToString($data);
    }

    /**
     * Static method to return CSV representation of array of data
     *
     * @param array $data One-dimensional array to convert to CSV string
     *
     * @return string
     */
    static public function arrayToString(array $data) {
        $line = '';
        foreach ($data as $v) {
            $v = str_replace('"', '""', $v);
            $line .= "\"$v\",";
        }
        $line = rtrim($line, ',');
        return $line . "\n";
    }

    /**
     * Static generator method to read a CSV line by line
     *
     * @param array $data One-dimensional array to print in CSV format
     *
     * @return $this
     */
    static public function readLine($file, $skipFirstLine = true, $trim = true) {
        if (!($fp = @\fopen($file, 'r'))) {
            throw new \Exception("Failed to open file for reading: $file");
        }
        $first = true;
        while ($data = \fgetcsv($fp)) {
            if ($skipFirstLine && $first) {
                $first = false;
                continue;
            }
            if ($trim) {
                foreach ($data as $k => $v) {
                    $data[$k] = trim($v);
                }
            }
            yield $data;
        }
        \fclose($fp);
    }
}
