<?php
namespace SeanKndy\CSV\Mutators;

use SeanKndy\CSV\Record;

/**
 *
 */
interface Mutator
{
    /**
     * Apply mutation to $record and return it
     *
     * @param Record $record Record to be mutated
     *
     * @return Record
     */
    public function mutate(Record $record);

    /**
     * If this mutator modifies column structure, then this method should return
     * the re-worked (mutated) header array to reflect the same structure as
     * mutate() performs.  Otherwise this method can just return $columns untouched.
     *
     * @param array $columns Array of column names
     *
     * @return array
     */
    public function mutateHeader(array $columns);
}
