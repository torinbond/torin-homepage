<?php
/**
 * Hook
 * API for hooking into events triggered by the site
 *
 * @author      Jack McDade
 * @author      Mubashar Iqbal
 * @author      Fred LeBlanc
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */

use Symfony\Component\Finder\Finder as Finder;

class Hook
{
    /**
     * Run the instance of a given hook
     *
     * @param string  $namespace  The namespace (addon/aspect) calling the hook
     * @param string  $hook       Name of hook
     * @param string  $type       Cumulative/replace/call
     * @param mixed   $return     Pass-through values
     * @param mixed   $data       Data to pass to hooked method
     * @return mixed
     **/
    public static function run($namespace, $hook, $type = NULL, $return = NULL, $data = NULL)
    {

        if (Folder::exists(Config::getAddOnsPath()) && Folder::exists(APP_PATH . '/core/tags')) {

            $finder = new Finder();

            $files = $finder->files()
                ->in(Config::getAddOnsPath())
                ->in(APP_PATH . '/core/tags')
                ->name("hooks.*.php");

            foreach ($files as $file) {

                require_once $file->getRealPath();

                $class_name = 'Hooks_' . $file->getRelativePath();
                $hook_class = new $class_name();

                $method = $namespace . '__' . $hook;

                if ( ! method_exists($hook_class, $method)) {
                    continue;
                }

                if ($type == 'cumulative') {
                    $response = $hook_class->$method($data);
                    if (is_array($response)) {
                        $return = is_array($return) ? $return + $response : $response;
                    } else {
                        $return .= $response;
                    }
                } elseif ($type == 'replace') {
                    $return = $hook_class->$method($data);
                } else {
                    $hook_class->$method($data);
                }
            }
        } else {
            Log::error('Add-ons path not found', 'hooks');
        }

        return $return;
    }
}
