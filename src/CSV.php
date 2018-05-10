<?php
namespace SeanKndy\CSV;

class CSV implements \Iterator
{
    protected $file;
    protected $fp;
    protected $fileIndexes = [];

    protected $csv = [];
    protected $columns = [];
    protected $options = [];
    protected $formatters = [];

    // current Iterator position
    protected $position = 0;

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
            $this->csv[] = new Record($this, $data, $this->options['trim']);
        }
        \fclose($this->fp);
        $this->loaded = true;
    }

    /**
     * Index file at $this->fp (record each start line position)
     *
     * @return void
     */
    protected function indexFile() {
        $this->fileIndexes = [];
        fseek($this->fp, $this->options['fileDataStartPos']);
        for ($pos = $this->options['fileDataStartPos']; $buf = fgets($this->fp); $pos += strlen($buf)) {
            $this->fileIndexes[] = $pos;
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
        $this->columns[] = $col;
        if ($fill) {
            foreach ($this->csv as $record) {
                $record->set($col, $fill);
            }
        }
        return $this;
    }

    /**
     * Delete column from CSV
     *
     * @param string $col Column name
     *
     * @return $this
     */
    public function deleteColumn($col, $clean = true) {
        if (($k = array_search($col, $this->columns)) !== false) {
            unset($this->columns[$k]);
        }
        $this->columns = array_values($this->columns);

        if ($clean) {
            foreach ($this->csv as $record) {
                $record->delete($col);
            }
        }

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
        return in_array($col, $this->columns);
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
    public function getColumns() {
        return $this->columns;
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
            self::printLine($this->columns);
        }
        foreach ($this as $record) {
            self::printLine($record->getAll());
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
            $columns = array_filter($this->columns, function ($v) use ($exclude) {
                return !in_array($v, $exclude);
            });
        }
        if ($includeHeader) {
            self::printLine($columns);
        }

	    $first = true;
        foreach ($this as $record) {
            self::printLine(
                array_filter($record->getAll(), function ($k) use ($columns) {
                    return in_array($k, $columns);
                }, ARRAY_FILTER_USE_KEY)
            );
        }
    }

    /**
     * Create new row and append to end of CSV
     *
     * @param array $fill Optional array containing values to fill into row
     *
     * @return int Row index of newly inserted row
     */
    public function appendRecord(array $fill = []) {
        return array_push($this->csv, new Record($this, $fill, $this->options['trim']));
    }

    /**
     * Fetch record from a specific row
     * If $rowIndex < 0, then fetch last record
     *
     * @param int $row Index of row to fetch or -1 to fetch last record.
     *
     * @return Record
     */
    public function get($row = -1) {
        if ($this->csv) {
            if ($row < 0) {
                return $this->csv[count($this->csv)-1];
            } else if ($row >= count($this->csv)) {
                throw new \OutOfBoundsException("Row $row is out of bounds!");
            } else {
                return $this->csv[$row];
            }
        } else {
            if ($row < 0) {
                $seekTo = $this->fileIndexes[count($this->fileIndexes)-1];
            } else if ($row > 0) {
                if ($row >= count($this->fileIndexes)) {
                    throw new \OutOfBoundsException("Row $row is out of bounds!");
                }
                $seekTo = $this->fileIndexes[$row];
            }
            fseek($this->fp, $seekTo);
            return new Record($this, fgetcsv($this->fp), $this->options['trim']);
        }
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
            if ($record->get($col) == $val) {
                return true;
            }
        }
        return false;
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
        if (!$this->csv) {
            throw new \RuntimeException("CSV must be in memory to use this method.");
        }

        $this->addColumn($newColumn);
        foreach ($this as $record) {
            $newVals = [];
            foreach ($columns as $col) {
                $newVals[] = $record->get($col);
                $record->delete($col);
            }
            $record->set($newColumn, implode($delimiter, $newVals));

        }

        foreach ($columns as $col) {
             // second arg is $clean and it's false because
             // i've already deleted the old columns above
            $this->deleteColumn($col, false);
        }
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
        if (!$this->csv) {
            throw new \RuntimeException("CSV must be in memory to use this method.");
        }

        foreach ($filter as $col => $f) {
            if (!is_array($f)) {
                $filter[$col] = [$f];
            }
        }

        foreach ($this->csv as $k => $record) {
            foreach ($filter as $col => $f) {
                if (!in_array($record->get($col), $f)) {
                    unset($this->csv[$k]);
                    break;
                }
            }
        }
        $this->csv = array_values($this->csv);
    }

    /**
     * Fill $theseColumns of $this CSV with $thoseColumns of another CSV.
     * Key/filter the two CSVs together by matching $thisKeyColumn in $this
     * CSV and $thatKeyColumn in $that.  If $theseColumns is empty, then
     * then the values from $that will be appended as new columns.
     *
     * @param CSV $that CSV object we're filling data from
     * @param string $thisKeyColumn Column contain key in $this CSV
     * @param string $thatKeyColumn Matching column containing key in $that CSV
     * @param array $theseColumns Columns we are over-writing in $this CSV
     * @param array $thoseColumns Columns we are getting data from (in $that CSV)
     *
     * @return void
     */
    public function join(CSV $that, $thisKeyColumn, $thatKeyColumn,
        array $theseColumns, array $thoseColumns) {
        if (!$this->csv) {
            throw new \RuntimeException("CSV must be in memory in order to use this method.");
        }
        if (!$this->columnExists($thisKeyColumn)) {
            throw new \InvalidArgumentException("\$thisKeyColumn ($thisKeyColumn) is undefined!");
        }
        if (!$that->columnExists($thatKeyColumn)) {
            throw new \InvalidArgumentException("\$thatKeyColumn ($thatKeyColumn) is undefined in \$that CSV!");
        }
        if ($theseColumns && count($theseColumns) != count($thoseColumns)) {
            throw new \InvalidArgumentException("\$theseColumns must be same length as \$thoseColumns");
        }
        if (count(array_intersect($this->columns, $theseColumns)) != count($theseColumns)) {
            throw new \InvalidArgumentException("\$theseColumns contains undefined columns!");
        }
        if (count(array_intersect($that->getColumns(), $thoseColumns)) != count($thoseColumns)) {
            throw new \InvalidArgumentException("\$thoseColumns contains undefined columns!");
        }

        if (!$theseColumns) { // if $theseColumns empty, then append the columns as new ones
            foreach ($thoseColumns as $c) {
                $this->addColumn($c);
            }
            $theseColumns = $thoseColumns;
        }

        foreach ($this as $thisRecord) {
            foreach ($that as $thatRecord) {
                if ($thisRecord->get($thisKeyColumn) == $thatRecord->get($thatKeyColumn)) {
                    foreach ($thoseColumns as $k => $thatCol) {
                        $thisRecord->set(
                            $theseColumns[$k],
                            $thatRecord->get($thatCol)
                        );
                    }
                }
            }
        }
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

    //
    // Itearator implementation
    //
    /**
     * Get current CSV line from either local memory or file
     *
     * @return mixed
     */
    public function current() {
        if ($this->csv) {
            return $this->csv[$this->position];
        } else {
            fseek($this->fp, $this->fileIndexes[$this->position]);
            return new Record($this, fgetcsv($this->fp), $this->options['trim']);
        }
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
        if (!$this->csv) {
            fseek($this->fp, $this->options['fileDataStartPos']);
        }
        $this->position = 0;
    }

    /**
     * Return true/false depending on if current line is valid.
     *
     * @return boolean
     */
    public function valid() {
        if ($this->csv) {
            return $this->position >= 0 && $this->position < count($this->csv);
        } else {
            return isset($this->fileIndexes[$this->position]);
        }
    }
    //
    // End Iterator implementation
    //
}
