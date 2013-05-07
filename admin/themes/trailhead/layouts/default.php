<?php
  $current_user = Statamic_Auth::get_current_user();
  $name = $current_user->get_name();
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">
  <title>Statamic Control Panel</title>
  <link rel="stylesheet" href="<?php print Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path']) ?>css/trailhead.css">
  <link rel="icon" href="<?php print Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path'])?>img/favicon.png" sizes="16x16" type="img/png" />
  <script type="text/javascript" src="<?php print Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path'])?>js/jquery.min.js"></script>
  <script type="text/javascript" src="<?php print Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path'])?>js/underscore.min.js"></script>
  <?php print Hook::run('control_panel', 'add_to_head', 'cumulative') ?>
</head>
<body id="<?php print $route; ?>">
  <div id="wrap">
    <div id="main">
      <div id="control-bar" class="clearfix">
        <ul class="item-count-<?php print CP_helper::nav_count() ?>">
          <?php if (CP_Helper::show_page('dashboard', false)): ?>
            <li id="item-dashboard"><a href="<?php print $app->urlFor("home"); ?>"><span class="icon">1</span><span class="title"><?php echo Localization::fetch('dashboard')?></span></a></li>
          <?php endif ?>

          <?php if (CP_Helper::show_page('pages')): ?>
            <li id="item-pages"><a href="<?php print $app->urlFor("pages"); ?>"><span class="icon">l</span><span class="title"><?php echo Localization::fetch('pages')?></span></a></li>
          <?php endif ?>

          <?php if (CP_Helper::show_page('members')): ?>
            <li id="item-members"><a href="<?php print $app->urlFor("members"); ?>"><span class="icon">,</span><span class="title"><?php echo Localization::fetch('members')?></span></a></li>
          <?php endif ?>

          <?php if (CP_Helper::show_page('account')): ?>
            <li id="item-account"><a href="<?php print $app->urlFor("member")."?name={$name}"; ?>"><span class="icon">.</span><span class="title"><?php echo Localization::fetch('account')?></span></a></li>
          <?php endif ?>

          <?php if (CP_Helper::show_page('system')): ?>
            <li id="item-system"><a href="<?php print $app->urlFor("system"); ?>"><span class="icon">@</span><span class="title"><?php echo Localization::fetch('system')?></span></a></li>
          <?php endif ?>

          <?php if (CP_Helper::show_page('logs')): ?>
            <li id="item-logs"><a href="<?php print $app->urlFor("logs"); ?>"><span class="icon">‘</span><span class="title"><?php echo Localization::fetch('logs')?></span></a></li>
          <?php endif ?>

          <?php if (CP_Helper::show_page('help')): ?>
            <li id="item-help"><a href="http://statamic.com/docs" target="_blank"><span class="icon">K</span><span class="title"><?php echo Localization::fetch('help')?></span></a></li>
          <?php endif ?>

          <?php if (CP_Helper::show_page('view_site')): ?>
            <li id="item-view-site"><a href="<?php print $app->config['_site_root']; ?>"><span class="icon">M</span><span class="title"><?php echo Localization::fetch('view_site')?></span></a></li>
          <?php endif ?>

          <?php if (CP_Helper::show_page('logout')): ?>
            <li id="item-logout"><a href="<?php print $app->urlFor("logout"); ?>"><span class="icon">V</span><span class="title"><?php echo Localization::fetch('logout')?></span></a></li>
          <?php endif ?>
        </ul>
      </div>
      <?php print $_html; ?>
  </div>
</div>
<div id="footer">
  &copy; <?php print date("Y")?> <a href="http://statamic.com">Statamic</a> v<?php print STATAMIC_VERSION ?> (build <?php print STATAMIC_REFINERY_BUILD ?>)
  <span id="version-check">
  <?php if (Pattern::isValidUUID($app->config['_license_key'])): ?>

    <?php if (isset($app->config['latest_version']) && $app->config['latest_version'] <> '' && STATAMIC_VERSION < $app->config['latest_version']): ?>
      <a href="https://store.statamic.com/account"><?php echo Localization::fetch('update_available')?>: v<?php print $app->config['latest_version']; ?></a>
    <?php else: ?>
      <?php echo Localization::fetch('up_to_date')?>
    <?php endif ?>
  <?php else: ?>
    <a href="http://store.statamic.com">You are using an UNLICENSED copy of Statamic. Please purchase and enter a valid license. Thanks!</a>
  <?php endif ?>
  </span>
</div>
<script type="text/javascript" src="<?php print Path::tidy(Config::getSiteRoot().'/'.$app->config['theme_path'])?>js/trailhead.min.js"></script>
<?php print Hook::run('control_panel', 'add_to_foot', 'cumulative') ?>
</body>
</html>
