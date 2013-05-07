<?php
/**
 * Path
 * API for manipulating and working with paths
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
class Path
{
    /**
     * Finds a given path on the server, adding in any ordering elements missing
     *
     * @param string  $path  Path to resolve
     * @return string
     */
    public static function resolve($path)
    {
        $content_root = Config::getContentRoot();
        $content_type = Config::getContentType();

        if (strpos($path, "/") === 0) {
            $parts = explode("/", substr($path, 1));
        } else {
            $parts = explode("/", $path);
        }

        $fixedpath = "/";
        foreach ($parts as $part) {
            if (! File::exists(URL::assemble($content_root,$path . '.' . $content_type))
                && ! is_dir(URL::assemble($content_root, $part))) {

                // check folders
                $list = Statamic::get_content_tree($fixedpath, 1, 1, FALSE, TRUE, FALSE);
                foreach ($list as $item) {
                    $t = basename($item['slug']);
                    if (Slug::isNumeric($t)) {
                        $nl = strlen(Slug::getOrderNumber($t)) + 1;
                        if (strlen($part) >= (strlen($item['slug']) - $nl) && Pattern::endsWith($item['slug'], $part)) {
                            $part = $item['slug'];
                            break;
                        }
                    } else {
                        if (Pattern::endsWith($item['slug'], $part)) {
                            if (strlen($part) >= strlen($t)) {
                                $part = $item['slug'];
                                break;
                            }
                        }
                    }
                }

                // check files

                $list = Statamic::get_file_list($fixedpath);

                foreach ($list as $key => $item) {
                    if (Pattern::endsWith($key, $part)) {
                        $t = basename($item);

                        $offset = 0;
                        if (Pattern::startsWith($key, '__')) {
                            $offset = 2;
                        } elseif (Pattern::startsWith($key, '_')) {
                            $offset = 1;
                        }

                        if (Config::getEntryTimestamps() && Slug::isDateTime($t)) {
                            if (strlen($part) >= (strlen($key) - 16 - $offset)) {
                                $part = $key;
                                break;
                            }
                        } elseif (Slug::isDate($t)) {
                            if (strlen($part) >= (strlen($key) - 12 - $offset)) {
                                $part = $key;
                                break;
                            }
                        } elseif (Slug::isNumeric($t)) {
                            $nl = strlen(Slug::getOrderNumber($key)) + 1;
                            if (strlen($part) >= (strlen($key) - $nl - $offset)) {
                                $part = $key;
                                break;
                            }
                        } else {
                            $t = basename($item);
                            if (strlen($part) >= strlen($t) - $offset) {
                                $part = $key;
                                break;
                            }
                        }
                    }
                }
            }

            if ($fixedpath != '/') {
                $fixedpath .= '/';
            }

            $fixedpath .= $part;
        }

        // /2-blog/hidden

        return $fixedpath;
    }


    /**
     * Removes occurrences of "//" in a $path (except when part of a protocol)
     *
     * @param string  $path  Path to remove "//" from
     * @return string
     */
    public static function tidy($path)
    {
        return preg_replace("#(^|[^:])//+#", "\\1/", $path);
    }


    /**
     * Trim slashes from either end of a given $path
     *
     * @param string  $path  Path to trim slashes from
     * @return string
     */
    public static function trimSlashes($path)
    {
        return trim($path, '/');
    }


    /**
     * Cleans up a given $path, removing any order keys (date-based or number-based)
     *
     * @param string  $path  Path to clean
     * @return string
     */
    public static function clean($path)
    {
        return preg_replace(Pattern::ORDER_KEY, "", $path);
    }


    /**
     * Checks if a given path is non-public content
     * 
     * @param string  $path  Path to check
     * @return boolean
     */
    public static function isNonPublic($path)
    {
        if (substr($path, 0, 1) !== "/") {
            $path = "/" . $path;
        }

        return (strpos($path, "/_") !== false);
    }


    /**
     * Removes any filesystem path outside of the site root
     * 
     * @param string  $path  Path to trim
     * @return string
     */
    public static function trimFilesystem($path)
    {
        return str_replace(BASE_PATH . "/" . Config::getContentRoot(), "", $path);
    }
}