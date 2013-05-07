<div id="status-bar">

  <?php if ($flash['success']): ?>
  <div id="flash-msg" class="success">
    <span class="icon">8</span>
    <span class="msg"><?php print $flash['success']; ?></span>
  </div>
  <?php endif ?>

  <?php if ($flash['error']): ?>
  <div id="flash-msg" class="error">
    <span class="icon">c</span>
    <span class="msg"><?php print $flash['error']; ?></span>
  </div>
  <?php endif ?>

  <span class="icon">n</span> <?php echo Localization::fetch('site_pages')?>
</div>

<div id="screen">

  <?php if (Config::get('_enable_add_top_level_page', true) && $are_fieldsets):?>
    <a href="#" class="btn add-page-btn" data-path="/" data-title="None"><?php echo Localization::fetch('new_top_level_page')?></a>
  <?php endif ?>

  <?php $fieldset = 'page' ?>

  <ul id="page-tree">
    <?php foreach ($pages as $page):?>
    <li class="page">
      <div class="page-wrapper">
        <div class="page-primary">
      <?php
      $base = $page['slug'];

      if ($page['type'] == 'file'): ?>
        <a href="<?php print $app->urlFor('publish')."?path={$page['url']}"; ?>"><span class="page-title"><?php print (isset($page['title']) && $page['title'] <> '') ? $page['title'] : Slug::prettify($page['slug']) ?></span></a>
      <?php elseif ($page['type'] == 'home'): ?>
        <a href="<?php print $app->urlFor('publish')."?path={$page['url']}"; ?>"><span class="page-title"><?php print isset($page['title']) ? $page['title'] : 'Home' ?></span></a>
      <?php else:
        $folder = dirname($page['file_path']);
        ?>
        <a href="<?php print $app->urlFor('publish')."?path={$page['file_path']}"; ?>"><span class="page-title"><?php print (isset($page['title']) && $page['title'] <> '') ? $page['title'] : Slug::prettify($page['slug']) ?></span></a>
      <?php endif ?>

      <?php if (isset($page['has_entries']) && $page['has_entries']): ?>
        <div class="control-entries">
          <span class="icon">n</span>
          <a href="<?php print $app->urlFor('entries')."?path={$base}"; ?>">
            <span class="iphone"><?php echo Localization::fetch('list')?></span>
            <span class="web"><?php echo Localization::fetch('list_entries')?></span>
          </a>
          <em>or</em><a href="<?php print $app->urlFor('publish')."?path={$base}&new=true"; ?>">
            <span class="iphone"><?php echo Localization::fetch('add')?></span>
            <span class="web"><?php echo Localization::fetch('create_entry')?></span>
          </a>
        </div>
      <?php endif ?>
    </div>
        <div class="page-extras">

          <?php if ($page['type'] == 'file'): ?>
            <div class="page-view"><a href="<?php print $page['url'] ?>" class="tip" title="View Page"><span class="icon">M</span></a></div>
          <?php elseif ($page['type'] == 'home'): ?>
            <div class="page-view"><a href="<?php print Config::getSiteRoot(); ?>" class="tip" title="View Page"><span class="icon">M</span></a></div>
          <?php else:
            $folder = dirname($page['file_path']);
          ?>
            <div class="page-view"><a href="<?php print $page['url'] ?>" class="tip" title="View Page"><span class="icon">M</span></a></div>
            <div class="page-add">
              <a href="#" data-path="<?php print $folder; ?>" data-title="<?php print $page['title']?>" class="tip add-page-btn" title="<?php echo Localization::fetch('new_child_page')?>"><span class="icon">j</span></a>
            </div>
            <?php if (Config::get('_enable_delete_page', true)):?>
              <div class="page-delete"><a class="confirm" href="<?php print $app->urlFor('delete_page') . '?path=' . $page['raw_url'] . '&type=' . $page['type']?>" class="tip" title="<?php echo Localization::fetch('delete_page')?>"><span class="icon">u</span></a></div>
            <?php endif ?>
          <?php endif ?>

          <div class="slug-preview">
          <?php if ($page['type'] == 'home'): ?>
            /
          <?php else: print isset($page['url']) ? $page['url'] : $base; endif; ?>
        </div>
        </div>
      </div>
      <?php if (isset($page['children']) && (sizeof($page['children'])> 0)): ?>
        <?php display_folder($app, $page['children'], $page['slug']) ?>
      <?php endif ?>
    </li>
    <?php endforeach ?>
  </ul>
</div>

<?php function display_folder($app, $folder, $base="") {  ?>
<ul class="subpages">
<?php foreach ($folder as $page):?>
<li class="page">
  <div class="page-wrapper">
    <div class="page-primary">

    <!-- PAGE TITLE -->
      <?php if ($page['type'] == 'file'): ?>
        <a href="<?php print $app->urlFor('publish')."?path={$base}/{$page['slug']}"; ?>"><span class="page-title"><?php print isset($page['title']) ? $page['title'] : Statamic_Helper::prettify($page['slug']) ?></span></a>
      <?php else: ?>
        <a href="<?php print $app->urlFor('publish')."?path={$page['file_path']}"; ?>"><span class="page-title"><?php print isset($page['title']) ? $page['title'] : Statamic_Helper::prettify($page['slug']) ?></span></a>

      <?php endif ?>

    <!-- ENTRIES -->
    <?php if (isset($page['has_entries']) && $page['has_entries']): ?>
      <div class="control-entries">
          <span class="icon">n</span>
          <a href="<?php print $app->urlFor('entries')."?path={$base}/{$page['slug']}"; ?>">
            <span class="iphone"><?php echo Localization::fetch('list')?></span>
            <span class="web"><?php echo Localization::fetch('list_entries')?></span>
          </a>
          <em>or</em><a href="<?php print $app->urlFor('publish')."?path={$base}/{$page['slug']}&new=true"; ?>">
            <span class="iphone"><?php echo Localization::fetch('add')?></span>
            <span class="web"><?php echo Localization::fetch('create_entry')?></span>
          </a>
        </div>
    <?php endif ?>
    </div>

    <!-- SLUG & VIEW PAGE LINK -->
    <div class="page-extras">
      <div class="page-view"><a href="<?php print $page['url']?>" class="tip" title="View Page"><span class="icon">M</span></a></div>
      <?php if ($page['type'] != 'file'): ?>
      <div class="page-add"><a href="#" data-path="<?php print $page['raw_url']?>" data-title="<?php print $page['title']?>" class="tip add-page-btn" title="<?php echo Localization::fetch('new_child_page')?>"><span class="icon">j</span></a></div>
      <?php endif; ?>
      <div class="page-delete"><a class="confirm" href="<?php print $app->urlFor('delete_page') . '?path=' . $page['raw_url'] . '&type=' . $page['type']?>" class="tip" title="<?php echo Localization::fetch('delete_page')?>"><span class="icon">u</span></a></div>
      <div class="slug-preview"><?php print isset($page['url']) ? $page['url'] : $base.' /'.$page['slug'] ?></div>
    </div>

  </div>
  <?php if (isset($page['children']) && (sizeof($page['children'])> 0)) {
    display_folder($app, $page['children'], $base."/".$page['slug']);
  } ?>

</li>
<?php endforeach ?>
</ul>
<?php } #end function ?>

<div id="modal-placement"></div>

<script type="text/html" id="fieldset-selector">

<?php if ($are_fieldsets):?>

<div class="modal" id="fieldset-modal" tabindex="1">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3><?php echo Localization::fetch('select_new_page_type')?></h3>
  </div>
  <div class="modal-body">
  <ul>
    <?php foreach ($fieldsets as $fieldset => $fieldset_data): ?>
      <li><a href="<?php print $app->urlFor('publish')?>?path=<%= path %>&new=true&fieldset=<?php print $fieldset ?>&type=none"><?php print $fieldset_data['title'] ?></a></li>
    <?php endforeach; ?>
  </ul>
  <div class="modal-footer">
    <?php echo Localization::fetch('parent')?>: <em><%= parent %></em>
  </div>
</div>

<?php endif ?>

</script>

<script type="text/javascript">
  var selector = _.template($("#fieldset-selector").text());
  $(".add-page-btn").click(function(){
    var html = selector({
      'path': $(this).attr('data-path'),
      'parent': $(this).attr('data-title')
    });
  $("#modal-placement").html(html);
  $('#fieldset-modal').modal();
});
</script>
