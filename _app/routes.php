<?php

/////////////////////////////////////////////////////////////////////////////////////////////////
// ROUTING HOOKS
/////////////////////////////////////////////////////////////////////////////////////////////////

$app->map('/TRIGGER/:namespace/:hook', function ($namespace, $hook) use ($app) {

    Hook::run($namespace, $hook);

})->via('GET', 'POST', 'HEAD');


/////////////////////////////////////////////////////////////////////////////////////////////////
// Static Asset Pipeline
/////////////////////////////////////////////////////////////////////////////////////////////////

$app->get('/assets/(:segments+)', function($segments = array()) use ($app) {

  $file_requested = implode($segments, '/');
  $file = Theme::getPath() . $file_requested;

  # Routes only if the file doesn't already exist (e.g. /assets/whatever.ext)
  if ( ! File::exists($file_requested) && File::exists($file)) {
    $mime = File::resolveMime($file);

    header("Content-type: {$mime}");
    readfile($file);

    exit();
  }

});


/////////////////////////////////////////////////////////////////////////////////////////////////
// GLOBAL STATAMIC CONTENT ROUTING
/////////////////////////////////////////////////////////////////////////////////////////////////

$app->map('/(:segments+)', function ($segments = array()) use ($app) {

    // segments
    foreach ($segments as $key => $seg) {
        $count                            = $key + 1;
        $app->config['segment_' . $count] = $seg;
    }
    $app->config['last_segment'] = end($segments);

    // ignore segments via routes.yaml
    if (isset($app->config['_routes']['ignore']) && is_array($app->config['_routes']['ignore']) && count($app->config['_routes']['ignore']) > 0) {
        $ignore = $app->config['_routes']['ignore'];

        $remove_segments = array_intersect($ignore, $segments);
        $segments        = array_diff($segments, $remove_segments);
    }

    // determine paths
    $path        = '/' . implode($segments, '/');
    $current_url = $path;

    // allow mod_rewrite for .html file extensions
    if (substr($path, -5) == '.html') {
        $path = str_replace('.html', '', $path);
    }

    $app->config['current_path'] = $path;

    // init some variables for below
    $content_root  = Config::getContentRoot();
    $content_type  = Config::getContentType();
    $response_code = 200;
    $visible       = true;
    $add_prev_next = false;

    $template_list = array('default');

    // set up the app based on if a
    if (File::exists("{$content_root}/{$path}.{$content_type}") || Folder::exists("{$content_root}/{$path}")) {
        // endpoint or folder exists!
    } else {
        $path                        = Path::resolve($path);
        $app->config['current_url']  = $app->config['current_path'];
        $app->config['current_path'] = $path; # override global current_path
    }

    // routes via routes.yaml
    if (isset($app->config['_routes']['routes'][$current_url]) || isset($app->config['_routes'][$current_url])) {

        # allows the route file to run without "route:" as the top level array key (backwards compatibility)
        $current_route = isset($app->config['_routes']['routes'][$current_url]) ? $app->config['_routes']['routes'][$current_url] : $app->config['_routes'][$current_url];

        $route    = $current_route;
        $template = $route;
        $data     = array();

        if (is_array($route)) {
            $template = isset($route['template']) ? $route['template'] : 'default';

            if (isset($route['layout'])) {
                $data['_layout'] = $route['layout'];
            }
        }

        $template_list = array($template);

    // actual file exists
    } elseif (File::exists("{$content_root}/{$path}.{$content_type}")) {
        $add_prev_next   = true;
        $template_list[] = 'post';
        $page     = basename($path);
        $folder   = substr($path, 0, (-1*strlen($page))-1);

        $data     = Content::get($current_url);

        $data['current_url'] = $current_url;
        $data['slug']        = basename($current_url);

    // url is taxonomy-based
    } elseif (Taxonomy::isTaxonomyURL($path)) {
        list($type, $slug) = Taxonomy::getCriteria($path);

        $data                  = Statamic::get_content_meta($type, Statamic::remove_taxonomy_from_path($path, $type, $slug));
        $data['taxonomy_slug'] = urldecode($slug);
        $data['taxonomy_name'] = Taxonomy::getTaxonomyName($type, $slug);

        $template_list[] = "taxonomies";
        $template_list[] = $type;

    // this is a directory,so we look for page.md
    } elseif (is_dir("{$content_root}/{$path}")) {
        $data = Content::get($current_url);

    // Not found. 404 O'Clock.
    } else {
        // determine where user came from for log message
        if (strstr($path, 'favicon.ico')) {
            // Favicons are annoying.
            Log::info("The site favicon could not be found.", "site", "favicon");
        } else {
            if (isset($_SERVER['HTTP_REFERER'])) {
                $url_parts = parse_url($_SERVER['HTTP_REFERER']);

                // get local referrer
                $local_referrer = $url_parts['path'];
                $local_referrer .= (isset($url_parts['query']) && $url_parts['query']) ? '?' . $url_parts['query'] : '';
                $local_referrer .= (isset($url_parts['fragment']) && $url_parts['fragment']) ? '#' . $url_parts['fragment'] : '';

                if (strstr($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
                    // the call came from inside the house!
                    $more   = 'There is a bad link on <a href="' . $local_referrer . '">' . $local_referrer . '</a>.';
                    $aspect = 'page';
                } else {
                    // external site linked to here
                    $more   = 'User clicked an outside bad link at <a href="' . $_SERVER['HTTP_REFERER'] . '">' . $_SERVER['HTTP_REFERER'] . '</a>.';
                    $aspect = 'external';
                }
            } else {
                // user typing error
                $more   = 'Visitor came directly to this page and may have typed the URL incorrectly.';
                $aspect = 'visitor';
            }

            Log::error("404 - Page not found. " . $more, $aspect, "content");
        }

        $data          = Statamic::get_content_meta("404", "/");
        $template_list = array('404');
        $response_code = 404;
    }

    # We now have all the YAML content
    # Let's process action fields

    # Redirect
    if (isset($data['_redirect'])) {
        $response = 302;

        if (is_array($data['_redirect'])) {
            $url = isset($data['_redirect']['to']) ? $data['_redirect']['to'] : false;

            if (!$url) {
                $url = isset($data['_redirect']['url']) ? $data['_redirect']['url'] : false; #support url key as alt
            }

            $response = isset($data['_redirect']['response']) ? $data['_redirect']['response'] : $response;
        } else {
            $url = $data['_redirect'];
        }

        if ($url) {
            $app->redirect($url, $response);
        }
    }

    // status
    if (preg_match("/\/_[^_]/", $path) && !$app->config['logged_in']) {
        $data          = Statamic::get_content_meta("404", "/");
        $template_list = array('404');
        $visible       = false;
        $response_code = 404;

    // legacy status
    } elseif (isset($data['status']) && $data['status'] != 'live' && $data['status'] != 'hidden' && !$app->config['logged_in']) {
        $data          = Statamic::get_content_meta("404", "/");
        $template_list = array('404');
        $visible       = false;
        $response_code = 404;
    }

    // find next/previous
    if ($add_prev_next && $visible) {
        $folder = substr(preg_replace(Pattern::ORDER_KEY, "", substr($path, 0, (-1*strlen($page))-1)), 1);

        $relative     = Statamic::find_relative($current_url, $folder);
        $data['prev'] = $relative['prev'];
        $data['next'] = $relative['next'];
    }

    // grab data for this folder
    $folder_data = Statamic::get_content_meta("page", dirname($path));

    // set defaults for template and layout if needed
    if (empty($data['_template']) && !empty($folder_data['_default_folder_template'])) {
        $data['_template'] = $folder_data['_default_folder_template'];
    }

    if (empty($data['_layout']) && !empty($folder_data['_default_folder_layout'])) {
        $data['_layout'] = $folder_data['_default_folder_layout'];
    }

    // set template and layout
    if (isset($data['_template'])) {
        $template_list[] = $data['_template'];
    }

    if (isset($data['_layout'])) {
        Statamic_View::set_layout("layouts/{$data['_layout']}");
    }

    // set up the view
    Statamic_View::set_templates(array_reverse($template_list));

    // set type, allows for RSS feeds
    if (isset($data['_type'])) {
        if ($data['_type'] == 'rss') {
            $data['_xml_header']      = '<?xml version="1.0" encoding="utf-8"?>';
            $response                 = $app->response();
            $response['Content-Type'] = 'application/xml';
        }
    }

    // and go!
    $app->render(null, $data, $response_code);
    $app->halt($response_code, ob_get_clean());

})->via('GET', 'POST', 'HEAD');
