<?php
use Symfony\Component\Finder\Finder as Finder;
/**
 * Content
 * API for interacting with content within the site
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
class Content
{
    /**
     * Checks to see if a given $slug (and optionally $folder) exist
     *
     * @param string  $slug  Slug to check
     * @param mixed  $folder  Folder to look inside
     * @return bool
     */
    public static function exists($slug, $folder=NULL)
    {
        $folder        = (is_null($folder)) ? '' : $folder;
        $content_path  = Config::getContentRoot() . "/{$folder}";
        $content_type  = Config::getContentType();

        return file_exists("{$content_path}/{$slug}.{$content_type}");
    }


    /**
     * Parses given $template_data with $data, converts content to $type
     *
     * @param string  $template_data  Template data to parse
     * @param array  $data  List of variables to fill in
     * @param mixed  $type  Optional content type to render
     * @return string
     */
    public static function parse($template_data, $data, $type=NULL)
    {
        $app   = \Slim\Slim::getInstance();
        $data  = array_merge($data, $app->config);

        $parser = new Lex\Parser();
        $parser->cumulativeNoparse(TRUE);

        $parse_order = Config::getParseOrder();

        if ($parse_order[0] == 'tags') {
            $output = $parser->parse($template_data, $data);
            $output = self::transform($output, $type);
        } else {
            $output = self::transform($template_data, $type);
            $output = $parser->parse($output, $data);
        }

        return $output;
    }


    /**
     * Render content via a given $content_type
     *
     * @param string  $content  Content to render
     * @param mixed  $content_type  Content type to use (overrides configured content_type)
     * @return string
     */
    public static function transform($content, $content_type=NULL) {
        $content_type = Helper::pick($content_type, Config::getContentType());

        // render HTML from the given $content_type
        switch (strtolower($content_type)) {
            case "markdown":
            case "md":
                $content = Markdown($content);
                break;

            case "text":
            case "txt":
                $content = nl2br(strip_tags($content));
                break;

            case "textile":
                $textile = new Textile();
                $content = $textile->TextileThis($content);
        }

        if (Config::get('_enable_smartypants', TRUE) == TRUE) {
            $content = SmartyPants($content);
        }

        return trim($content);
    }


    /**
     * Fetch a single content entry or page
     *
     * @param string  $url  URL to fetch
     * @return array
     */
    public static function get($url)
    {
        $content_set = ContentService::getContentByURL($url);
        $content = $content_set->get();

        return (isset($content[0])) ? $content[0] : array();
    }

}