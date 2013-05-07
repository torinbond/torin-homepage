<?php

/**
 * The Routes
 **/

function authenticateForRole($role = 'member')
{
    $admin_app = \Slim\Slim::getInstance();
    $user = Statamic_Auth::get_current_user();
    if ($user) {
      if ($user->has_role($role) === false) {
        $admin_app->redirect($admin_app->urlFor('denied'));
      }
    } else {
      $admin_app->redirect($admin_app->urlFor('login'));
    }

    return true;
}

function isCurlEnabled()
{
  return function_exists('curl_version') ? true : false;
}

function doStatamicVersionCheck($app)
{
  // default values
  $app->config['latest_version_url'] = '';
  $app->config['latest_version'] = '';

  if (isCurlEnabled()) {
    $cookie = $app->getEncryptedCookie('stat_latest_version');
    if (!$cookie) {
      $license = Config::getLicenseKey();
      $site_url = Config::getSiteURL();
      $parts = parse_url($site_url);
      $domain = isset($parts['host']) ? $parts['host'] : '/';

      $url = "http://outpost.statamic.com/check?v=".urlencode(STATAMIC_VERSION)."&l=".urlencode($license)."&d=".urlencode($domain);
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_TIMEOUT, '3');
      $content = trim(curl_exec($ch));
      curl_close($ch);

      if ($content <> '') {
        $response = json_decode($content);
        if ($response && $response->status == 'ok') {
          $app->setEncryptedCookie('stat_latest_version', $response->current_version);
          $app->setEncryptedCookie('stat_latest_version_url', $response->url);
          $app->config['latest_version_url'] = $response->current_version;
          $app->config['latest_version'] = $response->current_version;
        } else {
          $app->config['latest_version_url'] = '';
          $app->config['latest_version'] = '';
        }
      }
    } else {
      $app->config['latest_version'] = $cookie;
      $app->config['latest_version_url'] = $app->getEncryptedCookie('stat_latest_version_url');
    }
  }
}

/////////////////////////////////////////////////////////////////////////////////////////////////
// ROUTES
/////////////////////////////////////////////////////////////////////////////////////////////////

$admin_app->get('/',  function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  if ( ! CP_Helper::show_page('dashboard')) {
    $redirect_to = Config::get('_admin_start_page', 'pages');
    $admin_app->redirect($_SERVER['SCRIPT_NAME'].'/'.$redirect_to);
  }

  $template_list = array("dashboard");
  Statamic_View::set_templates(array_reverse($template_list));
  $admin_app->render(null, array('route' => 'root', 'app' => $admin_app));
})->name('home');




// AUTH RELATED FUNCTION
// --------------------------------------------------------
$admin_app->get('/denied', function() use ($admin_app) {
  $template_list = array("denied");
  Statamic_View::set_templates(array_reverse($template_list));
  Statamic_View::set_layout("layouts/login");
  $admin_app->render(null, array('route' => 'login', 'app' => $admin_app));
})->name('denied');




$admin_app->get('/login', function() use ($admin_app) {
  $template_list = array("login");
  Statamic_View::set_templates(array_reverse($template_list));
  Statamic_View::set_layout("layouts/login");
  $admin_app->render(null, array('route' => 'login', 'app' => $admin_app));
})->name('login');




$admin_app->post('/login', function() use ($admin_app) {
  $app = \Slim\Slim::getInstance();

  $login = Request::post('login');
  $username = $login['username'];
  $password = $login['password'];

  $errors = array();
  // Auth login
  // if success direct to admin homepage
  if (Statamic_Auth::login($username, $password)) {

    $user = Statamic_Auth::get_user($username);

    if ( ! $user->is_password_encrypted()) {
      $user->set_password($user->get_password(), true);
      $user->save();
      $errors = array('login' => 'Password has been encrypted. Please login again.');
    } else {
       $app->redirect($app->urlFor('home'));
    }

  } else {
    $errors = array('login' => 'Incorrect username or password. Try again.');
  }

  $template_list = array("login");
  Statamic_View::set_templates(array_reverse($template_list));
  Statamic_View::set_layout("layouts/login");
  $admin_app->render(null, array('route' => 'login', 'app' => $admin_app, 'errors' => $errors));

})->name('login-submit');




$admin_app->get('/logout', function() use ($admin_app) {
  Statamic_Auth::logout();
  $admin_app->redirect($admin_app->urlFor('home'));
})->name('logout');




// ERROR FUNCTION
// --------------------------------------------------------
$admin_app->get('/error', function() use ($admin_app) {
  $template_list = array("error");
  Statamic_View::set_templates(array_reverse($template_list));
  Statamic_View::set_layout("layouts/default");
  $admin_app->render(null, array('route' => 'login', 'app' => $admin_app));
})->name('error');



// PUBLICATION
// --------------------------------------------------------
$admin_app->get('/pages', function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);
  $template_list = array("pages");

  if ( ! Statamic::is_content_writable()) {
    $admin_app->flash('error', 'Content folder not writable');
    $url = $admin_app->urlFor('error')."?code=write_permission";
    $admin_app->redirect($url);

    return;
  }

  $path = "";
  $path = $admin_app->request()->get('path');
  $errors = array();
  $pages = Statamic::get_content_tree('/', 1, 1000, false, false, false, false, '/');

  #######################################################################
  # Fieldsets
  #######################################################################

  $fieldsets = Statamic_Fieldset::get_list();

  foreach ($fieldsets as $key => $fieldset) {

    # Remove hidden fieldsets
    if (isset($fieldset['hide']) && $fieldset['hide'] === true) {
      unset($fieldsets[$key]);
    } elseif ( ! isset($fieldset['title'])) {
      # set a optional name
      $fieldsets[$key]['title'] = Slug::prettify($key);
    }

  }

  # Sort by title
  uasort($fieldsets, function($a, $b) {
    return strcmp($a['title'], $b['title']);
  });

  #######################################################################

  $node['type'] = 'home';
  $node['url'] = "/page";
  $node['slug'] = "/";

  $meta = Statamic::get_content_meta("page", "");

  //$node['meta'] = $meta;

  if (isset($meta['title'])) {
    $node['title'] = $meta['title'];
  }
  if (File::exists(Path::tidy(Config::getContentRoot()."/fields.yaml"))) {
    $node['has_entries'] = TRUE;
  }
  $node['depth'] = 1;

  array_unshift($pages, $node);

  Statamic_View::set_templates(array_reverse($template_list));
  $admin_app->render(null, array('route' => 'pages', 'app' => $admin_app
    , 'errors' => $errors
    , 'path' => $path
    , 'pages' => $pages
    , 'fieldsets' => $fieldsets
    , 'are_fieldsets' => count($fieldsets) > 0 ? true : false
    ));
})->name('pages');




$admin_app->get('/entries', function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);
  $content_root = Config::getContentRoot();
  $template_list = array("entries");

  $path = "";
  $path = $admin_app->request()->get('path');
  $errors = array();

  $path = $admin_app->request()->get('path');
  if ($path) {
    $entry_type = Statamic::get_entry_type($path);

    $order = $entry_type == 'date' ? 'desc' : 'asc';

    $entries = Statamic::get_content_list($path,null,0,true,true,$entry_type, $order, null, null, true);
    Statamic_View::set_templates(array_reverse($template_list));

    $admin_app->render(null, array(
       'route'   => 'entries',
       'app'     => $admin_app,
       'errors'  => $errors,
       'path'    => $path,
       'entries' => $entries,
       'type'    => $entry_type
      ));
  }
})->name('entries');

// LOGIC
// - VALIDATE
// - SAVE TO ORIGINAL FILENAME
// - IF NECESSARY: RENAME

// POST: PUBLISH
$admin_app->post('/publish', function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $content_root = Config::getContentRoot();
  $content_type = Config::getContentType();

  $app = \Slim\Slim::getInstance();
  $path = Request::get('path');

  if ($path) {
    $index_file = false;
    $form_data = Request::post('page');

    // 1. Validate
    if ($form_data) {
      // ### Intercept the timestamp and convert to something we can work with
      if (isset($form_data['meta']['publish-time'])) {
        $_ts = $form_data['meta']['publish-time'];
        $ts = strtotime($_ts);
        $form_data['meta']['publish-time'] = Date::format("Hi", $ts);
      }

      if ($form_data['type'] == 'none') {
        $index_file = true;
      }

      // @TODO, confirm "/page" is the best match pattern
      // e.g. "2-blog/_2013-04-11-a-hidden-page" will trigger (true)
      if (Pattern::endsWith($path, '/page')) {
        $index_file = true;
      }

      $errors = array();

      if ( ! $form_data['yaml']['title'] || $form_data['yaml']['title'] == '') {
        $errors['title'] = 'is required';
      }

      if ($index_file) {
        // some different validation rules
        $slug = $form_data['meta']['slug'];
        if ($slug == '') {
          $errors['slug'] = 'is required';
        } else {
          if ($slug != $form_data['original_slug']) {
            if ($form_data['type'] == 'none') {
              $file = $check_file = $content_root."/".$path."/".$slug."/page.".$content_type;
              $folders = Statamic::get_content_tree($path,1,1,false,false,true);
              if (Statamic_Validate::folder_slug_exists($folders, $slug)) {
                $errors['slug'] = 'already exists';
              }
            } else {
              $file = $content_root."/".dirname($path)."/page.".$content_type;
              $check_file = str_replace($form_data['original_slug'], $slug, $file);
              if (File::exists($check_file)) {
                $errors['slug'] = 'already exists';
              }
            }

          }
        }
      } elseif (isset($form_data['type']) && $form_data ['type'] == 'none') {
        $slug = $form_data['meta']['slug'];
        $file = $content_root."/".$path."/".$slug.".".$content_type;
        if (File::exists($file)) {
          $errors['slug'] = 'already exists';
        }
      } else {
        if (isset($form_data['new'])) {
          $entries = Statamic::get_content_list($path,null,0,true,true);
        } else {
          $entries = Statamic::get_content_list(dirname($path),null,0,true,true);
        }

        $slug = $form_data['meta']['slug'];
        if ($slug == '') {
          $errors['slug'] = 'is required';
        } else {
          // do we have this slug already?
          if (isset($form_data['new']) || $slug != $form_data['original_slug']) {
            if (Statamic_Validate::content_slug_exists($entries, $slug)) {
              $errors['slug'] = 'already exists';
            }
          }
        }

        // generate slug & datestamp/number
        $datestamp = '';
        $timestamp = '';
        $numeric = '';
        if ($form_data['type'] == 'date') {
          // STANDARDIZE INPUT
          $datestamp = $form_data['meta']['publish-date'];
          if ($datestamp == '') {
            $errors['datestamp'] = 'is required';
          }

          if (Config::getEntryTimestamps()) {
            $timestamp = $form_data['meta']['publish-time'];
            if ($timestamp == '') {
              $errors['timestamp'] = 'is required';
            }
          }
        } elseif ($form_data['type'] == 'number') {
          $numeric = $form_data['meta']['publish-numeric'];
          if ($numeric == '') {
            $errors['numeric'] = 'is required';
          }
        }
      }

      if (sizeof($errors) > 0) {
        // REPOPULATE IF THERE IS AN ERROR
        if (isset($form_data['new'])) {
          $data['new'] = $form_data['new'];
        }

        $data['path']        = $path;
        $data['page']        = '';
        $data['title']       = $form_data['yaml']['title'];

        $folder              = $form_data['folder'];
        $data['folder']      = $form_data['folder'];
        $data['content']     = $form_data['content'];
        $data['content_raw'] = $form_data['content'];
        $data['type']        = $form_data['type'];
        $data['errors']      = $errors;

        $data['slug'] = $form_data['meta']['slug'];
        $data['full_slug'] = $form_data['full_slug'];
        $data['original_slug'] = $form_data['original_slug'];

        $data['original_datestamp'] = $form_data['original_datestamp'];
        $data['original_timestamp'] = $form_data['original_timestamp'];
        $data['original_numeric'] = $form_data['original_numeric'];

        if (isset($form_data['fieldset'])) {
          $data['fieldset'] = $form_data['fieldset'];
        }

        if (!$index_file) {
          if (isset($form_data['type']) && $form_data ['type'] != 'none') {
            $data['datestamp'] = strtotime($datestamp);
            $data['timestamp'] = strtotime($datestamp." ".$timestamp);
            $data['numeric'] = $numeric;
          }
        }

        if (isset($form_data['yaml']['_template'])) {
          $data['_template'] = $form_data['yaml']['_template'];
        } else {
          $data['_template'] = '';
        }

        $data['templates'] = Theme::getTemplates();
        $data['layouts'] = Theme::getLayouts();

        $fields_data = null;
        $content_root = Config::getContentRoot();

        // fieldset
        if ($data['type'] == 'none') {
          // load field set

          if (isset($data['fieldset'])) {
            $fieldset = $data['fieldset'];
            $fs = Statamic_Fieldset::load($fieldset);
            $fields_data = $fs->get_data();
            $data['fields'] = isset($fields_data['fields']) ? $fields_data['fields'] : array();
            $data['fieldset'] = $fieldset;
          }
        } elseif ($data['type'] != 'none' && File::exists("{$content_root}/{$folder}/fields.yaml")) {

          $fields_raw = File::get("{$content_root}/{$folder}/fields.yaml");
          $fields_data = YAML::Parse($fields_raw);
          if (isset($fields_data['_fieldset'])) {
            $fieldset = $fields_data['_fieldset'];
            $fs = Statamic_Fieldset::load($fieldset);
            $fields_data = $fs->get_data();
            $data['fields'] = isset($fields_data['fields']) ? $fields_data['fields'] : array();
            $data['fieldset'] = $fieldset;
          }
        }

        if ($fields_data && isset($fields_data['fields'])) {
          $data['fields'] = $fields_data['fields'];
          // reload the fields data
          foreach ($data['fields'] as $key => $value) {
            if (isset($form_data['yaml'][$key])) {
              $data[$key] = $form_data['yaml'][$key];
            }
          }
        }

        $template_list = array("publish");
        Statamic_View::set_templates(array_reverse($template_list));
        $admin_app->render(null, array('route' => 'publish', 'app' => $admin_app)+$data);

        return;
      }
    } else {
      print "no form data";
    }
  } else {
    print "no form data";
  }

  $status = array_get($form_data['yaml'], 'status', 'live');
  $status_prefix = Slug::getStatusPrefix($status);

  // if we got here, have no errors
  // save to original file if not new
  if (isset($form_data['new'])) {
    if ($form_data['type'] == 'date') {

      $date_or_datetime = Config::getEntryTimestamps() ? $datestamp."-".$timestamp : $datestamp;
      $file = $content_root."/".$path."/".$status_prefix.$date_or_datetime."-".$slug.".".$content_type;

    } elseif ($form_data['type'] == 'number') {
      $file = $content_root."/".$path."/".$numeric.".".$slug.".".$content_type;
    } elseif ($form_data['type'] == 'none') {
      $numeric = Statamic::get_next_numeric_folder($path);

      $file = $content_root."/".$path."/".$numeric."-".$slug."/page.".$content_type;
      $file = Path::tidy($file);
      if (!File::exists(dirname($file))) {
        mkdir(dirname($file), 0777, true);
      }
    } else {
      $file = $content_root."/".$path."/".$form_data['original_slug'].".".$content_type;
    }
    $folder = $path;
  } else {

    $file = ltrim(URL::assemble(Config::getContentRoot(), $path), '/') . '.' . $content_type;

    // $folder = dirname($path);
    // if ($form_data['type'] == 'date') {
    //   if (Config::getEntryTimestamps()) {
    //     if ($form_data['original_timestamp'] == '') {
    //       $file = $content_root."/".dirname($path)."/".$form_data['original_datestamp']."-".$form_data['original_slug'].".".$content_type;
    //     } else {
    //       $file = $content_root."/".dirname($path)."/".$form_data['original_datestamp']."-".$form_data['original_timestamp']."-".$form_data['original_slug'].".".$content_type;
    //     }
    //   } else {
    //     $file = $content_root."/".dirname($path)."/".$form_data['original_datestamp']."-".$form_data['original_slug'].".".$content_type;
    //   }
    // } elseif ($form_data['type'] == 'number') {
    //   // $file = $content_root."/".dirname($path)."/".$form_data['original_numeric'].".".$form_data['original_slug'].".".$content_type;
    //   $file = $content_root."/".$path.".".$content_type;
    // } else {
    //   if ($index_file) {
    //     $file = $content_root."/".dirname($path)."/page.".$content_type;
    //   } else {
    //     $file = $content_root."/".dirname($path)."/".$form_data['original_slug'].".".$content_type;
    //   }
    // }
  }

  // load the original yaml
  if (isset($form_data['new'])) {
    $file_data = array();
  } else {
    $page = basename($path);
    $folder = dirname($path);
    $file_data = Statamic::get_content_meta($page, $folder, true);
  }

  # Post-processing for Fieldtypes api
  if (isset($file_data['_fieldset'])) {

    # defined a fieldset in the front-matter
    $fs = Statamic_Fieldset::load($file_data['_fieldset']);
    $fieldset_data = $fs->get_data();
    $data['fields'] = $fieldset_data['fields'];
  } elseif (isset($fields_data['fields'])) {

    # fields.yaml controls the fields
    $data['fields'] = $fields_data['fields'];
  } elseif (isset($fields_data['_fieldset'])) {

    # using a fieldset
    $fieldset = $fields_data['_fieldset'];
    $fs = Statamic_Fieldset::load($fieldset);
    $fieldset_data = $fs->get_data();
    $data['fields'] = $fieldset_data['fields'];
  } else {

    # not set.
    $data['fields'] = array();
  }

  $fieldset = null;
  if (file_exists("{$content_root}/{$folder}/fields.yaml")) {
    $fields_raw = File::get("{$content_root}/{$folder}/fields.yaml");
    $fields_data = YAML::Parse($fields_raw);

    if (isset($fields_data['fields'])) {

      #fields.yaml
      $field_settings = $fields_data['fields'];
    } elseif (isset($fields_data['_fieldset'])) {

      # using a fieldset
      $fieldset = $fields_data['_fieldset'];
      $fs = Statamic_Fieldset::load($fieldset);
      $fieldset_data = $fs->get_data();
      $field_settings = $fieldset_data['fields'];
    } else {
      $field_settings = array();
    }
  } elseif (isset($form_data['type']) && $form_data['type'] == 'none') {
    if (isset($form_data['fieldset'])) {
      $fieldset = $form_data['fieldset'];

      $file_data['_fieldset'] = $fieldset;
      $fs = Statamic_Fieldset::load($fieldset);
      $fields_data = $fs->get_data();
      $field_settings = $fields_data['fields'];
    }
  }

  // check for empty checkbox fields
  // unchecked checkbox fields will not be included in the POST array due to
  // being unsuccessful, thus, we need to loop through all expected fields
  // looking for a checkbox type, and if it isn't in POST, set it to 0 manually
  foreach ($field_settings as $field => $settings) {
    if (isset($settings['type']) && $settings['type'] == 'checkbox' && !isset($form_data['yaml'][$field])) {
      $form_data['yaml'][$field] = 0;
    }
  }

  if (isset($_FILES['page'])) {
    foreach ($_FILES['page']['name']['yaml'] as $field => $value) {
      if (isset($field_settings[$field]['type'])) {
        if ($field_settings[$field]['type'] == 'file') {
          if ($value <> '') {
            $file_values = array();
            $file_values['name'] = $_FILES['page']['name']['yaml'][$field];
            $file_values['type'] = $_FILES['page']['type']['yaml'][$field];
            $file_values['tmp_name'] = $_FILES['page']['tmp_name']['yaml'][$field];
            $file_values['error'] = $_FILES['page']['error']['yaml'][$field];
            $file_values['size'] = $_FILES['page']['size']['yaml'][$field];
            $val = Fieldtype::process_field_data('file', $file_values, $field_settings[$field]);
            $file_data[$field] = $val;
            unset($form_data['yaml'][$field]);
          } else {
            if (isset($form_data['yaml'][$field.'_remove'])) {
              $form_data['yaml'][$field] = '';
              $file_data[$field] = '';
            } else {
              $file_data[$field] = isset($form_data['yaml'][$field]) ? $form_data['yaml'][$field] : '';
            }
          }
          // unset the remove column
          if (isset($form_data['yaml']["{$field}_remove"])) {
            unset($form_data['yaml']["{$field}_remove"]);
          }
        }
      }
    }
  }

  // foreach ($_FILES as $field => $value) {
  //   if (isset($form_data['yaml'][$field.'_remove'])) {
  //     $file_data[$field] = '';
  //   }

  //   if (isset($field_settings[$field]['type'])) {
  //     if ($field_settings[$field]['type'] == 'file') {
  //       $value = Fieldtype::process_field_data($field_settings[$field]['type'], $value, $field_settings[$field]);
  //       if ($value <> '') {
  //         $file_data[$field] = $value;
  //       }
  //     }
  //   }
  // }

  foreach ($form_data['yaml'] as $field => $value) {
    if (isset($field_settings[$field]['type']) && $field_settings[$field]['type'] != 'file') {
      $file_data[$field] = Fieldtype::process_field_data($field_settings[$field]['type'], $value, $field_settings[$field], $field);
    }
  }

  unset($file_data['content']);
  unset($file_data['content_raw']);
  unset($file_data['last_modified']);

  if (isset($file_data['status'])) {
    unset($file_data['status']);
  }

  $file_content = File::buildContent($file_data, $form_data['content']);
  File::put($file, $file_content);


  // Do we need to rename the file?
  if ( ! isset($form_data['new'])) {

    $new_slug = $form_data['meta']['slug'];

    // rd($new_slug);

    if ($form_data['type'] == 'date') {
      if (Config::getEntryTimestamps()) {
        $new_timestamp = $form_data['meta']['publish-time'];
        $new_datestamp = $form_data['meta']['publish-date'];
        $new_file = $content_root . "/" . dirname($path) . "/" . $status_prefix .  $new_datestamp . "-" . $new_timestamp . "-" . $new_slug.".".$content_type;
      } else {
        $new_datestamp = $form_data['meta']['publish-date'];
        $new_file = $content_root . "/" . dirname($path) . "/" . $status_prefix . $new_datestamp . "-" . $new_slug.".".$content_type;
      }
    } elseif ($form_data['type'] == 'number') {
      $new_numeric = $form_data['meta']['publish-numeric'];
      $new_file = $content_root . "/" . dirname($path) . "/" . $status_prefix . $new_numeric . "." . $new_slug . "." . $content_type;
    } else {
      if ($index_file) {
        $new_file = str_replace($form_data['original_slug'], $new_slug, $file);
      } else {
        $new_file = $content_root . "/" . dirname($path) . "/" . $status_prefix . $new_slug . "." . $content_type;
      }
    }

    if ($file !== $new_file) {
      if ($index_file) {
        rename(dirname($file), dirname($new_file));
      } else {
        rename($file, $new_file);
      }
    }
  }

  // rediect back to entries
  if ($form_data['type'] == 'none') {
    $app->flash('success', 'Page saved successfully!');
    $url = $app->urlFor('pages')."?path=".$folder;
    $app->redirect($url);
  } else {
    $app->flash('success', 'Entry saved successfully!');
    $url = $app->urlFor('entries')."?path=".$folder;
    $app->redirect($url);
  }

});

// GET: DELETE ENTRY
$admin_app->get('/delete/entry', function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);
  $content_root = Config::getContentRoot();
  $content_type = Config::getContentType();

  $path = $admin_app->request()->get('path');
  $folder = dirname($path);
  $file = $content_root."/".$path.".".$content_type;
  if (File::exists($file)) {
    // rediect back to entries
    unlink($file);
    $admin_app->flash('success', 'Entry successfully deleted!');
    $url = $admin_app->urlFor('entries')."?path=".$folder;
    $admin_app->redirect($url);
  }
})->name('delete_entry');




// GET: DELETE PAGE
$admin_app->get('/delete/page', function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $path = URL::assemble(BASE_PATH, Config::getContentRoot(), $admin_app->request()->get('path'));
  $type = $admin_app->request()->get('type');

  if ($type == "folder" && Folder::exists($path)) {
    Folder::delete($path);
    $admin_app->flash('success', 'Page successfully deleted!');

  } elseif (File::exists($path.'.'.Config::getContentType())) {
    File::delete($path.'.'.Config::getContentType());
    $admin_app->flash('success', 'Page successfully deleted!');

  } else {
    $admin_app->flash('failusre', 'Unable to delete page.');
  }

  $admin_app->redirect($admin_app->urlFor('pages'));
})->name('delete_page');




// GET: PUBLISH
$admin_app->get('/publish', function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);
  $content_root = Config::getContentRoot();
  $app = \Slim\Slim::getInstance();

  $data     = array();
  $path     = Request::get('path');
  $new      = Request::get('new');
  $fieldset = Request::get('fieldset');
  $type     = Request::get('type');

  if ($path) {

    if ($new) {
      $data['new'] = 'true';
      $page     = 'new-slug';
      $folder   = $path;
      $data['full_slug'] = dirname($path);
      $data['slug'] = '';
      $data['path'] = $path;
      $data['page'] = '';
      $data['title'] = '';
      $data['folder'] = $folder;
      $data['content'] = '';
      $data['content_raw'] = '';

      $data['datestamp'] = time();
      $data['timestamp'] = time();

      $data['original_slug'] = '';
      $data['original_datestamp'] = '';
      $data['original_timestamp'] = '';
      $data['original_numeric'] = '';

      if ($type == 'none') {
        $data['folder'] = $path;
        $data['full_slug'] = $path;
      }

    } else {
      $page   = basename($path);

      $folder = substr($path, 0, (-1*strlen($page))-1);

      if ( ! Content::exists($page, $folder)) {
        $app->flash('error', 'Content not found!');
        $url = $app->urlFor('pages');
        $app->redirect($url);

        return;
      }

      $data = Statamic::get_content_meta($page, $folder, true);

      $data['status'] = array_get($data, 'status', Slug::getStatus($page));

      $data['title'] = isset($data['title']) ? $data['title'] : '';
      $data['slug'] = basename($path);
      $data['full_slug'] = $folder."/".$page;
      $data['path'] = $path;
      $data['folder'] = $folder;
      $data['page'] = $page;
      $data['type'] = 'none';
      $data['original_slug'] = '';
      $data['original_datestamp'] = '';
      $data['original_timestamp'] = '';
      $data['original_numeric'] = '';
      $data['datestamp'] = 0;

      if ($page == 'page') {
        $page = basename($folder);
        if ($page == '') $page = '/';
        $folder = dirname($folder);
        $data['full_slug'] = $page;
      }
    }

    $content_root = $content_root;
    if ($data['slug'] != 'page' && File::exists("{$content_root}/{$folder}/fields.yaml")) {

      $fields_raw = file_get_contents("{$content_root}/{$folder}/fields.yaml");
      $fields_data = YAML::Parse($fields_raw);

      if (isset($fields_data['fields'])) {
        # fields.yaml controls the fields
        $data['fields'] = $fields_data['fields'];
      } elseif (isset($fields_data['_fieldset'])) {
        # using a fieldset
        $fieldset = $fields_data['_fieldset'];
        $fs = Statamic_Fieldset::load($fieldset);
        $fieldset_data = $fs->get_data();
        $data['fields'] = $fieldset_data['fields'];
      } else {
        # not set.
        $data['fields'] = array();
      }

      $data['type'] = isset($fields_data['type']) && ! is_array($fields_data['type']) ? $fields_data['type'] : $fields_data['type']['prefix'];

      // Slug
      if (Slug::isDraft($page)) {
        $slug = substr($page, 2);
      } elseif (Slug::isHidden($page)) {
        $slug = substr($page, 1);
      } else {
        $slug = $page;
      }

      if ($data['type'] == 'date') {
        if (Config::getEntryTimestamps() && Slug::isDateTime($page)) {

          $data['full_slug'] = $folder;
          $data['original_slug'] = substr($slug, 16);
          $data['slug'] = substr($slug, 16);
          $data['original_datestamp'] = substr($slug, 0, 10);
          $data['original_timestamp'] = substr($slug, 11, 4);
          if (!$new) {
            $data['datestamp'] = strtotime(substr($slug, 0, 10));
            $data['timestamp'] = strtotime(substr($slug, 0, 10) . " " . substr($slug, 11, 4));

            $data['full_slug'] = $folder."/".$data['original_slug'];
          }
        } else {
          $data['full_slug'] = $folder;
          $data['original_slug'] = substr($slug, 11);
          $data['slug'] = substr($slug, 11);
          $data['original_datestamp'] = substr($slug, 0, 10);
          $data['original_timestamp'] = "";
          if (!$new) {
            $data['datestamp'] = strtotime(substr($slug, 0, 10));
            $data['full_slug'] = $folder."/".$data['original_slug'];
            $data['timestamp'] = "0000";
          }
        }
      } elseif ($data['type'] == 'number') {
        if ($new) {
          $data['original_numeric'] = Statamic::get_next_numeric($folder);
          $data['numeric'] = Statamic::get_next_numeric($folder);
          $data['full_slug'] = $folder;
        } else {
          $numeric = Slug::getOrderNumber($slug);
          $data['slug'] = substr($slug, strlen($numeric)+1);
          $data['original_slug'] = substr($slug, strlen($numeric)+1);
          $data['numeric'] = $numeric;
          $data['original_numeric'] = $numeric;
          $data['full_slug'] = $folder."/".$data['original_slug'];
        }
      }
    } else {

      if ($new) {
        if ($fieldset) {
          $fs = Statamic_Fieldset::load($fieldset);
          $fields_data = $fs->get_data();
          $data['fields'] = isset($fields_data['fields']) ? $fields_data['fields'] : array();
          $data['type'] = 'none';
          $data['fieldset'] = $fieldset;
        }
      } else {
        if (isset($data['_fieldset'])) {
          $fs = Statamic_Fieldset::load($data['_fieldset']);
          $fields_data = $fs->get_data();
          $data['fields'] = isset($fields_data['fields']) ? $fields_data['fields'] : array();
          $data['fieldset'] = $data['_fieldset'];
        }
        $data['type'] = 'none';
      }

      $data['slug'] = $page;
      $data['original_slug'] = $page;
    }

  } else {
    print "NO PATH";
  }

  $data['templates'] = Theme::getTemplates();
  $data['layouts'] = Theme::getLayouts();

  $template_list = array("publish");
  Statamic_View::set_templates(array_reverse($template_list));
  $admin_app->render(null, array('route' => 'publish', 'app' => $admin_app)+$data);
})->name('publish');




// MEMBERS
// --------------------------------------------------------
$admin_app->get('/members', function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $members = Statamic_Auth::get_user_list();
  $data['members'] = $members;

  $template_list = array("members");
  Statamic_View::set_templates(array_reverse($template_list));
  $admin_app->render(null, array('route' => 'members', 'app' => $admin_app)+$data);
})->name('members');




// POST: MEMBER
// --------------------------------------------------------
$admin_app->post('/member', function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $data = array();
  $name = $admin_app->request()->get('name');

  $form_data = $admin_app->request()->post('member');
  if ($form_data) {
    $errors = array();
    // VALIDATE
    if (isset($form_data['new'])) {
      $name = $form_data['name'];
      if ($name == '') {
        $errors['name'] = 'is required';
      } else {
        if (Statamic_Auth::user_exists($name)) {
          $errors['name'] = 'is already taken';
        }
      }
      if ((!isset($form_data['yaml']['password'])) || (!isset($form_data['yaml']['password']))) {
        $errors['password'] = 'and confirmation is required';
      } else {
        if ($form_data['yaml']['password'] == '') {
          $errors['password'] = 'must be at least 1 character';
        } elseif ($form_data['yaml']['password'] != $form_data['yaml']['password_confirmation']) {
          $errors['password'] = 'and confirmation do not match';
        }
      }
    } else {
      if ($form_data['name'] <> $form_data['original_name']) {
        if (Statamic_Auth::user_exists($form_data['name'])) {
          $errors['name'] = 'is already taken';
        }
      }

      if (isset($form_data['yaml']['password'])) {
        if ((!isset($form_data['yaml']['password'])) || (!isset($form_data['yaml']['password']))) {
          $errors['password'] = 'and confirmation is required';
        } else {
          if ($form_data['yaml']['password'] <> '') {
            if ($form_data['yaml']['password'] != $form_data['yaml']['password_confirmation']) {
              $errors['password'] = 'and confirmation do not match';
            }
          }
        }
      }
    }

    if (sizeof($errors) > 0) {
      // repopulate and re-render
      $data['errors'] = $errors;

      $data['name'] = $form_data['name'];
      $data['first_name'] = $form_data['yaml']['first_name'];
      $data['last_name'] = $form_data['yaml']['last_name'];
      $data['roles'] = $form_data['yaml']['roles'];
      $data['biography'] =  $form_data['biography'];
      $data['original_name'] = $form_data['original_name'];

      $template_list = array("member");
      Statamic_View::set_templates(array_reverse($template_list));
      $admin_app->render(null, array('route' => 'publish', 'app' => $admin_app)+$data);

      return;
    }

    // IF NOT ERRORS SAVE
    if (isset($form_data['new'])) {
      $user = new Statamic_User(array());
      $user->set_name($name);
    } else {
      $user = Statamic_User::load($name);
    }

    $user->set_first_name($form_data['yaml']['first_name']);
    $user->set_last_name($form_data['yaml']['last_name']);
    if ( ! isset($form_data['yaml']['roles'])) {
      $form_data['yaml']['roles'] = '';
    }
    $user->set_roles($form_data['yaml']['roles']);
    $user->set_biography_raw($form_data['biography']);


    if (isset($form_data['yaml']['password']) && $form_data['yaml']['password'] <> '') {
      $user->set_password($form_data['yaml']['password'], true);
    }

    $user->save();

    // Rename?
    if (!isset($form_data['new'])) {
      $user->rename($form_data['name']);
    }

    // REDIRECT
    $admin_app->flash('success', 'Member successfully saved!');
    $url = $admin_app->urlFor('members');
    $admin_app->redirect($url);
  }
});





// GET: MEMBER
// --------------------------------------------------------
$admin_app->get('/member', function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);
  $data = array();

  if ( ! Statamic::are_users_writable()) {
    $admin_app->flash('error', 'The Users directory is not writable.');
    $url = $admin_app->urlFor('error')."?code=write_permission";
    $admin_app->redirect($url);

    return;
  }

  $name = $admin_app->request()->get('name');
  $new  = $admin_app->request()->get('new');

  if ($new) {
    $data['name']           = '';
    $data['new']            = 'true';
    $data['content_raw']    = '';
    $data['original_name']  = '';
    $data['first_name']     = '';
    $data['last_name']      = '';
    $data['roles']          = '';
    $data['biography']      = '';
    $data['is_password_encrypted'] = false;

  } else {
    $user = Statamic_Auth::get_user($name);

    if (!$user) {
      die("Error");
    }

    $data['name'] = $name;
    $data['first_name'] = $user->get_first_name();
    $data['last_name'] = $user->get_last_name();
    $data['roles'] = $user->get_roles_list();
    $data['is_password_encrypted'] = $user->is_password_encrypted();

    $data['biography'] =  $user->get_biography_raw();

    $data['original_name'] = $name;
  }

  $template_list = array("member");
  Statamic_View::set_templates(array_reverse($template_list));
  $admin_app->render(null, array('route' => 'members', 'app' => $admin_app)+$data);
})->name('member');





// GET: DELETE MEMBER
$admin_app->get('/deletemember', function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $name = $admin_app->request()->get('name');
  if (Statamic_Auth::user_exists($name)) {
    $user = Statamic_Auth::get_user($name);
    $user->delete();
  }

  // Redirect
  $admin_app->flash('info', 'Member deleted');
  $url = $admin_app->urlFor('members');
  $admin_app->redirect($url);
})->name('deletemember');




// Account
// --------------------------------------------------------
$admin_app->get('/account', function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $template_list = array("account");
  Statamic_View::set_templates(array_reverse($template_list));
  $admin_app->render(null, array('route' => 'members', 'app' => $admin_app));
})->name('account');




// System
// --------------------------------------------------------
$admin_app->get('/system', function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $template_list = array("system");
  Statamic_View::set_templates(array_reverse($template_list));

  $data = array();

  if (isCurlEnabled()) {

    $user = Statamic_Auth::get_current_user();
    $username = $user->get_name();

    $tests = array(
      '_app'                                            => "Your application folder is accessible to the web. While not critical, it's best practice to protect this folder.",
      '_config'                                         => "Your config folder is accessible to the web. It is critical that you protect this folder.",
      '_config/settings.yaml'                           => "Your settings files are accessible to the web. It is critical that you protect this folder.",
      '_config/users/'.$username.'.yaml'                => "Your member files are accessible to the web. It is critical that you protect this folder.",
      Config::getContentRoot()                          => "Your content folder is accessible to the web. While not critical, it is best practice to protect this folder.",
      Config::getTemplatesPath().'layouts/default.html' => "Your theme template files are accessible to the web. While not critical, it is best practice to protect this folder.",
      '_logs'                                           => "Your logs folder is accessible to the web. It is critical that you protect this folder."
    );

    $site_url = 'http://'.$_SERVER['HTTP_HOST'].'/';

    foreach ($tests as $url => $message) {
      $test_url = $site_url.$url;

      $http = curl_init($test_url);
      curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);
      $result = curl_exec($http);
      $http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
      curl_close($http);

      $data['system_checks'][$url]['status_code'] = $http_status;
      $data['system_checks'][$url]['status'] = $http_status !== 200 ? 'good' : 'warning';
      $data['system_checks'][$url]['message'] = $message;
    }
  }

  $data['users'] = Statamic_Auth::get_user_list();

  $admin_app->render(null, array('route' => 'system', 'app' => $admin_app)+$data);
})->name('system');





// Logs
// --------------------------------------------------------
$admin_app->get('/logs', function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $template_list = array("logs");
  Statamic_View::set_templates(array_reverse($template_list));

  $data = array();
  $data['enabled']       = Config::get("_log_enabled", false);
  $data['raw_path']      = Config::get("_log_file_path");
  $data['prefix']        = Config::get("_log_file_prefix");
  $data['log_level']     = Config::get("_log_level");
  $data['time_format']   = Config::get("_time_format");
  $data['logs']          = array();
  $data['logs_exist']    = FALSE;
  $data['records_exist'] = FALSE;
  $data['log_items']     = 0;
  $data['load_date']     = Date::format("Y-m-d");
  $data['log']           = array();
  $data['filter']        = '';
  $data['logs_writable'] = FALSE;

  // determine actual path
  $data['path'] = $data['raw_path'];
  if (!in_array(substr($data['raw_path'], 0, 1), array("/", "."))) {
    $data['path'] = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $data['raw_path'];
  }

  // is log folder writable?
  if (is_writable($data['path'])) {
    $data['logs_writable'] = TRUE;
  }

  // do any logs exist here?
  try {
    $filename_regex = "/^" . $data['prefix'] . "_(\d{4})-(\d{2})-(\d{2})/i";
    $dir = opendir($data['path']);

    if (!$dir) {
      throw new Exception("Directory not found");
    }

    while (FALSE !== ($file = readdir($dir))) {
      if (!preg_match($filename_regex, $file, $matches)) {
        // no match, nothing to see here
        continue;
      }

      $data['logs'][$matches[1] . "-" . $matches[2] . "-" . $matches[3]] = array(
        "date" => Date::format(Config::getDateFormat(), $matches[1] . "-" . $matches[2] . "-" . $matches[3]),
        "raw_date" => $matches[1] . "-" . $matches[2] . "-" . $matches[3],
        "filename" => $file,
        "full_path" => $data['path'] . DIRECTORY_SEPARATOR . $file
        );

      // we have found at least one valid log
      $data['logs_exist'] = TRUE;
    }

    closedir($dir);

    // flip the order of logs
    $data['logs'] = array_reverse($data['logs']);
  } catch (Exception $e) {
    // no logs exist
    $data['logs_exist'] = FALSE;
  }

  // filter
  $match = array('DEBUG', 'INFO', 'WARN', 'ERROR', 'FATAL');
  if (isset($_GET['filter']) && trim($_GET['filter']) && in_array(strtoupper($_GET['filter']), $match)) {
    $data['filter'] = strtolower($_GET['filter']);

    switch(strtolower($_GET['filter'])) {
      case 'debug':
        $match = array('DEBUG');
        break;

      case 'info':
        $match = array('INFO');
        break;

      case 'info+';
        $match = array('INFO', 'WARN', 'ERROR', 'FATAL');
        break;

      case 'warn':
        $match = array('WARN');
        break;

      case 'warn+';
        $match = array('WARN', 'ERROR', 'FATAL');
        break;

      case 'error':
        $match = array('ERROR');
        break;

      case 'error+';
        $match = array('ERROR', 'FATAL');
        break;

      case 'fatal':
        $match = array('FATAL');
        break;
    }
  }

  // parse out logs, filtering the logs we want
  if ($data['logs_exist']) {
    $logs = array_values($data['logs']);

    // check for a log file to capture
    $data['load_date'] = (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) ? $_GET['date'] : $logs[0]['raw_date'];

    // load log
    try {
      $raw_log = file_get_contents($data['logs'][$data['load_date']]['full_path']);

      // parse through log
      $raw_log_lines = explode(PHP_EOL, trim($raw_log));
      $data['log_items'] = count($raw_log_lines);

      // check for existing but empty log files
      if ($data['log_items'] === 1 && trim($raw_log) === "") {
        $data['log_items'] = 0;
      }

      foreach($raw_log_lines as $line) {
        $log = explode("|", $line);

        if (!in_array($log[0], $match)) {
          continue;
        }

        array_push($data['log'], $log);
      }

      $data['records_exist'] = (bool) count($data['log']);
      $data['log'] = array_reverse($data['log']);
    } catch (Exception $e) {
      // no extra steps needed
    }
  }

  $admin_app->render(null, array('route' => 'logs', 'app' => $admin_app)+$data);
})->name('logs');





// GET: IMAGES
// DEPRICATED in 1.3
// --------------------------------------------------------
$admin_app->get('/images',  function() use ($admin_app) {
  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $path = $admin_app->request()->get('path');

  $image_list = glob($path."*.{jpg,jpeg,gif,png}", GLOB_BRACE);
  $images = array();

  if (count($image_list) > 0) {
    foreach ($image_list as $image) {
      $images[] = array(
        'thumb' => '/'.$image,
        'image' => '/'.$image
      );
    }
  }


  echo json_encode($images);

})->name('images');




// GET: 404
// --------------------------------------------------------
$admin_app->notFound(function() use ($admin_app) {

  authenticateForRole('admin');
  doStatamicVersionCheck($admin_app);

  $admin_app->flash('error', "That page did not exist, so we sent you here instead.");
  $redirect_to = Config::get('_admin_start_page', 'pages');
  $admin_app->redirect($redirect_to);

});
