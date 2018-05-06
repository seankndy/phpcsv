<?php
namespace SeanKndy\CSV;

class CSV implements \Iterator
{
    protected $file;
    protected $fp;

    protected $loaded = false;
    protected $csv = [];
    protected $columns = [];
    protected $options = [];
    protected $formatters = [];

    // properties for Iterator
    protected $position = 0;
    protected $positionValid = true;

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
        $this->options['hasHeader'] = (!isset($options['hasHeader']) || $options['headHeader']);

        if ($this->file && $this->options['preload']) {
            $this->load();
        }

        return $this;
    }

    /**
     * Load CSV from file into memory
     *
     * @return void
     */
    public function load() {
        if (!($this->fp = @\fopen($this->file, 'r'))) {
            throw new \Exception("Failed to open file for reading: $file");
        }
        $first = true;
        while ($data = \fgetcsv($this->fp)) {
            if ($this->options['trim']) {
                foreach ($data as $k => $v) {
                    $data[$k] = trim($v);
                }
            }
            if ($first && $this->options['hasHeader']) {
                $this->columns = $data;
                $first = false;
            }
            foreach ($this->formatters as $i => $formatter) {
                $data[$i] = $formatter->format($data[$i]);
            }
            $this->csv[] = $data;
        }
        \fclose($this->fp);
        $this->loaded = true;
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
        $i = 0;
        if ($this->options['hasHeader']) {
            $this->csv[$i++][] = $col;
        }
        for (; $i < count($this->csv); $i++) {
            $this->csv[$i][] = $fill;
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
    public function defColumns(array $cols) {
        $this->columns = array_values($cols);
        return $this;
    }

    /**
     * Set data formatter (Formatter object) for column $col
     *
     * @param string $col Column name
     * @param Formatter Formatter object to use on data in column $col
     *
     * @return $this
     */
    public function setFormatter(string $col, Formatters\Formatter $formatter) {
        if (($colIndex = $this->columnIndex($col)) < 0) {
            throw new \InvalidArgumentException("Invalid/unknown column passed in: $col\n");
        }
        foreach ($this->csv as $k => $data) {
            $this->csv[$k][$colIndex] = $formatter->format($data[$colIndex]);
        }
        $this->formatters[$colIndex] = $formatter;
        return $this;
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
     * Dump $this->csv to stdout
     *
     * @param boolean $includeHeader Include headers first or not
     *
     * @return void
     */
    public function dump($includeHeader = true) {
        if ($includeHeader) {
            self::printLine($this->columns);
        }
        foreach ($this->csv as $data) {
            self::printLine($data);
        }
    }

    /**
     * Create new row and append to end of CSV
     *
     * @param array $fill Optional array containing values to fill into row
     *
     * @return int Row index of newly inserted row
     */
    public function appendRow(array $fill = []) {
        $row = array_fill(0, count($this->columns)-1, '');
        if ($fill) {
            $row = array_merge($row, $fill);
        }
        ksort($row);
        return array_push($this->csv, $row);
    }

    /**
     * Insert $data for column $col into either row at $rowIndex or
     * new row appended (if $rowIndex < 0)
     *
     * @param mixed $col Column name or index of column to insert into
     * @param string $data Data to insert into column
     *
     * @return void
     */
    public function insert($col, $data, $rowIndex = -1) {
        if (is_int($col)) {
            $colIndex = $col;
        } else if (($colIndex = $this->columnIndex($col)) < 0) {
            throw new \InvalidArgumentException("Invalid/unknown column passed in: $col\n");
        }

        if ($rowIndex < 0) {
            $rowIndex = count($this->csv)-1;
        }

        if (isset($this->csv[$rowIndex])) {
            if (isset($this->formatters[$colIndex])) {
                $data = $this->formatters[$colIndex]->format($data);
            }
            $this->csv[$rowIndex][$colIndex] = $data;
        } else {
            throw new \OutOfBoundsException("$rowIndex is out of bound.");
        }
    }

    /**
     * Fetch data from a specific row and column.
     * If $rowIndex < 0, then fetch data from the last row.
     *
     * @param string $col Column name to fetch (as specified in defColumn())
     * @param int    $rowIndex Index of row to fetch or -1 to fetch from
     *     uncommitted row.
     *
     * @return mixed
     */
    public function get($col, $rowIndex = -1) {
        if (is_int($col)) {
            $index = $col;
        } else if (($index = $this->columnIndex($col)) < 0) {
            throw new \InvalidArgumentException("Invalid/unknown column passed in: $col\n");
        }
        if ($rowIndex < 0) {
            $rowIndex = count($this->csv)-1;
        }

        if (isset($this->csv[$rowIndex])) {
            return $this->csv[$rowIndex][$index];
        }
        return null;
    }

    /**
     * Fetch index/position of column $col
     *
     * @param string $col Column name
     *
     * @return int
     */
    public function columnIndex($col) {
        if (($index = array_search($col, $this->columns)) === false) {
            return -1;
        }
        return $index;
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
    public function fillFrom(CSV $that, $thisKeyColumn, $thatKeyColumn,
        array $theseColumns, array $thoseColumns) {
        if (!$this->loaded) {
            throw new \RuntimeException("CSV must be loaded into memory (use load() method) in order to use this method.");
        }
        if (($thisKeyIndex = $this->columnIndex($thisKeyColumn)) < 0) {
            throw new \InvalidArgumentException("\$thisKeyColumn ($thisKeyColumn) is undefined!");
        }
        if (!($thatKeyIndex = $that->columnIndex($thatKeyColumn)) < 0) {
            throw new \InvalidArgumentException("\$thisKeyColumn ($thisKeyColumn) is undefined!");
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

        $theseColumnIndexes = [];
        if (!$theseColumns) { // if $theseColumns empty, then append the columns
            foreach ($thoseColumns as $c) {
                $this->addColumn($c);
                $theseColumnIndexes[] = count($this->columns)-1;
            }
            $theseColumns = $thoseColumns;
        } else {
            foreach ($theseColumns as $c) {
                $theseColumnIndexes[] = $this->columnIndex($c);
            }
        }
        $thoseColumnIndexes = [];
        foreach ($thoseColumns as $c) {
            $thoseColumnIndexes[] = $that->columnIndex($c);
        }

        $outsideFirst = true;
        foreach ($this as $thisIndex => $thisData) {
            if ($outsideFirst && $this->options['hasHeader']) {
                $outsideFirst = false;
                continue;
            }
            $insideFirst = true;
            foreach ($that as $thatIndex => $thatData) {
                if ($insideFirst && $that->getOptions()['hasHeader']) {
                    $insideFirst = false;
                    continue;
                }
                if ($thisData[$thisKeyIndex] == $thatData[$thatKeyIndex]) {
                    foreach ($thoseColumnIndexes as $i => $thatColIdx) {
                        $this->insert(
                            $theseColumnIndexes[$i],
                            $thatData[$thatColIdx],
                            $thisIndex
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
    static public function printLine($data) {
        $line = '';
        foreach ($data as $v) {
            $v = str_replace('"', '""', $v);
            $line .= "\"$v\",";
        }
        $line = rtrim($line, ',');
        echo $line . "\n";
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
        if ($this->loaded) {
            return $this->csv[$this->position];
        } else {
            $data = \fgetcsv($this->fp);
            if ($this->options['trim']) {
                foreach ($data as $k => $v) {
                    $data[$k] = trim($v);
                }
            }
            if ($this->position == 0 && $this->options['hasHeader']) {
                $this->columns = $data;
            }
            return $data;
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
        if (!$this->loaded) {
            $this->positionValid = (\fgets($this->fp) !== false);
        } else {
            $this->positionValid = ($this->position+1 < count($this->csv));
        }
        $this->position++;
    }

    /**
     * Rewind to beginning of CSV
     *
     * @return void
     */
    public function rewind() {
        if (!$this->loaded) {
            $this->positionValid = (fseek($this->fp, 0) == 0);
        } else {
            $this->positionValid = true;
        }
        $this->position = 0;
    }

    /**
     * Return true/false depending on if current line is valid.
     *
     * @return boolean
     */
    public function valid() {
        return $this->positionValid;
    }
    //
    // End Iterator implementation
    //
}
