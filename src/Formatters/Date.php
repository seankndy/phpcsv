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
        try {
            $date = new \DateTime(trim($data), new \DateTimeZone($this->tz));
            return $date->format($this->format);
        } catch (\Exception $e) {
            return $data;
        }
    }
}
