<?php
/**
 * Helper
 * API for doing miscellaneous tasks
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
class Helper
{
    /**
     * Picks the first value that isn't null
     *
     * @return mixed
     */
    public static function pick()
    {
        $args = func_get_args();

        if (!is_array($args) || !count($args)) {
            return NULL;
        }

        foreach ($args as $arg) {
            if (!is_null($arg)) {
                return $arg;
            }
        }
    }


    /**
     * Creates a random string
     *
     * @param int  $length  Length of string to return
     * @return string
     */
    public static function getRandomString($length=32)
    {
        $string = '';
        $characters = "BCDFGHJKLMNPQRSTVWXYZbcdfghjklmnpqrstvwxwz0123456789";
        $upper_limit = strlen($characters) - 1;

        for (; $length > 0; $length--) {
            $string .= $characters{rand(0, $upper_limit)};
        }

        return str_shuffle($string);
    }


    /**
     * Checks whether the given $value is an empty array or not
     *
     * @param mixed  $value  Value to check
     * @return bool
     */
    public static function isEmptyArray($value)
    {
        if (is_array($value)) {
            foreach ($value as $subvalue) {
                if (!self::isEmptyArray($subvalue)) {
                    return FALSE;
                }
            }
        } elseif (!empty($value) || $value !== '') {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Ensures that a given variable is an array
     *
     * @param mixed $value  variable to check
     * @return array
     **/
    public static function ensureArray($value)
    {
        if ( ! is_array($value)) {
            return array($value);
        }

        return $value;
    }


    /**
     * Explodes options into an array
     *
     * @param string  $string  String to explode
     * @param bool $keyed  Are options keyed?
     * @return array
     */
    public static function explodeOptions($string, $keyed=FALSE)
    {
        $options = explode('|', $string);

        if ($keyed) {

            $temp_options = array();
            foreach ($options as $value) {

                if (strpos($value, ':')) {
                    # key:value pair present
                    list($option_key, $option_value) = explode(':', $value);
                } else {
                    # default value is false
                    $option_key   = $value;
                    $option_value = FALSE;
                }

                # set the main options array
                $temp_options[$option_key] = $option_value;
            }
            # reassign and override
            $options = $temp_options;
        }

        return $options;
    }


    /**
     * Compares two values, returns 1 if first is greater, -1 if second is, 0 if same
     *
     * @param mixed  $value_1  Value 1 to compare
     * @param mixed  $value_2  Value 2 to compare
     * @return int
     */
    public static function compareValues($value_1, $value_2) {
        // something is NULL
        if (is_null($value_1) || is_null($value_2)) {
            if (is_null($value_1) && !is_null($value_2)) {
                return 1;
            } elseif (!is_null($value_1) && is_null($value_2)) {
                return -1;
            }

            return 0;
        }

        // something is an array
        if (is_array($value_1) || is_array($value_2)) {
            if (is_array($value_1) && !is_array($value_2)) {
                return 1;
            } elseif (!is_array($value_1) && is_array($value_2)) {
                return -1;
            }

            return 0;
        }

        // something is an object
        if (is_object($value_1) || is_object($value_2)) {
            if (is_object($value_1) && !is_object($value_2)) {
                return 1;
            } elseif (!is_object($value_1) && is_object($value_2)) {
                return -1;
            }

            return 0;
        }

        // something is a boolean
        if (is_bool($value_1) || is_bool($value_2)) {
            if ($value_1 && !$value_2) {
                return 1;
            } elseif (!$value_1 && $value_2) {
                return -1;
            }

            return 0;
        }

        // string based
        if (!is_numeric($value_1) || !is_numeric($value_2)) {
            return strcasecmp($value_1, $value_2);
        }

        // number-based
        if ($value_1 > $value_2) {
            return 1;
        } elseif ($value_1 < $value_2) {
            return -1;
        }

        return 0;
    }


    /**
     * Creates a sentence list from the given $list
     *
     * @param array  $list  List of items to list
     * @param string  $glue  Joining string before the last item when more than one item
     * @param bool  $oxford_comma  Include a comma before $glue?
     * @return string
     */
    public static function makeSentenceList(Array $list, $glue="and", $oxford_comma=TRUE)
    {
        $length = count($list);

        switch ($length) {
            case 0:
            case 1:
                return join("", $list);
                break;

            case 2:
                return join(" " . $glue . " ", $list);
                break;

            default:
                $last = array_pop($list);
                $sentence  = join(", ", $list);
                $sentence .= ($oxford_comma) ? "," : "";
                return $sentence . " " . $glue . " " . $last;
        }
    }


    /**
     * Resolves a given $value, if a closure, calls closure, otherwise returns $value
     *
     * @param mixed  $value  Value or closure to resolve
     * @return mixed
     */
    public static function resolveValue($value)
    {
        return (is_callable($value) && !is_string($value)) ? call_user_func($value) : $value;
    }


    /**
     * Confirms $array is an array, then returns $key if key is set, $that if it isn't
     *
     * @param mixed  $array  Array to use
     * @param string  $key  Key to return if set
     * @param mixed  $default  Default value to return
     * @return mixed
     */
    public static function choose($array, $key, $default)
    {
        return (is_array($array) && isset($array[$key])) ? $array[$key] : $default;
    }
    
    
    /**
     * Parses a mixed folder representation into a standardized array
     * 
     * @param mixed  $folders  Folders
     * @return array
     */
    public static function parseForFolders($folders)
    {
        $output = array();
        
        // make an array of all options
        if (is_array($folders)) {
            foreach ($folders as $folder) {
                if (strpos($folder, "|") !== false) {
                    $output = array_merge($output, explode("|", $folder));
                } else {
                    array_push($output, $folder);
                }
            }
        } else {
            if (strpos($folders, "|") !== false) {
                $output = explode("|", $folders);
            } else {
                array_push($output, $folders);
            }
        }

        // now fix the array
        if (!count($output)) {
            $output = array();
        } else {
            $output = array_map(function($item) {
                return ($item === "/") ? "" : $item;
            }, $output);
        }
        
        return array_unique($output);
    }
}
