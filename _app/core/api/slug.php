<?php
/**
 * Slug
 * API for interacting and manipulating slugs
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
class Slug
{
    /**
     * Creates a slug from a given $value
     *
     * @param string  $value  Value to make slug from
     * @return string
     */
    public static function make($value)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $value);

        // trim
        $text = trim($text, '-');

        // transliterate
        // if (function_exists('iconv'))
        // {
        //     $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        // }

        // lowercase
        // ### $MUBS$ is this necesary
        // $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }


    /**
     * Humanizes a slug, converting delimiters to spaces
     *
     * @param string  $value  Value to humanize from slug form
     * @return string
     */
    public static function humanize($value)
    {
        return trim(preg_replace('~[-_]~', ' ', $value), " ");
    }


    /**
     * Pretties up a slug, making it title case
     *
     * @param string  $value  Value to pretty
     * @return string
     */
    public static function prettify($value)
    {
        return ucwords(self::humanize($value));
    }


    /**
     * Checks to see whether a given $slug matches the DATE pattern
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isDate($slug)
    {
        if (!preg_match(Pattern::DATE, $slug, $matches)) {
            return FALSE;
        }

        return Pattern::isValidDate($matches[0]);
    }


    /**
     * Checks to see whether a given $slug matches the DATETIME pattern
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isDateTime($slug)
    {
        if (!preg_match(Pattern::DATETIME, $slug, $matches)) {
            return FALSE;
        }

        return Pattern::isValidDate($matches[0]);
    }


    /**
     * Checks to see whether a given $slug matches the NUMERIC pattern
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isNumeric($slug)
    {
        return (bool) preg_match(Pattern::NUMERIC, $slug);
    }


    /**
     * Checks the slug for status indicators
     *
     * @param string  $slug  Slug to check
     * @return string
     */
    public static function getStatus($slug)
    {
        if (substr($slug, 0, 2) === "__") {
            return 'draft';
        } elseif (substr($slug, 0, 1) === "_") {
            return 'hidden';
        }

        return 'live';
    }


    /**
     * Returns the proper status prefix
     *
     * @param string  $status  Status to check
     * @return string
     */
    public static function getStatusPrefix($status)
    {
        if ($status === 'draft') {
            return '__';
        } elseif ($status === 'hidden') {
            return '_';
        }

        return '';
    }

    /**
     * Checks if the slug has a draft indicator
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isDraft($slug)
    {
        return self::getStatus($slug) === 'draft';
    }


    /**
     * Checks if the slug has a hidden indicator
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isHidden($slug)
    {
        return self::getStatus($slug) === 'hidden';
    }


    /**
     * Checks if the slug has a no status indicators (thus, live)
     *
     * @param string  $slug  Slug to check
     * @return bool
     */
    public static function isLive($slug)
    {
        return self::getStatus($slug) === 'live';
    }


    /**
     * Gets the date and time from a given $slug
     *
     * @param string  $slug  Slug to parse
     * @return int
     */
    public static function getTimestamp($slug)
    {
        if (!preg_match(Pattern::DATE_OR_DATETIME, $slug, $matches) || !Pattern::isValidDate($matches[0])) {
            return FALSE;
        }

        $date_string = substr($matches[0], 0, 10);
        $delimiter   = substr($date_string, 4, 1);
        $date_array  = explode($delimiter, $date_string);

        // check to see if this is a full date and time
        $time_string = (strlen($matches[0]) > 11) ? substr($matches[0], 11, 4) : '0000';

        // construct the stringed time
        $date = $date_array[2] . '-' . $date_array[1] . '-' . $date_array[0];
        $time = substr($time_string, 0, 2) . ":" . substr($time_string, 2);

        return strtotime("{$date} {$time}");
    }


    /**
     * Gets the order number from a given $slug
     *
     * @param string  $slug  Slug to parse
     * @return int
     */
    public static function getOrderNumber($slug)
    {
        if (!preg_match(Pattern::NUMERIC, $slug, $matches)) {
            return FALSE;
        }

        return $matches[1];
    }
}
