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

  <span class="icon">n</span> <?php echo Localization::fetch('entries')?> <em><?php echo Localization::fetch('in')?></em> <?php print $path; ?>
</div>

<div id="screen">

  <a href="<?php print $app->urlFor('publish')."?path={$path}&new=true"; ?>" class="btn">New Entry</a>
  <table class="table-list sortable">
    <thead>
      <tr>
        <th><?php echo Localization::fetch('title')?></th>
        <th><?php echo Localization::fetch('slug')?></th>
        <th><?php echo Localization::fetch('status')?></th>
        <?php if ($type == 'date'): ?>
          <th><?php echo Localization::fetch('date')?></th>
        <?php elseif ($type == 'number'): ?>
          <th><?php echo Localization::fetch('number')?></th>
        <?php endif; ?>
        <th style="width:26px;"><?php echo Localization::fetch('view')?></th>
        <th style="width:26px;"><?php echo Localization::fetch('delete')?></th>
      </tr>
    </thead>
    <tbody>

    <?php foreach ($entries as $slug => $entry): ?>
    <?php $status = isset($entry['status']) ? $entry['status'] : 'live'; ?>
      <tr>
        <td class="title"><a href="<?php print $app->urlFor('publish')?>?path=<?php echo Path::tidy($path.'/')?><?php echo $slug ?>"><?php print (isset($entry['title']) && $entry['title'] <> '') ? $entry['title'] : Slug::prettify($entry['slug']) ?></a></td>
        <td class="slug"><?php print $entry['slug'] ?></td>
        <td class="status status-<?php print $status ?>"><span class="icon">}</span><?php print ucwords($status) ?></td>
        <?php if ($type == 'date'): ?>
          <td><span class="hidden"><?php print date("Y-m-d", @$entry['datestamp']) ?></span><?php print @$entry['date']?></td>
        <?php elseif ($type == 'number'): ?>
          <td><?php print $entry['numeric'] ?></td>
        <?php endif ?>
        <td class="action"><div class="page-view"><a href="<?php print $entry['url'] ?>" class="tip" title="<?php echo Localization::fetch('view_entry')?>"><span class="icon">M</span></a></div></td>
        <td class="action"><a class="confirm tip" href="<?php print $app->urlFor('delete_entry')."?path={$path}/{$slug}"; ?>" title="<?php echo Localization::fetch('delete_entry')?>"><span class="icon">u</span></a></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>

</div>
