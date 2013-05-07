<?php

class Localization
{
    /**
     * Fetch an L10n content string
     *
     * @param $key      string  YAML key of the desired text string
     * @param $language string  Optionally override the desired language
     * @return mixed
     **/
    public static function fetch($key, $language = false)
    {
        $app = \Slim\Slim::getInstance();

        $language = $language ? $language : Config::getCurrentLanguage();

        /*
        |--------------------------------------------------------------------------
        | Check for new language
        |--------------------------------------------------------------------------
        |
        | English is loaded by default. If requesting a language not already
        | cached, go grab it.
        |
        */

        if ( ! isset($app->config['_translations'][$language])) {
            $app->config['_translations'][$language] = YAML::parse(Config::getTranslation($language));
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve translation
        |--------------------------------------------------------------------------
        |
        | If the set language is found and the key exists, return it. Falls back to
        | English, and then falls back to the slug-style key itself.
        |
        */

        if (array_get($app->config['_translations'][$language]['translations'], $key, false)) {
            return array_get($app->config['_translations'][$language]['translations'], $key);
        }

        return array_get($app->config['_translations']['en']['translations'], $key, $key);
    }
}
