<?php
class statamic_user
{
  protected $data = array();
  protected $name = NULL;

  public function Statamic_User($data)
  {
    $this->data = $data;
  }

  public function set_name($name)
  {
    $this->name = $name;
  }

  public function get_name()
  {
    return $this->name;
  }

  public function set_first_name($name)
  {
    $this->data['first_name'] = $name;
  }

  public function get_first_name()
  {
    if (isset($this->data['first_name'])) {
      return $this->data['first_name'];
    }

    return '';
  }

  public function set_password($password, $encrypted=FALSE)
  {
    if ($encrypted) {

      if (!isset($this->data['salt']) || $this->data['salt'] == '') {
        $this->data['salt'] = Helper::getRandomString(32);
      }

      $encrypted_password = sha1($password.$this->data['salt']);
      $this->data['encrypted_password'] = $encrypted_password;
      $this->data['password'] = '';
    } else {
      $this->data['password'] = $password;
      $this->data['encrypted_password'] = '';
      $this->data['salt'] = '';
    }
  }

  public function get_last_name()
  {
    if (isset($this->data['last_name'])) {
      return $this->data['last_name'];
    }

    return '';
  }

  public function get_password()
  {
    if (isset($this->data['password'])) {
      return $this->data['password'];
    }
  }

  public function set_last_name($name)
  {
    $this->data['last_name'] = $name;
  }

  public function get_biography()
  {
    if (isset($this->data['biography'])) {
      return $this->data['biography'];
    }

    return '';
  }

  public function set_biography_raw($biography)
  {
    $this->data['biography_raw'] = $biography;
    $this->data['biography'] = Content::transform($biography);
  }

  public function get_biography_raw()
  {
    if (isset($this->data['biography_raw'])) {
      return $this->data['biography_raw'];
    }

    return '';
  }

  public function set_roles($string)
  {
    $this->data['roles'] = explode(",", $string);
  }

  public function get_roles_list($delim=', ')
  {
    if (isset($this->data['roles'])) {
      return implode($delim, $this->data['roles']);
    }

    return '';
  }

  public function correct_password($password)
  {
    if (isset($this->data['password']) && $this->data['password'] <> '') {
      if ($this->data['password'] == $password) {
        return TRUE;
      }
    } elseif (isset($this->data['encrypted_password']) && $this->data['encrypted_password'] <> '') {
      $salt = "";
      if (isset($this->data['salt'])) {
        $salt = $this->data['salt'];
      }
      if (sha1($password.$salt) == $this->data['encrypted_password']) {
        return TRUE;
      }
    }

    return FALSE;
  }

  public function get_encrypted_password($value='')
  {
    $ep = '';
    if (isset($this->data['password'])) {
      // ### TODO ENCRYPT THE PASSWORD
      $ep = $this->data['password'];
    } elseif (isset($this->data['encrypted_password'])) {
      $ep = $this->data['encrypted_password'];
    }

    return $ep;
  }

  public function is_password_encrypted()
  {
    if (isset($this->data['encrypted_password']) && $this->data['encrypted_password'] != "") {
      return TRUE;
    }

    return FALSE;
  }

  public function has_role($role)
  {
    if (isset($this->data['roles'])) {
      $roles = $this->data['roles'];

      if (in_array($role, $roles)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  public function rename($name)
  {
    $file = "_config/users/{$this->name}.yaml";
    $new_file = "_config/users/{$name}.yaml";
    rename($file, $new_file);
  }

  public function save()
  {
    $file_content = "";
    $file_content .= "---\n";
    $file_content .= "first_name: {$this->data['first_name']}\n";
    $file_content .= "last_name: {$this->data['last_name']}\n";
    $file_content .= "roles: [".implode(",",$this->data['roles'])."]\n";

    if (isset($this->data['password']))
      $file_content .= "password: {$this->data['password']}\n";

    if (isset($this->data['encrypted_password']))
      $file_content .= "encrypted_password: {$this->data['encrypted_password']}\n";

    if (isset($this->data['salt']))
      $file_content .= "salt: {$this->data['salt']}\n";

    $file_content .= "---\n";
    $file_content .= $this->data['biography_raw'];
    $file_content .= "\n";

    $file = "_config/users/{$this->name}.yaml";
    file_put_contents($file, $file_content);
  }

  public function delete()
  {
    $file = "_config/users/{$this->name}.yaml";
    unlink($file);
  }

  // STATIC FUNCTIONS
  // ------------------------------------------------------
  public static function load($username)
  {
    $meta_raw = "";
    if (File::exists("_config/users/{$username}.yaml")) {
      $meta_raw = file_get_contents("_config/users/{$username}.yaml");
    } else {
      return NULL;
    }

    if (Pattern::endsWith($meta_raw, "---")) {
      $meta_raw .= "\n"; # prevent parse failure
    }
    # Parse YAML Front Matter
    if (stripos($meta_raw, "---") === FALSE) {
      $meta = YAML::Parse($meta_raw);
      $meta['content'] = "";
    } else {

      list($yaml, $content) = preg_split("/---/", $meta_raw, 2, PREG_SPLIT_NO_EMPTY);
      $meta = YAML::Parse($yaml);
      $meta['biography_raw'] = trim($content);
      $meta['biography'] = Content::transform($content);

      $u = new Statamic_User($meta);
      $u->set_name($username);

      return $u;
    }
  }

  public static function get_profile($username)
  {
    if (File::exists("_config/users/{$username}.yaml")) {
      $protected_fields = array_fill_keys(
          array('password', 'encrypted_password', 'salt'),
        NULL);

      $profile_content = file_get_contents("_config/users/{$username}.yaml");
      $profile_data = Statamic::yamlize_content($profile_content, 'biography');

      return array_diff_key($profile_data, $protected_fields);

    }

    return NULL;
  }

}
