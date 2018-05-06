<?php
namespace SeanKndy\CSV\Formatters;

class Alphanumeric implements Formatter
{
    public function format($data) {
        return preg_replace('/[\W]/', '', $data);
    }
}
