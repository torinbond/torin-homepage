<?php
class statamic_fieldset
{
  protected $data = array();
  protected $name = null;

  public function Statamic_Fieldset($data)
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

  public function get_data()
  {
    return $this->data;
  }

  // STATIC FUNCTIONS
  // ------------------------------------------------------
  public static function load($fieldsets)
  {
    $fields = array('fields' => array());
    $included_fields = array('fields' => array());
    $fieldset_names = array();

    if (! is_array($fieldsets)) {
      $fieldsets = array($fieldsets);
    }

    foreach ($fieldsets as $key => $name) {
      if (File::exists("_config/fieldsets/{$name}.yaml")) {
        $meta = self::fetch_fieldset($name);

        if (isset($meta['include'])) {

          if ( ! is_array($meta['include'])) {
            $meta['include'] = array($meta['include']);
          }

          foreach ($meta['include'] as $include_key => $include_name) {
            $include = self::fetch_fieldset($include_name);
            $included_fields['fields'] = array_merge($included_fields['fields'], $include['fields']);
          }
        }

        $fields['fields'] = array_merge($fields['fields'], $included_fields['fields'], $meta['fields']);
        $fieldset_names[] = $name;
      }
    }

    $set = new Statamic_Fieldset($fields);
    $set->set_name($fieldset_names);

    return $set;
  }

  public static function fetch_fieldset($fieldset)
  {
    if (File::exists("_config/fieldsets/{$fieldset}.yaml")) {
      $meta_raw = file_get_contents("_config/fieldsets/{$fieldset}.yaml");
      $meta = YAML::Parse($meta_raw);

      return $meta;
    }

    return false;
  }

  public static function get_list()
  {
    $sets = array();
    $folder = "_config/fieldsets/*";
    $list = glob($folder);
    if ($list) {
      foreach ($list as $name) {
        if (is_dir($name)) {
        } else {
          $start = strrpos($name, "/")+1;
          $end = strrpos($name, ".");

          $key = substr($name, $start, $end-$start);
          $sets[$key] = self::fetch_fieldset($key);
        }
      }
    }

    return $sets;
  }

}
