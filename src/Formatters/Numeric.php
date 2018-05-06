<?php
namespace SeanKndy\CSV\Formatters;

class Numeric implements Formatter
{
    public function format($data) {
        return preg_replace('/[^0-9\.]/', '', $data);
    }
}
