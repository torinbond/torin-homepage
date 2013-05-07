<?php
class Plugin_get_content extends Plugin
{
  public function index()
  {
    $from = $this->fetchParam('from', false); // defaults to null

    if ($from) {
      $data = null;
      $content_root = Config::getContentRoot();
      $content_type = Config::getContentType();

      if (File::exists("{$content_root}/{$from}.{$content_type}") || is_dir("{$content_root}/{$from}")) {
        // endpoint or folder exists!
      } else {
        $from = Path::resolve($from);
      }

      if (File::exists("{$content_root}/{$from}.{$content_type}")) {
        // @todo: Load Post if a date/numerical entry, else page
        $page     = basename($from);
        $folder   = substr($from, 0, (-1*strlen($page))-1);

        $data = Statamic::get_content_meta($page, $folder);
      } elseif (is_dir("{$content_root}/{$from}")) {
        $data = Statamic::get_content_meta("page", $from);
      }

      if ($data) {
        return $data;
      }
    }

    return "";
  }

}
