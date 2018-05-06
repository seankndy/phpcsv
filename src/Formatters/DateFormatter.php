<?php
namespace SeanKndy\CSV\Formatters;

class DateFormatter implements Formatter
{
    protected $format;

    public function __construct($format) {
        $this->format = $format;
    }
    
    public function format($data) {
        $date = new DateTime(trim($data));
        return $date->format($this->format);
    }
}
