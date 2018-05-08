<?php
namespace SeanKndy\CSV;

/**
 * Container for a few common formatters
 *
 */
class Formatters
{
    public static function alphanumeric($data) {
        return preg_replace('/[\W]/', '', $data);
    }

    public static function date($data, $format, $timezone = 'America/Denver') {
        try {
            $date = new \DateTime(trim($data), new \DateTimeZone($timezone));
            return $date->format($format);
        } catch (\Exception $e) {
            return $data;
        }
    }

    public static function numeric($data) {
        return preg_replace('/[^0-9\.]/', '', $data);
    }
}
