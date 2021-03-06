<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">
  <title><?php echo Config::getSiteName(); ?> <?php echo Localization::fetch('login')?></title>
  <link rel="stylesheet" href="<?php echo Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path']) ?>css/trailhead.css">
  <link rel="icon" href="<?php echo Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path'])?>img/favicon.png" sizes="16x16" type="img/png" />
  <?php echo Hook::run('control_panel', 'add_to_head', 'cumulative') ?>
</head>
<body id="login">
<?php echo $_html; ?>
<script type="text/javascript" src="<?php echo Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path'])?>js/jquery.min.js"></script>
<script>window.jQuery || document.write('<?php echo Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path']) ?>js/jquery.min.js')</script>
<script type="text/javascript" src="<?php echo Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path']) ?>js/trailhead.min.js"></script>
<?php echo Hook::run('control_panel', 'add_to_foot', 'cumulative') ?>
</body>
</html>
