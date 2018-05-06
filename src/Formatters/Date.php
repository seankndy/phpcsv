<?php
namespace SeanKndy\CSV\Formatters;

class Date implements Formatter
{
    protected $format;
    protected $tz;

    public function __construct($format, $tz = 'America/Denver') {
        $this->format = $format;
        $this->tz = $tz;
    }

    public function format($data) {
        $date = new \DateTime(trim($data), new \DateTimeZone($this->tz));
        return $date->format($this->format);
    }
}
