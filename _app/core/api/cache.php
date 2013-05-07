<?php
/**
 * Cache
 * API for caching content
 *
 * @author      Jack McDade
 * @author      Mubashar Iqbal
 * @author      Fred LeBlanc
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
use Symfony\Component\Finder\Finder as Finder;

class Cache
{
    /**
     * Updates the internal content cache
     *
     * @return boolean
     */
    public static function update()
    {
        // track if any files have changed
        $files_changed = false;

        // grab length of content type extension
        $content_type        = Config::getContentType();
        $full_content_root   = rtrim(Path::tidy(BASE_PATH . "/" . Config::getContentRoot()), "/");
        $content_type_length = strlen($content_type) + 1;

        // the cache file we'll use
        $cache_file = BASE_PATH . "/_cache/_app/content/content.php";
        $time_file  = BASE_PATH . "/_cache/_app/content/last.php";
        $now        = time();

        // grab the existing cache
        $cache = unserialize(File::get($cache_file));
        if (!is_array($cache)) {
            $cache = array(
                "urls" => array(),
                "content" => array(),
                "taxonomies" => array()
            );
        }
        $last = File::get($time_file);

        // grab a list of all files
        $finder = new Finder();
        $files = $finder
            ->files()
            ->name("*." . Config::getContentType())
            ->in(Config::getContentRoot());
        
        // grab a separate list of files that have changed since last check
        $updated_files = clone $files;
        $updated = array();
        
        if ($last) {
            $updated_files->date(">= " . Date::format("Y-m-d H:i:s", $last));

            foreach ($updated_files as $file) {
                // we don't want directories, they may show up as being modified
                // if a file inside them has changed or been renamed
                if (is_dir($file)) {
                    continue;
                }
                
                // this isn't a directory, add it to the list
                $updated[] = Path::trimFilesystem($file->getRealPath());
            }
        }
        
        // loop over current files
        $current_files = array();
        foreach ($files as $file) {
            $current_files[] = Path::trimFilesystem($file->getRealPath());
        }

        // get a diff of files we know about and files currently existing
        $new_files = array_diff($current_files, $cache['urls']);
        
        // create a master list of files that need updating
        $changed_files = array_unique(array_merge($new_files, $updated));

        // add to the cache if files have been updated
        if (count($changed_files)) {
            $files_changed = true;

            // build content cache
            foreach ($changed_files as $file) {
                $file           = $full_content_root . $file;
                $local_path     = Path::trimFilesystem($file);
                $local_filename = Path::clean($local_path);

                // file parsing
                $content = substr(File::get($file), 3);
                $divide = strpos($content, "\n---");
                $front_matter = trim(substr($content, 0, $divide));

                // parse data
                $data                   = YAML::parse($front_matter);

                // set additional information
                $data['_file']          = $file;
                $data['_filemtime']     = filemtime($file);
                $data['_local_path']    = $local_path;

                $data['_order_key']     = null;
                $data['datestamp']      = null;
                $data['date']           = null;

                $data['_is_hidden']     = false;
                $data['_is_draft']      = false;

                // folder
                $data['_folder'] = preg_replace(Pattern::ORDER_KEY, "", str_replace($full_content_root, "", $data['_file']));
                $slash = strrpos($data['_folder'], "/");
                $data['_folder'] = ($slash === 0) ? "" : substr($data['_folder'], 1, $slash - 1);

                // fix hidden/draft files
                $slug = basename($file, "." . $content_type);
                if (substr($slug, 0, 2) === "__") {
                    $data['_is_hidden'] = true;
                    $data['_slug'] = substr($slug, 2);
                } elseif (substr($slug, 0, 1) === "_") {
                    $data['_is_draft'] = true;
                    $data['_slug'] = substr($slug, 1);
                } else {
                    $data['_slug'] = $slug;
                }

                $data['_basename'] = $data['_slug'] . "." . $content_type;
                $data['_filename'] = $data['_slug'];
                $data['_is_entry'] = preg_match(Pattern::ENTRY_FILEPATH, $data['_basename']);
                $data['_is_page']  = preg_match(Pattern::PAGE_FILEPATH,  $data['_basename']);

                // order key
                if (preg_match(Pattern::DATE_OR_DATETIME, $data['_basename'], $matches)) {
                    $date = $matches[1] . '-' . $matches[2] . '-' . $matches[3];

                    if (isset($matches[4])) {
                        $time = substr($matches[4], 0, 2) . ":" . substr($matches[4], 2);
                        $date = $date . " " . $time;

                        $data['_slug'] = substr($data['_slug'], 16);
                    } else {
                        $data['_slug'] = substr($data['_slug'], 11);
                    }

                    $data['_order_key'] = strtotime($date);
                    $data['datestamp']  = $data['_order_key'];
                    $data['date']       = $data['_order_key'];

                } elseif (preg_match(Pattern::NUMERIC, $data['_basename'], $matches)) {
                    $data['_order_key'] = $matches[1];
                    $data['_slug'] = substr($data['_slug'], strlen($matches[1]) + 1);

                } else {
                    $data['_order_key'] = $data['_basename'];
                }

                // determine url
                $data['url'] = preg_replace("/\/__?/", "/", $local_filename);

                // remove any content type extensions from the end of filename
                if (substr($data['url'], -$content_type_length) === "." . $content_type) {
                    $data['url'] = substr($data['url'], 0, strlen($data['url']) - $content_type_length);
                }

                // remove any base pages from filename
                if (substr($data['url'], -5) == "/page") {
                    $data['url'] = substr($data['url'], 0, strlen($data['url']) - 5);
                }

                $data['url'] = Path::tidy("/" . $data['url']);

                // add to cache file
                $cache['content'][$local_path] = $data;
                $cache['urls'][$data['url']] = $local_path;
            }
        }


        // loop through all cached content for deleted files
        // this isn't as expensive as you'd think in real-world situations
        foreach ($cache['content'] as $local_path => $data) {
            if (File::exists($full_content_root . $local_path)) {
                continue;
            }

            $files_changed = true;

            // remove from content cache
            unset($cache['content'][$local_path]);

            // remove from url cache
            $url = array_search($local_path, $cache['urls']);
            if ($url !== false) {
                unset($cache['urls'][$url]);
            }
        }


        // build taxonomy cache
        // only happens if files were added, updated, or deleted above
        if ($files_changed) {
            $taxonomies           = Config::getTaxonomies();
            $force_lowercase      = Config::getTaxonomyForceLowercase();
            $case_sensitive       = Config::getTaxonomyCaseSensitive();
            $cache['taxonomies']  = array();

            if (count($taxonomies)) {
                // set up taxonomy array
                foreach ($taxonomies as $taxonomy) {
                    $cache['taxonomies'][$taxonomy] = array();
                }

                // loop through content to build cached array
                foreach ($cache['content'] as $file => $data) {

                    // do not grab anything not public
                    if (array_get($data, '_is_hidden', false) || array_get($data, '_is_draft', false)) {
                        continue;
                    }

                    // loop through the types of taxonomies
                    foreach ($taxonomies as $taxonomy) {

                        // if this file contains this type of taxonomy
                        if (isset($data[$taxonomy])) {
                            $values = Helper::ensureArray($data[$taxonomy]);

                            // add the file name to the list of found files for a given taxonomy value
                            foreach ($values as $value) {
                                if (!$value) {
                                    continue;
                                }

                                $key = (!$case_sensitive) ? strtolower($value) : $value;

                                if (!isset($cache['taxonomies'][$taxonomy][$key])) {
                                    $cache['taxonomies'][$taxonomy][$key] = array(
                                        "name" => ($force_lowercase) ? strtolower($value) : $value,
                                        "files" => array()
                                    );
                                }

                                array_push($cache['taxonomies'][$taxonomy][$key]['files'], $data['url']);
                            }
                        }
                    }
                }
            }

            if (File::put($cache_file, serialize($cache)) === false) {
                if (!File::isWritable($cache_file)) {
                    Log::fatal("Cache folder is not writable.", "core", "content-cache");
                }

                Log::fatal("Could not write to the cache.", "core", "content-cache");
                return false;
            }
        }

        File::put($time_file, $now);
        return true;
    }
}