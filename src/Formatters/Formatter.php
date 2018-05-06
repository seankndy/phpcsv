<?php
namespace SeanKndy\CSV\Formatters;

interface Formatter
{
    /**
     * Format $data and return it.
     *
     * @param string $data Data to be formatted.
     *
     * @return mixed
     */
    public function format($data);
}
