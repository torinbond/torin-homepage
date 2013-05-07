<?php
/**
 * Parse
 * API for parsing different types of content and templates
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
class Parse
{
    /**
     * Parse a block of YAML into PHP
     *
     * @param string  $yaml  YAML-formatted string to parse
     * @return array
     */
    public static function yaml($yaml)
    {
        return YAML::parse($yaml);
    }


    /**
     * Parses a template, replacing variables with their values
     *
     * @param string  $html  HTML template to parse
     * @param array  $variables  List of variables ($key => $value) to replace into template
     * @return string
     */
public static function template($html, $variables, $callback = null)
{
    $parser = new \Lex\Parser();
    $parser->cumulativeNoparse(TRUE);
    $allow_php = Config::get('_allow_php', false);

    return $parser->parse($html, $variables, $callback, $allow_php);
}


    /**
     * Parses a tag loop, replacing template variables with each array in a list of arrays
     *
     * @param string  $content  Template for replacing
     * @param array  $data  Array of arrays containing values
     * @return string
     */
    public static function tagLoop($content, $data)
    {
        $output = "";

        $count         = 1;
        $total_results = count($data);

        // adds contextual tags to $data
        foreach ($data as $key => $post) {
            $data[$key]['count']         = $count;
            $data[$key]['total_results'] = $total_results;

            if ($count === 1) {
                $data[$key]['first'] = TRUE;
            }

            if ($count == $total_results) {
                $data[$key]['last'] = TRUE;
            }

            $count++;
        }

        // loop through each record of $data
        foreach ($data as $item) {
            $item_content = $content;

            // replace all inline instances of { variable } with the variable's value
            if (preg_match_all(Pattern::TAG, $item_content, $data_matches, PREG_SET_ORDER + PREG_OFFSET_CAPTURE)) {
                foreach ($data_matches as $match) {
                    $tag  = $match[0][0];
                    $name = $match[1][0];
                    if (isset($item[$name])) {
                        $item_content = str_replace($tag, $item[$name], $item_content);
                    }
                }
            }

            // add this record's parsed template to the output string
            $output .= Parse::template($item_content, $item);
        }

        // return what we've parsed
        return $output;
    }


    /**
     * Parses a conditions string
     *
     * @param string  $conditions  Conditions to parse
     * @return array
     */
    public static function conditions($conditions)
    {
        $conditions = explode(",", $conditions);
        $output = array();

        foreach ($conditions as $condition) {
            $result = Parse::condition($condition);
            $output[$result['key']] = $result['value'];
        }

        return $output;
    }


    /**
     * Recursively parses a condition (key:value), returning the key and value
     *
     * @param string  $condition  Condition to parse
     * @return array
     */
    public static function condition($condition)
    {
        // check for a colon
        if (strstr($condition, ":") === FALSE) {
            return array(
                "key" => $condition,
                "value" => NULL
            );
        }

        // breaks this into key => value
        $parts  = explode(":", $condition, 2);

        // return the parsed array
        return array(
            "key" => trim($parts[0]),
            "value" => Parse::conditionValue(trim($parts[1]))
        );
    }


    /**
     * Recursively parses a condition, returning the key and value
     *
     * @param string  $value  Condition to parse
     * @return array
     */
    public static function conditionValue($value)
    {
        // found a bar, split this
        if (strstr($value, "|")) {
            $item = array(
                "type" => "in",
                "value" => explode("|", $value)
            );
        } else {
            if (substr($value, 0, 4) == "not ") {
                $item = array(
                    "type" => "not equal",
                    "value" => substr($value, 4)
                );
            } else {
                $item = array(
                    "type" => "equal",
                    "value" => $value
                );
            }
        }

        return $item;
    }
}