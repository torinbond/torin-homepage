<?php

class HTML
{

    /**
     * Convert HTML characters to entities.
     *
     * The encoding specified in the application configuration file will be used.
     *
     * @param  string  $value
     * @return string
     */
    public static function convertEntities($value)
    {
        return htmlentities($value, ENT_QUOTES, Config::get('encoding', 'UTF-8'), FALSE);
    }

    /**
     * Convert entities to HTML characters.
     *
     * @param  string  $value
     * @return string
     */
    public static function decodeEntities($value)
    {
        return html_entity_decode($value, ENT_QUOTES, Config::get('encoding', 'UTF-8'));
    }

    /**
     * Convert HTML special characters.
     *
     * The encoding specified in the application configuration file will be used.
     *
     * @param  string  $value
     * @return string
     */
    public static function convertSpecialCharacters($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, Config::get('encoding', 'UTF-8'), FALSE);
    }

    /**
     * Generate a link to a JavaScript file.
     *
     * <code>
     *    // Generate a link to a JavaScript file
     *    echo HTML::script('js/jquery.js');
     *
     *    // Generate a link to a JavaScript file and add some attributes
     *    echo HTML::script('js/jquery.js', array('defer'));
     * </code>
     *
     * @param  string  $url
     * @param  array   $attributes
     * @return string
     */
    public static function includeScript($url, $attributes = array())
    {
        // $url = URL::to_asset($url);

        return '<script src="' . $url . '"' . static::buildAttributes($attributes) . '></script>' . PHP_EOL;
    }

    /**
     * Generate a link to a CSS file.
     *
     * If no media type is selected, "all" will be used.
     *
     * <code>
     *    // Generate a link to a CSS file
     *    echo HTML::style('css/common.css');
     *
     *    // Generate a link to a CSS file and add some attributes
     *    echo HTML::style('css/common.css', array('media' => 'print'));
     * </code>
     *
     * @param  string  $url
     * @param  array   $attributes
     * @return string
     */
    public static function includeStylesheet($url, $attributes = array())
    {
        $defaults = array('media' => 'all', 'type' => 'text/css', 'rel' => 'stylesheet');

        $attributes = $attributes + $defaults;

        // $url = URL::to_asset($url);

        return '<link href="' . $url . '"' . static::buildAttributes($attributes) . '>' . PHP_EOL;
    }

    /**
     * Generate a HTML link.
     *
     * <code>
     *    // Generate a link to a location within the application
     *    echo HTML::makeLink('user/profile', 'User Profile');
     *
     *    // Generate a link to a location outside of the application
     *    echo HTML::makeLink('http://google.com', 'Google');
     * </code>
     *
     * @param  string  $url
     * @param  string  $title
     * @param  array   $attributes
     * @param  bool    $https
     * @return string
     */
    public static function makeLink($url, $title = NULL, $attributes = array(), $https = NULL)
    {
        $url = URL::to($url, $https);

        if (is_null($title)) $title = $url;

        return '<a href="' . $url . '"' . static::buildAttributes($attributes) . '>' . static::convertEntities($title) . '</a>';
    }

    /**
     * Generate a HTTPS HTML link.
     *
     * @param  string  $url
     * @param  string  $title
     * @param  array   $attributes
     * @return string
     */
    public static function linkToSecure($url, $title = NULL, $attributes = array())
    {
        return static::makeLink($url, $title, $attributes, TRUE);
    }


    /**
     * Generate an HTML mailto link.
     *
     * The E-Mail address will be obfuscated to protect it from spam bots.
     *
     * @param  string  $email
     * @param  string  $title
     * @param  array   $attributes
     * @return string
     */
    public static function mailTo($email, $title = NULL, $attributes = array())
    {
        $email = static::obfuscateEmail($email);

        if (is_null($title)) $title = $email;

        $email = '&#109;&#097;&#105;&#108;&#116;&#111;&#058;' . $email;

        return '<a href="' . $email . '"' . static::buildAttributes($attributes) . '>' . static::convertEntities($title) . '</a>';
    }

    /**
     * Obfuscate an e-mail address to prevent spam-bots from sniffing it.
     *
     * @param  string  $email
     * @return string
     */
    public static function obfuscateEmail($email)
    {
        return str_replace('@', '&#64;', static::obfuscate($email));
    }

    /**
     * Generate an HTML image element.
     *
     * @param  string  $url
     * @param  string  $alt
     * @param  array   $attributes
     * @return string
     */
    public static function makeImage($url, $alt = '', $attributes = array())
    {
        $attributes['alt'] = $alt;

        return '<img src="' . URL::to_asset($url) . '"' . static::buildAttributes($attributes) . '>';
    }

    /**
     * Generate an ordered list of items.
     *
     * @param  array   $list
     * @param  array   $attributes
     * @return string
     */
    public static function makeOl($list, $attributes = array())
    {
        return static::makeList('ol', $list, $attributes);
    }

    /**
     * Generate an un-ordered list of items.
     *
     * @param  array   $list
     * @param  array   $attributes
     * @return string
     */
    public static function makeUl($list, $attributes = array())
    {
        return static::makeList('ul', $list, $attributes);
    }

    /**
     * Generate an ordered or un-ordered list.
     *
     * @param  string  $type
     * @param  array   $list
     * @param  array   $attributes
     * @return string
     */
    private static function makeList($type, $list, $attributes = array())
    {
        $html = '';

        if (count($list) == 0) return $html;

        foreach ($list as $key => $value) {
            // If the value is an array, we will recurse the function so that we can
            // produce a nested list within the list being built. Of course, nested
            // lists may exist within nested lists, etc.
            if (is_array($value)) {
                if (is_int($key)) {
                    $html .= static::makeList($type, $value);
                } else {
                    $html .= '<li>' . $key . static::makeList($type, $value) . '</li>';
                }
            } else {
                $html .= '<li>' . static::convertEntities($value) . '</li>';
            }
        }

        return '<' . $type . static::buildAttributes($attributes) . '>' . $html . '</' . $type . '>';
    }

    /**
     * Build a list of HTML attributes from an array.
     *
     * @param  array   $attributes
     * @return string
     */
    public static function buildAttributes($attributes)
    {
        $html = array();

        foreach ((array)$attributes as $key => $value) {
            // For numeric keys, we will assume that the key and the value are the
            // same, as this will convert HTML attributes such as "required" that
            // may be specified as required="required", etc.
            if (is_numeric($key)) $key = $value;

            if (!is_null($value)) {
                $html[] = $key . '="' . static::convertEntities($value) . '"';
            }
        }

        return (count($html) > 0) ? ' ' . implode(' ', $html) : '';
    }

    /**
     * Obfuscate a string to prevent spam-bots from sniffing it.
     *
     * @param  string  $value
     * @return string
     */
    protected static function obfuscate($value)
    {
        $safe = '';

        foreach (str_split($value) as $letter) {
            // To properly obfuscate the value, we will randomly convert each
            // letter to its entity or hexadecimal representation, keeping a
            // bot from sniffing the randomly obfuscated letters.
            switch (rand(1, 3)) {
                case 1:
                    $safe .= '&#' . ord($letter) . ';';
                    break;

                case 2:
                    $safe .= '&#x' . dechex(ord($letter)) . ';';
                    break;

                case 3:
                    $safe .= $letter;
            }
        }

        return $safe;
    }

}
