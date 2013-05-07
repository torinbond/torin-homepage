<?php
/**
 * Config
 * API for interacting with the site's configuration
 *
 * @author      Jack McDade
 * @author      Mubashar Iqbal
 * @author      Fred LeBlanc
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */

class Config
{
    /**
     * Get a setting, check for a _prefixed fallback,
     * with an optional default
     *
     * @param string  $setting  Key to retrieve
     * @param mixed  $default  Default value to return if no key is found
     * @return mixed
     **/
    public static function get($setting, $default = false)
    {
        $app = \Slim\Slim::getInstance();

        if (isset($app->config[$setting])) {
            return $app->config[$setting];

        } elseif (isset($app->config['_' . $setting])) {
            return $app->config['_' . $setting];
        }

        return $default;
    }


    /**
     * Returns the root directory of the site
     *
     * @return string
     **/
    public static function getSiteRoot()
    {
        return self::get('site_root', '/');
    }


    /**
     * Returns the path to the admin directory
     *
     * @return string
     **/
    public static function getAdminPath()
    {
        return Path::tidy(ltrim(self::get('admin_path', 'admin/'), '/').'/');
    }


    /**
     * Returns the path to the fieldtypes directory
     *
     * @return string
     */
    public static function getFieldtypesPath()
    {
        return self::getAdminPath() . 'fieldtypes';
    }


    /**
     * Returns the path to the add-ons directory
     *
     * @return string
     */
    public static function getAddOnsPath()
    {
        return self::get('addons_path', '_add-ons');
    }


    /**
     * Returns the path for a given $addon
     *
     * @param string  $addon  Add-on to use
     * @return string
     */
    public static function getAddOnPath($addon)
    {
        return URL::assemble(self::getAddOnsPath(), $addon);
    }


    /**
     * Gets the Config folder path
     *
     * @return string
     */
    public static function getConfigPath()
    {
        return BASE_PATH . '/_config';
    }


    /**
     * Gets the Config folder path
     *
     * @return string
     */
    public static function getAppConfigPath()
    {
        return APP_PATH . '/config';
    }


    /**
     * Gets the Site URL
     *
     * @return string
     */
    public static function getSiteURL()
    {
        return self::get("site_url", "");
    }


    /**
     * Gets the Site Name
     *
     * @return string
     */
    public static function getSiteName()
    {
        return self::get("site_name", "");
    }


    /**
     * Gets the License Key
     *
     * @return string
     */
    public static function getLicenseKey()
    {
        return self::get("license_key", "");
    }


    /**
     * Gets the Theme
     *
     * @return string
     */
    public static function getTheme()
    {
        return self::get("theme", "denali");
    }


    /**
     * Gets the Themes Path
     *
     * @return string
     */
    public static function getThemesPath()
    {
        return self::get("themes_path", "_themes");
    }


    /**
     * Gets the Theme Assets Path
     *
     * @return string
     */
    public static function getThemeAssetsPath()
    {
        return self::get("theme_assets_path", "");
    }


    /**
     * Gets the Theme Path
     *
     * @return string
     */
    public static function getCurrentThemePath()
    {
        return self::get("theme_path", self::getThemesPath() . '/' . self::getTheme());
    }


    /**
     * Gets the Content Root
     *
     * @return string
     */
    public static function getContentRoot()
    {
        return self::get("content_root", "_content");
    }


    /**
     * Gets the current site language
     *
     * @return string
     */
    public static function getCurrentLanguage()
    {
        return self::get("language", "en");
    }


    /**
     * Gets the path to the translations folder
     *
     * @return string
     */
    public static function getTranslationsPath()
    {
        return self::getConfigPath() . '/translations';
    }


    /**
     * Gets the path to a translation file
     *
     * @param string  $language  Language to translate to
     * @return string
     */
    public static function getTranslation($language)
    {
        return self::getTranslationsPath() . '/' . $language . '.yaml';
    }


    /**
     * Gets the Content Type
     *
     * @return string
     */
    public static function getContentType()
    {
        $type = self::get("content_type", "md");
        return (in_array(strtolower($type), array("markdown_edge", "markdown"))) ? "md" : $type;
    }


    /**
     * Gets the Date Format
     *
     * @return string
     */
    public static function getDateFormat()
    {
        return self::get("date_format", "Y-m-d");
    }


    /**
     * Gets the Time Format
     *
     * @return string
     */
    public static function getTimeFormat()
    {
        return self::get("time_format", "h:ia");
    }


    /**
     * Gets the Entry Timestamps setting
     *
     * @return string
     */
    public static function getEntryTimestamps()
    {
        return (bool) self::get("entry_timestamps", false);
    }


    /**
     * Gets the configured parse order
     *
     * @return array
     */
    public static function getParseOrder()
    {
        return self::get("parse_order", array('tags', 'content'));
    }


    /**
     * Gets the current templates path
     *
     * @return string
     */
    public static function getTemplatesPath()
    {
        return self::get("templates.path", "./_themes/".Config::getTheme());
    }


    /**
     * Gets the pagination variable
     *
     * @return string
     */
    public static function getPaginationVariable()
    {
        return self::get("pagination_variable", "page");
    }


    /**
     * Gets the pagination style
     *
     * @return string
     */
    public static function getPaginationStyle()
    {
        return self::get("pagination_style", "prev_next");
    }


    /**
     * Gets the taxonomies
     *
     * @return array
     */
    public static function getTaxonomies()
    {
        $taxonomies = self::get("taxonomy", array());

        if ( ! is_array($taxonomies)) {
            $taxonomies = array($taxonomies);
        }

        return $taxonomies;
    }


    /**
     * Are taxonomies slugified?
     *
     * @return boolean
     */
    public static function getTaxonomySlugify()
    {
        return self::get("taxonomy_slugify", false);
    }


    /**
     * Are taxonomies case-sensitive?
     *
     * @return boolean
     */
    public static function getTaxonomyCaseSensitive()
    {
        return self::get("taxonomy_case_sensitive", false);
    }


    /**
     * Force taxonomies to display lowercase?
     *
     * @return boolean
     */
    public static function getTaxonomyForceLowercase()
    {
        return self::get("taxonomy_force_lowercase", true);
    }


    /**
     * Is the task ticker running?
     *
     * @return boolean
     */
    public static function areTasksRunning()
    {
        $ticks_path = BASE_PATH . "/_cache/_add-ons/tasks/ticks.yaml";

        // check that the file exists
        if (!File::exists($ticks_path)) {
            return false;
        }

        // parse the ticks file
        $ticks = Yaml::parse($ticks_path);

        // check that last-tick exists
        if (!is_array($ticks) || !isset($ticks['last-tick'])) {
            return false;
        }

        // return whether the last tick was less than 5 minutes ago
        return (time() - $ticks['last-tick'] < 60);
    }


    /**
     * Should Statamic fix out-of-range pagination?
     *
     * @return boolean
     */
    public static function getFixOutOfRangePagination()
    {
        return self::get("fix_out_of_range_pagination", true);
    }
}