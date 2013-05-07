<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">
  <title><?php print Config::getSiteName(); ?> <?php echo Localization::fetch('login')?></title>
  <link rel="stylesheet" href="<?php print $app->config['theme_path'] ?>css/trailhead.css">
  <link rel="icon" href="<?php print Path::tidy(Config::getSiteRoot().'/'.Config::getThemePath())?>img/favicon.png" sizes="16x16" type="img/png" />
</head>
<body id="login">

<div id="login-wrapper">
  <div id="disabled-control-panel" class="center">
    <h1 class="push-down"><?php echo Localization::fetch('offline')?></h1>
  </div>
</div>

<script type="text/javascript" src="<?php print Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path'])?>js/jquery.min.js"></script>
<script>window.jQuery || document.write('<?php print $app->config['theme_path']?>js/jquery.min.js')</script>
<script type="text/javascript" src="<?php print $app->config['theme_path']?>js/trailhead.min.js"></script>
</body>
</html>
