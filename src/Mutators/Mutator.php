<?php
namespace SeanKndy\CSV\Mutators;

use SeanKndy\CSV\Record;

/**
 *
 */
abstract class Mutator
{
    /**
     * Apply mutation to $record and return it
     *
     * @param Record $record Record to be mutated
     *
     * @return Record
     */
    abstract public function mutate(Record $record);

    /**
     * If this mutator modifies column structure, then this method should return
     * the re-worked (mutated) header array to reflect the same structure as
     * mutate() performs.  Otherwise no need to override this method.
     *
     * @param array $columns Array of column names
     *
     * @return array
     */
    public function mutateHeader(array $columns) {
        return $columns;
    }
}
