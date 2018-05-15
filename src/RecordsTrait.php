<?php
namespace SeanKndy\CSV;

/**
 * Common methods for Records iterator objects
 *
 */
trait RecordsTrait
{
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
     * Return all data from column $col as an array
     *
     * @param string Column name of data to fetch into array
     *
     * @return array
     */
    public function arrayFromColumn(string $col) {
        $data = [];
        foreach ($this as $record) {
            $data[] = $record->get($col);
        }
        return $data;
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
            $columns = array_filter($this->csv->getColumns(), function ($v) use ($exclude) {
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
}
