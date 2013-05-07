<?php

namespace Lex;

class Parser
{
    protected $allowPhp = false;
    protected $regexSetup = false;
    protected $scopeGlue = ':';
    protected $tagRegex = '';
    protected $cumulativeNoparse = false;

    protected $inCondition = false;

    protected $variableRegex = '';
    protected $variableLoopRegex = '';
    protected $variableTagRegex = '';

    protected $callbackTagRegex = '';
    protected $callbackLoopTagRegex = '';

    protected $noparseRegex = '';

    protected $conditionalRegex = '';
    protected $conditionalElseRegex = '';
    protected $conditionalEndRegex = '';
    protected $conditionalData = array();

    protected static $extractions = array(
        'noparse' => array(),
    );

    protected static $data = null;
    protected static $callbackData = array();

    /**
     * The main Lex parser method.  Essentially acts as dispatcher to
     * all of the helper parser methods.
     *
     * @param  string       $text     Text to parse
     * @param  array|object $data     Array or object to use
     * @param  mixed        $callback Callback to use for Callback Tags
     * @return string
     */
    public function parse($text, $data = array(), $callback = false, $allowPhp = false)
    {
        $this->setupRegex();
        $this->allowPhp = $allowPhp;

        // Is this the first time parse() is called?
        if (self::$data === null) {
            // Let's store the local data array for later use.
            self::$data = $data;
        } else {
            // Let's merge the current data array with the local scope variables
            // So you can call local variables from within blocks.
            $data = array_merge(self::$data, $data);

            // Since this is not the first time parse() is called, it's most definately a callback,
            // let's store the current callback data with the the local data
            // so we can use it straight after a callback is called.
            self::$callbackData = $data;
        }

        // The parseConditionals method executes any PHP in the text, so clean it up.
        if (! $allowPhp) {
            $text = str_replace(array('<?', '?>'), array('&lt;?', '?&gt;'), $text);
        }

        $text = $this->extractNoparse($text);
        $text = $this->parseComments($text);
        $text = $this->extractLoopedTags($text, $data, $callback);

        // Order is important here.  We parse conditionals first as to avoid
        // unnecessary code from being parsed and executed.
        $text = $this->parseConditionals($text, $data, $callback);
        $text = $this->injectExtractions($text, 'looped_tags');
        $text = $this->parseVariables($text, $data, $callback);
        $text = $this->injectExtractions($text, 'callback_blocks');

        if ($callback) {
            $text = $this->parseCallbackTags($text, $data, $callback);
        }

        // To ensure that {{ noparse }} is never parsed even during consecutive parse calls
        // set $cumulativeNoparse to true and use self::injectNoparse($text); immediately
        // before the final output is sent to the browser
        if (! $this->cumulativeNoparse) {
            $text = $this->injectExtractions($text);
        }

        // no callback callback, hack to get tag pairs with parameters to work
        if (strstr($text, "{{"))  {
            $text = $this->parseCallbackTags($text, $data, null);
        }

        return $text;
    }

    /**
     * Removes all of the comments from the text.
     *
     * @param  string $text Text to remove comments from
     * @return string
     */
    public function parseComments($text)
    {
        $this->setupRegex();

        return preg_replace('/\{\{#.*?#\}\}/s', '', $text);
    }

    /**
     * Recursivly parses all of the variables in the given text and
     * returns the parsed text.
     *
     * @param  string       $text Text to parse
     * @param  array|object $data Array or object to use
     * @return string
     */
    public function parseVariables($text, $data, $callback = null)
    {
        $this->setupRegex();

        $datev = false;
        if (stripos($text, "datestamp")) {
            $datev = true;
        }

        /**
         * $data_matches[][0][0] is the raw data loop tag
         * $data_matches[][0][1] is the offset of raw data loop tag
         * $data_matches[][1][0] is the data variable (dot notated)
         * $data_matches[][1][1] is the offset of data variable
         * $data_matches[][2][0] is the content to be looped over
         * $data_matches[][2][1] is the offset of content to be looped over
         */
        if (preg_match_all($this->variableLoopRegex, $text, $data_matches, PREG_SET_ORDER + PREG_OFFSET_CAPTURE)) {
            foreach ($data_matches as $index => $match) {
                if ($loop_data = $this->getVariable($match[1][0], $data)) {
                    $looped_text = '';
                    if (is_array($loop_data)) {
                        $index = 0;

                        foreach ($loop_data as $item_data => $value) {
                            $index = $index + 1;
                            $str = "";
                            if (is_array($item_data)) {
                                if (!isset($item_data['index'])) {
                                    $item_data['index'] = $index;
                                }
                                if (!isset($item_data['total_results'])) {
                                    $item_data['total_results'] = sizeof($loop_data);
                                }

                                $str = $this->parseConditionals($match[2][0], $item_data, $callback);
                                $str = $this->parseVariables($str, $item_data, $callback);
                                if ($callback !== null) {
                                    $str = $this->parseCallbackTags($str, $item_data, $callback);
                                }
                            } else {
                                $array_data = array($item_data => $value);

                                if (is_array($value)) {
                                    $value['index'] = $index;
                                    $value['total_results'] = sizeof($loop_data);
                                    $value['first'] = false;
                                    $value['last'] = false;
                                    if ($index == 1) {
                                        $value['first'] = true;
                                    }
                                    if ($index == $value['total_results']) {
                                        $value['last'] = true;
                                    }
                                    $str = $this->parseConditionals($match[2][0], $value, $callback);
                                } else {
                                    $str = $this->parseConditionals($match[2][0], $loop_data, $callback);
                                }
                                $str = $this->parseVariables($str, $value, $callback);
                                if ($callback !== null) {
                                    $str = $this->parseCallbackTags($str, $array_data, $callback);
                                }
                            }
                            $looped_text .= $str;
                        }
                        $text = preg_replace('/'.preg_quote($match[0][0], '/').'/m', addcslashes($looped_text, '\\$'), $text, 1);
                    } else {
                        return $loop_data;
                    }
                } else { // It's a callback block.
                    // Let's extract it so it doesn't conflict
                    // with the local scope variables in the next step.
                    $text = $this->createExtraction('callback_blocks', $match[0][0], $match[0][0], $text);
                }
            }
        }

        /**
         * $data_matches[0] is the raw data tag
         * $data_matches[1] is the data variable (dot notated)
         */
        if (preg_match_all($this->variableTagRegex, $text, $data_matches)) {
            foreach ($data_matches[1] as $index => $var) {
                if (($val = $this->getVariable($var, $data, '__lex_no_value__')) !== '__lex_no_value__') {
                    $text = str_replace($data_matches[0][$index], $val, $text);
                }
            }
        }

        $regex = '/\{\{\s*('.$this->variableRegex.')(\s+.*?)?\s*\}\}/ms';
        if (preg_match_all($regex, $text, $data_matches, PREG_SET_ORDER + PREG_OFFSET_CAPTURE)) {
            foreach ($data_matches as $match) {

                $parameters = array();
                $tag = $match[0][0];
                $start = $match[0][1];
                $name = $match[1][0];

                if ($name != 'content' && isset($data[$name])) {
                    if (isset($match[2])) {
                        $cb_data = $data;
                        if (is_array($data)) {
                            if ( !empty(self::$callbackData)) {
                                $cb_data = array_merge(self::$callbackData, $data);
                            }
                            $raw_params = $this->injectExtractions($match[2][0], '__cond_str');
                            $parameters = $this->parseParameters($raw_params, $cb_data, $callback);
                        } else if (is_string($data)) {
                            $text = str_replace($tag, $data, $text);
                        }
                    }

                    if (isset($parameters['format'])) {
                        $value = "";
                        if (is_integer($data[$name])) {
                            $value = date($parameters['format'], $data[$name]);
                            $text = str_replace($tag, $value, $text);
                        } else if (is_string($data[$name])) {
                            $value = date($parameters['format'], strtotime($data[$name]));
                            $text = str_replace($tag, $value, $text);
                        }
                    }
                }
            }
        }

        return $text;
    }

    /**
     * Parses all Callback tags, and sends them through the given $callback.
     *
     * @param  string $text           Text to parse
     * @param  mixed  $callback       Callback to apply to each tag
     * @param  bool   $inConditional Whether we are in a conditional tag
     * @return string
     */
    public function parseCallbackTags($text, $data, $callback)
    {
        $extractions = array();
        $this->setupRegex();
        $inCondition = $this->inCondition;

        if ($inCondition) {
            $regex = '/\{\s*('.$this->variableRegex.')(\s+.*?)?\s*\}/ms';
        } else {
            $regex = '/\{\{\s*('.$this->variableRegex.')(\s+.*?)?\s*\}\}/ms';
        }
        /**
         * $match[0][0] is the raw tag
         * $match[0][1] is the offset of raw tag
         * $match[1][0] is the callback name
         * $match[1][1] is the offset of callback name
         * $match[2][0] is the parameters
         * $match[2][1] is the offset of parameters
         */
        while (preg_match($regex, $text, $match, PREG_OFFSET_CAPTURE)) {
            $parameters = array();
            $tag = $match[0][0];
            $start = $match[0][1];
            $name = $match[1][0];
            if (isset($match[2])) {
                $cb_data = $data;
                if ( !empty(self::$callbackData)) {
                    $cb_data = array_merge(self::$callbackData, $data);
                }
                $raw_params = $this->injectExtractions($match[2][0], '__cond_str');
                $parameters = $this->parseParameters($raw_params, $cb_data, $callback);
                $count = 0;

                // $$$ MUBS: Replace variables in the parameters
                foreach ($parameters as $key => $value) {
                    if (preg_match('/\{\s*('.$this->variableRegex.')\s*\}/', $value)) {
                        $value = str_replace('{', '{{', $value);
                        $value = str_replace('}', '}}', $value);
                        $parameters[$key] = $this->parseVariables($value, $data);
                    }
                }
            }
            $content = '';

            $temp_text = substr($text, $start + strlen($tag));
            if (preg_match('/\{\{\s*\/'.preg_quote($name, '/').'\s*\}\}/m', $temp_text, $temp_match, PREG_OFFSET_CAPTURE)) {
                $content = substr($temp_text, 0, $temp_match[0][1]);
                $tag .= $content.$temp_match[0][0];

                // Is there a nested block under this one existing with the same name?
                $nested_regex = '/\{\{\s*('.preg_quote($name, '/').')(\s.*?)\}\}(.*?)\{\{\s*\/\1\s*\}\}/ms';
                if (preg_match($nested_regex, $content.$temp_match[0][0], $nested_matches)) {
                    $nested_content = preg_replace('/\{\{\s*\/'.preg_quote($name, '/').'\s*\}\}/m', '', $nested_matches[0]);
                    $content = $this->createExtraction('nested_looped_tags', $nested_content, $nested_content, $content);
                }
            }

            if ($callback) {
                $replacement = call_user_func_array($callback, array($name, $parameters, $content));
                $replacement = $this->parseRecursives($replacement, $content, $callback);
            } else {
                $replacement = '';
            }

            if ($name != 'content' && $replacement == '') {
                // ### $$$
                if (isset($data[$name])) {
                    $values = $data[$name];

                    if (isset($parameters['sort_by'])) {
                        $field = $parameters['sort_by'];

                        if ($field == 'random') {
                            shuffle($values);
                        } else {
                            usort($values, function($a, $b) use ($field)
                            {
                                if (isset($a[$field]) && (isset($b[$field]))) {
                                    return strcmp($a[$field], $b[$field]);
                                } else {
                                    return true;
                                }
                            });
                        }
                    }

                    if (isset($parameters['sort_dir'])) {
                        $dir = $parameters['sort_dir'];
                        if ($dir == 'desc') {
                            $values = array_reverse($values);
                        }
                    }

                    if (isset($parameters['first'])) {
                        $values = array_splice($values, 0, 1);
                    }

                    if (isset($parameters['last'])) {
                        $values = array_splice($values, -1, 1);
                    }

                    if (isset($parameters['offset'])) {
                        $values = array_splice($values, $parameters['offset']);
                    }

                    if (isset($parameters['index'])) {
                        $values = array_splice($values, $parameters['index']-1, 1);
                    }

                    if (isset($parameters['limit'])) {
                        $values = array_splice($values, 0, $parameters['limit']);
                    }

                    if (is_array($values)) {
                        $num = 1;
                        foreach ($values as $k => $v) {
                            if (!isset($values[$k]['index'])) {
                                $values[$k]['index'] = $num;
                            }
                            $num ++;
                        }
                    }

                    $sdata = array();
                    $sdata[$name] = $values;

                    if (is_array($sdata)) {
                        if (isset($sdata[$name]) && $sdata[$name] <> '') {
                            $replacement = $this->parseVariables("{{ $name }}$content{{ /$name }}", $sdata, $callback);
                        } else {
                            $replacement = "";
                        }
                    }
                } else {
                    // $$$ MUBS
                    if ($callback == null) {
                        $text = $this->createExtraction('__variables_not_callbacks', $text, $text, $text);
                    }
                }
            }

            if ($inCondition) {
                $replacement = $this->valueToLiteral($replacement);
            }

            $text = preg_replace('/'.preg_quote($tag, '/').'/m', addcslashes($replacement, '\\$'), $text, 1);
            $text = $this->injectExtractions($text, 'nested_looped_tags');
        }

        // reinject?
        if ($callback == null) {
            $text = $this->injectExtractions($text, '__variables_not_callbacks');
        }

        return $text;
    }

    /**
     * Parses all conditionals, then executes the conditionals.
     *
     * @param  string $text     Text to parse
     * @param  mixed  $data     Data to use when executing conditionals
     * @param  mixed  $callback The callback to be used for tags
     * @return string
     */
    public function parseConditionals($text, $data, $callback)
    {
        $this->setupRegex();
        preg_match_all($this->conditionalRegex, $text, $matches, PREG_SET_ORDER);

        $this->conditionalData = $data;

        /**
         * $matches[][0] = Full Match
         * $matches[][1] = Either 'if', 'unless', 'elseif', 'elseunless'
         * $matches[][2] = Condition
         */
        foreach ($matches as $match) {
            $this->inCondition = true;

            $condition = $match[2];

            // Extract all literal string in the conditional to make it easier
            if (preg_match_all('/(["\']).*?(?<!\\\\)\1/', $condition, $str_matches)) {
                foreach ($str_matches[0] as $m) {
                    $condition = $this->createExtraction('__cond_str', $m, $m, $condition);
                }
            }
            $condition = preg_replace($this->conditionalNotRegex, '$1!$2', $condition);

            if (preg_match_all($this->conditionalExistsRegex, $condition, $existsMatches, PREG_SET_ORDER)) {
                foreach ($existsMatches as $m) {
                    $exists = 'true';
                    if ($this->getVariable($m[2], $data, '__doesnt_exist__') === '__doesnt_exist__') {
                        $exists = 'false';
                    }
                    $condition = $this->createExtraction('__cond_exists', $m[0], $m[1].$exists.$m[3], $condition);
                }
            }

            $condition = preg_replace_callback('/\b('.$this->variableRegex.')\b/', array($this, 'processConditionVar'), $condition);

            if ($callback) {
                $condition = preg_replace('/\b(?!\{\s*)('.$this->callbackNameRegex.')(?!\s+.*?\s*\})\b/', '{$1}', $condition);
                $condition = $this->parseCallbackTags($condition, $data, $callback);

                // Incase the callback returned a string, we need to extract it
                if (preg_match_all('/(["\']).*?(?<!\\\\)\1/', $condition, $str_matches)) {
                    foreach ($str_matches[0] as $m) {
                        $condition = $this->createExtraction('__cond_str', $m, $m, $condition);
                    }
                }
            }

            // Re-process for variables, we trick processConditionVar so that it will return null
            $this->inCondition = false;
            $condition = preg_replace_callback('/\b('.$this->variableRegex.')\b/', array($this, 'processConditionVar'), $condition);
            $this->inCondition = true;

            // Re-inject any strings we extracted
            $condition = $this->injectExtractions($condition, '__cond_str');
            $condition = $this->injectExtractions($condition, '__cond_exists');

            $conditional = '<?php ';

            if ($match[1] == 'unless') {
                $conditional .= 'if ( ! ('.$condition.'))';
            } elseif ($match[1] == 'elseunless') {
                $conditional .= 'elseif ( ! ('.$condition.'))';
            } else {
                $conditional .= $match[1].' ('.$condition.')';
            }

            $conditional .= ': ?>';

            $text = preg_replace('/'.preg_quote($match[0], '/').'/m', addcslashes($conditional, '\\$'), $text, 1);
        }

        $text = preg_replace($this->conditionalElseRegex, '<?php else: ?>', $text);
        $text = preg_replace($this->conditionalEndRegex, '<?php endif; ?>', $text);

        $text = $this->parsePhp($text);
        $this->inCondition = false;

        return $text;
    }

    /**
     * Goes recursively through a callback tag with a passed child array.
     *
     * @param  string $text      - The replaced text after a callback.
     * @param  string $orig_text - The original text, before a callback is called.
     * @param  mixed  $callback
     * @return string $text
     */
    public function parseRecursives($text, $orig_text, $callback)
    {
        // Is there a {{ *recursive [array_key]* }} tag here, let's loop through it.
        if (preg_match($this->recursiveRegex, $text, $match)) {
            $array_key = $match[1];
            $tag = $match[0];
            $next_tag = null;
            $children = self::$callbackData[$array_key];
            $child_count = count($children);
            $count = 1;

            // Is the array not multi-dimensional? Let's make it multi-dimensional.
            if ($child_count == count($children, COUNT_RECURSIVE)) {
                $children = array($children);
                $child_count = 1;
            }

            foreach ($children as $child) {
                $has_children = true;

                // If this is a object let's convert it to an array.
                is_array($child) OR $child = (array) $child;

                // Does this child not contain any children?
                // Let's set it as empty then to avoid any errors.
                if ( ! array_key_exists($array_key, $child)) {
                    $child[$array_key] = array();
                    $has_children = false;
                }

                $replacement = $this->parse($orig_text, $child, $callback, $this->allowPhp);

                // If this is the first loop we'll use $tag as reference, if not
                // we'll use the previous tag ($next_tag)
                $current_tag = ($next_tag !== null) ? $next_tag : $tag;

                // If this is the last loop set the next tag to be empty
                // otherwise hash it.
                $next_tag = ($count == $child_count) ? '' : md5($tag.$replacement);

                $text = str_replace($current_tag, $replacement.$next_tag, $text);

                if ($has_children) {
                    $text = $this->parseRecursives($text, $orig_text, $callback);
                }
                $count++;
            }
        }

        return $text;
    }

    /**
     * Gets or sets the Scope Glue
     *
     * @param  string|null $glue The Scope Glue
     * @return string
     */
    public function scopeGlue($glue = null)
    {
        if ($glue !== null) {
            $this->regexSetup = false;
            $this->scopeGlue = $glue;
        }

        return $this->scopeGlue;
    }

    /**
     * Sets the noparse style. Immediate or cumulative.
     *
     * @param  bool $mode
     * @return void
     */
    public function cumulativeNoparse($mode)
    {
        $this->cumulativeNoparse = $mode;
    }

    /**
     * Injects noparse extractions.
     *
     * This is so that multiple parses can store noparse
     * extractions and all noparse can then be injected right
     * before data is displayed.
     *
     * @param  string $text Text to inject into
     * @return string
     */
    public static function injectNoparse($text)
    {
        if (isset(self::$extractions['noparse'])) {
            foreach (self::$extractions['noparse'] AS $hash => $replacement) {
                if (strpos($text, "noparse_{$hash}") !== FALSE) {
                    $text = str_replace("noparse_{$hash}", $replacement, $text);
                }
            }
        }

        return $text;
    }

    /**
     * This is used as a callback for the conditional parser.  It takes a variable
     * and returns the value of it, properly formatted.
     *
     * @param  array  $match A match from preg_replace_callback
     * @return string
     */
    protected function processConditionVar($match)
    {
        $var = is_array($match) ? $match[0] : $match;
        if (in_array(strtolower($var), array('true', 'false', 'null', 'or', 'and')) or
            strpos($var, '__cond_str') === 0 or
            strpos($var, '__cond_exists') === 0 or
            is_numeric($var))
        {
            return $var;
        }

        $value = $this->getVariable($var, $this->conditionalData, '__processConditionVar__');

        if ($value === '__processConditionVar__') {
            return $this->inCondition ? $var : 'null';
        }

        return $this->valueToLiteral($value);
    }

    /**
     * This is used as a callback for the conditional parser.  It takes a variable
     * and returns the value of it, properly formatted.
     *
     * @param  array  $match A match from preg_replace_callback
     * @return string
     */
    protected function processParamVar($match)
    {
        return $match[1].$this->processConditionVar($match[2]);
    }

    /**
     * Takes a value and returns the literal value for it for use in a tag.
     *
     * @param  string $value Value to convert
     * @return string
     */
    protected function valueToLiteral($value)
    {
        if (is_object($value) and is_callable(array($value, '__toString'))) {
            return var_export((string) $value, true);
        } elseif (is_array($value)) {
            return !empty($value) ? "true" : "false";
        } else {
            return var_export($value, true);
        }
    }

    /**
     * Sets up all the global regex to use the correct Scope Glue.
     *
     * @return void
     */
    protected function setupRegex()
    {
        if ($this->regexSetup) {
            return;
        }
        $glue = preg_quote($this->scopeGlue, '/');

        $this->variableRegex = $glue === '\\.' ? '[|a-zA-Z\-,0-9_'.$glue.']+' : '[|a-zA-Z\-,0-9_\.'.$glue.']+';
        $this->callbackNameRegex = $this->variableRegex.$glue.$this->variableRegex;
        $this->variableLoopRegex = '/\{\{\s*('.$this->variableRegex.')\s*\}\}(.*?)\{\{\s*\/\1\s*\}\}/ms';
        $this->variableTagRegex = '/\{\{\s*('.$this->variableRegex.')\s*\}\}/m';

        $this->callbackBlockRegex = '/\{\{\s*('.$this->variableRegex.')(\s.*?)\}\}(.*?)\{\{\s*\/\1\s*\}\}/ms';

        $this->recursiveRegex = '/\{\{\s*\*recursive\s*('.$this->variableRegex.')\*\s*\}\}/ms';

        $this->noparseRegex = '/\{\{\s*noparse\s*\}\}(.*?)\{\{\s*\/noparse\s*\}\}/ms';

        $this->conditionalRegex = '/\{\{\s*(if|unless|elseif|elseunless)\s*((?:\()?(.*?)(?:\))?)\s*\}\}/ms';
        $this->conditionalElseRegex = '/\{\{\s*else\s*\}\}/ms';
        $this->conditionalEndRegex = '/\{\{\s*endif\s*\}\}/ms';
        $this->conditionalExistsRegex = '/(\s+|^)exists\s+('.$this->variableRegex.')(\s+|$)/ms';
        $this->conditionalNotRegex = '/(\s+|^)not(\s+|$)/ms';

        $this->regexSetup = true;

        // This is important, it's pretty unclear by the documentation
        // what the default value is on <= 5.3.6
        ini_set('pcre.backtrack_limit', 1000000);
    }

    /**
     * Extracts the noparse text so that it is not parsed.
     *
     * @param  string $text The text to extract from
     * @return string
     */
    protected function extractNoparse($text)
    {
        /**
         * $matches[][0] is the raw noparse match
         * $matches[][1] is the noparse contents
         */
        if (preg_match_all($this->noparseRegex, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $text = $this->createExtraction('noparse', $match[0], $match[1], $text);
            }
        }

        return $text;
    }

    /**
     * Extracts the looped tags so that we can parse conditionals then re-inject.
     *
     * @param  string $text The text to extract from
     * @return string
     */
    protected function extractLoopedTags($text, $data = array(), $callback = null)
    {
        /**
         * $matches[][0] is the raw match
         */
        if (preg_match_all($this->callbackBlockRegex, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                // Does this callback block contain parameters?
                if ($this->parseParameters($match[2], $data, $callback)) {
                    // Let's extract it so it doesn't conflict with local variables when
                    // parseVariables() is called.
                    $text = $this->createExtraction('callback_blocks', $match[0], $match[0], $text);
                } else {
                    $text = $this->createExtraction('looped_tags', $match[0], $match[0], $text);
                }
            }
        }

        return $text;
    }

    /**
     * Extracts text out of the given text and replaces it with a hash which
     * can be used to inject the extractions replacement later.
     *
     * @param  string $type        Type of extraction
     * @param  string $extraction  The text to extract
     * @param  string $replacement Text that will replace the extraction when re-injected
     * @param  string $text        Text to extract out of
     * @return string
     */
    protected function createExtraction($type, $extraction, $replacement, $text)
    {
        $hash = md5($replacement);
        self::$extractions[$type][$hash] = $replacement;

        return str_replace($extraction, "{$type}_{$hash}", $text);
    }

    /**
     * Injects all of the extractions.
     *
     * @param  string $text Text to inject into
     * @return string
     */
    protected function injectExtractions($text, $type = null)
    {
        if ($type === null) {
            foreach (self::$extractions as $type => $extractions) {
                foreach ($extractions as $hash => $replacement) {
                    if (strpos($text, "{$type}_{$hash}") !== false) {
                        $text = str_replace("{$type}_{$hash}", $replacement, $text);
                        unset(self::$extractions[$type][$hash]);
                    }
                }
            }
        } else {
            if ( ! isset(self::$extractions[$type])) {
                return $text;
            }

            foreach (self::$extractions[$type] as $hash => $replacement) {
                if (strpos($text, "{$type}_{$hash}") !== false) {
                    $text = str_replace("{$type}_{$hash}", $replacement, $text);
                    unset(self::$extractions[$type][$hash]);
                }
            }
        }

        return $text;
    }

    /**
     * Takes a dot-notated key and finds the value for it in the given
     * array or object.
     *
     * @param  string       $key     Dot-notated key to find
     * @param  array|object $data    Array or object to search
     * @param  mixed        $default Default value to use if not found
     * @return mixed
     */
    protected function getVariable($key, $data, $default = null)
    {
        $modifiers = null;
        if (strpos($key, "|") === false) {
        } else {
            $parts = explode("|", $key);
            $key = $parts[0];
            $modifiers = array_splice($parts, 1);
        }

        if (strpos($key, $this->scopeGlue) === false) {
            $parts = explode('.', $key);
        } else {
            $parts = explode($this->scopeGlue, $key);
        }
        foreach ($parts as $key_part) {
            if (is_array($data)) {
                if ( ! array_key_exists($key_part, $data)) {
                    return $default;
                }

                $data = $data[$key_part];
            } elseif (is_object($data)) {
                if ( ! isset($data->{$key_part})) {
                    return $default;
                }

                $data = $data->{$key_part};
            } else {
                return $default;
            }
        }

        if ($modifiers) {
            foreach ($modifiers as $mod) {
                if (strpos($mod, ":") === false) {
                    $modifier_name = $mod;
                    $modifier_params = array();
                } else {
                    $parts = explode(":", $mod);
                    $modifier_name = $parts[0];
                    $modifier_params = array_splice($parts, 1);
                }

                if ($modifier_name == 'trim') {
                    $data = trim($data);

                } elseif ($modifier_name == 'img') {
                    $data = '<img src="'.$data.'" />';

                } elseif ($modifier_name == 'link') {
                    if (filter_var($data, FILTER_VALIDATE_EMAIL)) {
                        // email address
                        $data = '<a href="mailto:'.$data.'" />'.$data.'</a>';
                    } else {
                        $data = '<a href="'.$data.'" />'.$data.'</a>';
                    }

                } elseif ($modifier_name == 'upper') {
                    $data = strtoupper($data);

                } else if ($modifier_name == 'lower') {
                    $data = strtolower($data);

                } else if ($modifier_name == 'slugify') {
                    $data = \Slug::make($data);

                } else if ($modifier_name == 'deslugify') {
                    $data = trim(preg_replace('~[-_]~', ' ', $data), " ");

                } else if ($modifier_name == 'title') {
                    $data = ucwords($data);

                } else if ($modifier_name == 'format') {
                    $data = date($modifier_params[0], $data);

                } else if ($modifier_name == 'in_future') {
                    $data = (\Date::resolve($data) > time()) ? "true" : "";

                } else if ($modifier_name == 'in_past') {
                    $data = (\Date::resolve($data) < time()) ? "true" : "";

                } else if ($modifier_name == 'markdown') {
                    $data = Markdown($data);

                } else if ($modifier_name == 'textile') {
                    $textile = new \Textile();
                    $data = $textile->TextileThis($data);

                } else if ($modifier_name == 'length') {
                    if (is_array($data)) {
                        $data = count($data);
                    } else {
                        $data = strlen($data);
                    }

                } else if ($modifier_name == 'scramble') {
                    $data = str_shuffle($data);

                } else if ($modifier_name == 'word_count') {
                    $data = str_word_count($data);

                } else if ($modifier_name == 'rot13') {
                    $data = str_rot13($data);

                } else if ($modifier_name == 'urlencode') {
                    $data = urlencode($data);

                } else if ($modifier_name == 'urldecode') {
                    $data = urldecode($data);

                } else if ($modifier_name == 'striptags') {
                    $data = strip_tags($data);

                } else if ($modifier_name == '%') {
                    $divisor = (isset($modifier_params[0])) ? $modifier_params[0] : 1;
                    $data = $data % $divisor;

                } else if ($modifier_name == 'empty') {
                    $data = (\Helper::isEmptyArray($data)) ? "true" : "";

                } else if ($modifier_name == 'not_empty') {
                    $data = (!\Helper::isEmptyArray($data)) ? "true" : "";

                } else if ($modifier_name == 'repeat') {
                    $multiplier = (isset($modifier_params[0])) ? $modifier_params[0] : 1;
                    $data = str_repeat($data, $multiplier);

                } else if ($modifier_name == 'reverse') {
                    $data = strrev($data);

                } else if ($modifier_name == 'round') {
                    $precision = (isset($modifier_params[0])) ? (int) $modifier_params[0] : 0;
                    $data = round((float) $data, $precision);

                } else if ($modifier_name == 'floor') {
                    $data = floor((float) $data);

                } else if ($modifier_name == 'ceil') {
                    $data = ceil((float) $data);

                } else if ($modifier_name == 'distance_in_mi_from') {
                    if (!isset($modifier_params[0])) {
                        return 'Unknown';
                    }

                    if (!preg_match(\Pattern::COORDINATES, $data, $point_1_matches)) {
                        return 'Unknown';
                    }

                    if (!preg_match(\Pattern::COORDINATES, $modifier_params[0], $point_2_matches)) {
                        return 'Unknown';
                    }

                    $point_1 = array($point_1_matches[1], $point_1_matches[2]);
                    $point_2 = array($point_2_matches[1], $point_2_matches[2]);

                    $distance = \Math::getDistanceInKilometers($point_1, $point_2);
                    $data = \Math::convertKilometersToMiles($distance);

                } else if ($modifier_name == 'distance_in_km_from') {
                    if (!isset($modifier_params[0])) {
                        return 'Unknown';
                    }

                    if (!preg_match(\Pattern::COORDINATES, $data, $point_1_matches)) {
                        return 'Unknown';
                    }

                    if (!preg_match(\Pattern::COORDINATES, $modifier_params[0], $point_2_matches)) {
                        return 'Unknown';
                    }

                    $point_1 = array($point_1_matches[1], $point_1_matches[2]);
                    $point_2 = array($point_2_matches[1], $point_2_matches[2]);

                    $data = \Math::getDistanceInKilometers($point_1, $point_2);

                } else if ($modifier_name == 'smartypants') {
                    $data = SmartyPants($data);

                } else if ($modifier_name == 'widont') {
                    // thanks to Shaun Inman for inspriation here
                    // http://www.shauninman.com/archive/2008/08/25/widont_2_1_1

                    // if there are content tags
                    if (preg_match("/<\/(?:p|li|h1|h2|h3|h4|h5|h6|figcaption)>/ism", $data)) {
                        $data = preg_replace("/(?<!<[p|li|h1|h2|h3|h4|h5|h6|div|figcaption])([^\s])[ \t]+([^\s]+(?:<\/(?:p|li|h1|h2|h3|h4|h5|h6|div|figcaption)>))$/im", "$1&nbsp;$2", rtrim($data));

                    // otherwise
                    } else {
                        $data = preg_replace("/([^\s])\s+([^\s]+)\s*$/im", "$1&nbsp;$2", rtrim($data));
                    }

                } elseif ($modifier_name == 'backspace') {
                    if (!is_array($data) && isset($modifier_params[0]) && $modifier_params[0] > 0) {
                        $data = substr($data, 0, -$modifier_params[0]);
                    }

                } else if ($modifier_name == 'truncate') {
                    $length = 30;
                    $hellip ="&hellip;";
                    if (sizeof($modifier_params) > 0) {
                        $length = (int) $modifier_params[0];
                    } else if (isset($modifier_params[1])) {
                        $hellip = (int) $modifier_params[1];
                    }
                    if (strlen($data) > $length) {
                        $data = substr($data, 0, $length).$hellip;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Evaluates the PHP in the given string.
     *
     * @param  string $text Text to evaluate
     * @return string
     */
    protected function parsePhp($text)
    {
        ob_start();
        $result = eval('?>'.$text.'<?php ');

        if ($result === false) {
            $output = 'You have a syntax error in your Lex tags. The offending code: ';
            throw new ParsingException($output.str_replace(array('?>', '<?php '), '', $text));
        }

        return ob_get_clean();
    }


    /**
     * Parses a parameter string into an array
     *
     * @param   string  The string of parameters
     * @return array
     */
    protected function parseParameters($parameters, $data, $callback)
    {
        $this->conditionalData = $data;
        $this->inCondition = true;
        // Extract all literal string in the conditional to make it easier
        if (preg_match_all('/(["\']).*?(?<!\\\\)\1/', $parameters, $str_matches)) {
            foreach ($str_matches[0] as $m) {
                $parameters = $this->createExtraction('__param_str', $m, $m, $parameters);
            }
        }

        $parameters = preg_replace_callback(
            '/(.*?\s*=\s*(?!__))('.$this->variableRegex.')/is',
            array($this, 'processParamVar'),
            $parameters
        );
        if ($callback) {
            $parameters = preg_replace('/(.*?\s*=\s*(?!\{\s*)(?!__))('.$this->callbackNameRegex.')(?!\s*\})\b/', '$1{$2}', $parameters);
            $parameters = $this->parseCallbackTags($parameters, $data, $callback);
        }

        // Re-inject any strings we extracted
        $parameters = $this->injectExtractions($parameters, '__param_str');
        $this->inCondition = false;

        if (preg_match_all('/(.*?)\s*=\s*(\'|"|&#?\w+;)(.*?)(?<!\\\\)\2/s', trim($parameters), $matches)) {
            $return = array();
            foreach ($matches[1] as $i => $attr) {
                $return[trim($matches[1][$i])] = stripslashes($matches[3][$i]);
            }

            return $return;
        }

        return array();
    }
}