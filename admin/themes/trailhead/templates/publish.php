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

  <span class="icon">n</span>
  <?php if (isset($new)): ?><?php echo Localization::fetch('new')?> <?php else: ?><?php echo Localization::fetch('editing')?><?php endif ?>
  <?php if ($type == 'none' && $original_slug != 'page'):?><?php echo Localization::fetch('page')?>: <?php print $full_slug; ?>
  <?php elseif ($type == 'none'): ?> <?php echo Localization::fetch('page')?>: <?php print $full_slug; ?>
  <?php else: ?><?php echo Localization::fetch('entry')?> <em><?php echo Localization::fetch('in')?></em> <span class="slug"><?php print $full_slug; ?></span><?php endif ?>

  </span>
</div>

<div id="screen">

  <form enctype="multipart/form-data" method="post" action="publish?path=<?php print $path ?>">

    <?php print Hook::run('control_panel', 'add_to_publish_form', 'cumulative') ?>

    <input type="hidden" name="page[full_slug]" value="<?php print $full_slug; ?>">
    <input type="hidden" name="page[type]" value="<?php print $type ?>" />
    <input type="hidden" name="page[folder]" value="<?php print $folder ?>" />
    <input type="hidden" name="page[original_slug]" value="<?php print $original_slug ?>" />
    <input type="hidden" name="page[original_datestamp]" value="<?php print $original_datestamp ?>" />
    <input type="hidden" name="page[original_timestamp]" value="<?php print $original_timestamp ?>" />
    <input type="hidden" name="page[original_numeric]" value="<?php print $original_numeric ?>" />

    <?php if (isset($new)): ?>
      <input type="hidden" name="page[new]" value="1" />
    <?php endif ?>

    <?php if (isset($fieldset)): ?>
      <?php if (is_array($fieldset)):?>
        <?php foreach($fieldset as $key => $set): ?>
          <input type="hidden" name="page[fieldset][<?php print $key ?>]" value="<?php print $set; ?>" />
        <?php endforeach; ?>
      <?php else: ?>
        <input type="hidden" name="page[fieldset]" value="<?php print $fieldset; ?>" />
      <?php endif; ?>
    <?php endif ?>

      <?php if (isset($errors) && (sizeof($errors) > 0)): ?>
      <div class="panel topo">
        <p>Sorry an error prevented the form submission</p>
        <ul class="errors">
          <?php foreach ($errors as $field => $error): ?>
          <li><span class="field"><?php print $field ?></span> <span class="error"><?php print $error ?></span></li>
          <?php endforeach ?>
        </ul>
      </div>
      <?php endif ?>

      <?php
      // grab default value and instructions for title
      $title_details = array(
        "instructions" => array(
          "above" => null,
          "below" => null
          )
      );

      if (isset($fields) && is_array($fields) && isset($fields['title'])) {
//        if (isset($fields['title']['default'])) {
//          $title_details["default"] = $fields['title']['default'];
//        }
        if (isset($fields['title']['instructions'])) {
          if (!is_array($fields['title']['instructions'])) {
            $title_details["instructions"]["above"] = $fields['title']['instructions'];
          } else {
            if (isset($fields['title']['instructions']['above'])) {
              $title_details["instructions"]["above"] = $fields['title']['instructions']['above'];
            }
            if (isset($fields['title']['instructions']['below'])) {
              $title_details["instructions"]["below"] = $fields['title']['instructions']['below'];
            }
          }
        }
      }
      ?>

      <div class="input-block input-text required">
        <label for="publish-title">Title</label>
        <?php
        if ($title_details['instructions']['above']) {
          echo "<small>{$title_details['instructions']['above']}</small>";
        }
        ?>
        <input name="page[yaml][title]" class="text text-large" tabindex="<?php print tabindex(); ?>" placeholder="Enter a title..." id="publish-title" value="<?php print htmlspecialchars($title); ?>" />
        <?php
        if ($title_details['instructions']['below']) {
          echo "<small>{$title_details['instructions']['below']}</small>";
        }
        ?>
      </div>

      <?php if ($slug != '/'): ?>
      <div class="input-block input-text required">
        <label for="publish-slug">Slug</label>
        <input type="text" id="publish-slug" tabindex="<?php print tabindex(); ?>" class="text<?php if (isset($new)): ?> auto-slug <?php endif ?>" name="page[meta][slug]" value="<?php print $slug ?>" />
      </div>
      <?php else: ?>
        <input type="hidden" id="publish-slug" tabindex="<?php print tabindex(); ?>" name="page[meta][slug]" value="<?php print $slug ?>" />
      <?php endif ?>

      <?php if ($type == 'date'): ?>
      <div class="input-block input-date date required" data-date="<?php print date("Y-m-d", $datestamp) ?>" data-date-format="yyyy-mm-dd">
        <label>Publish Date</label>
        <span class="icon">P</span>
        <input name="page[meta][publish-date]" tabindex="<?php print tabindex(); ?>" type="text" id="publish-date"  value="<?php print date("Y-m-d", $datestamp) ?>" class="datepicker" />
      </div>

      <?php if (Config::getEntryTimestamps()) { ?>
      <div class="input-block input-time time required" data-date="<?php print date("h:i a", $timestamp) ?>" data-date-format="h:i a">
        <label>Publish Time</label>
        <span class="icon">N</span>
        <input name="page[meta][publish-time]" tabindex="<?php print tabindex(); ?>" type="text" id="publish-time" class="timepicker" value="<?php print date("h:i a", $timestamp) ?>" />
      </div>
      <?php } ?>

      <?php elseif ($type == 'number'): ?>
      <div class="input-block input-text input-number" id="publish-order-number">
        <label for="publish-order-number">Order Number</label>
        <input name="page[meta][publish-numeric]" type="text" class="text date input-4char"  tabindex="<?php print tabindex(); ?>" maxlength="4" id="publish-order-number" value="<?php print $numeric; ?>" />
      </div>
      <?php endif ?>

      <?php
      if (isset($fields) && count($fields) > 0):
        foreach ($fields as $key => $value):

          if ($key === 'content' || $key === 'title')
            continue;

          $fieldtype = isset($value['type']) ? $value['type'] : 'text';

          $val = "";
          if (isset($$key)) {
            $val = $$key;
          } elseif (isset($value['default'])) {
            $val = $value['default'];
          }

          # special rule for Status
          if ($fieldtype == 'status' && isset($status)) {
            $val = $status;
          }

          if ( ! isset($value['display'])) {
            $value['display'] = Slug::prettify($key);
          }

        ?>

          <div class="input-block input-<?php print $fieldtype?> <?php if ( isset($value['required']) && $value['required'] === TRUE) print ' required'?>">
            <?php ## FIELDTYPE API ## ?>
            <?php print Fieldtype::render_fieldtype($fieldtype, $key, $value, $val, tabindex());?>
          </div>

        <?php endforeach ?>
      <?php endif ?>

      <?php
        # CONTENT FIELD SETTINGS
        $label = 'Content';
        $fieldtype = 'markitup';
        $fieldname = 'content';
        $field_config = array();
        $val = $content_raw;
        $instructions = '';
        $required = '';

        if (isset($fields) && isset($fields['content'])) {
          $field_config = $fields['content'];
          $label = isset($field_config['display']) ? $field_config['display'] : "Content";
          $fieldtype = $field_config['type'];
          $required = isset($field_config['required']) && $field_config['required'] === true ? 'required' : '';
          $instructions = isset($value['instructions']) ? "<div class='instructions'>{$value['instructions']}</div>" : '';
        }

      ?>

      <div class="input-block input-<?php print $fieldtype?> <?php print $required ?>">
        <?php ## FIELDTYPE API ## ?>
        <?php print Fieldtype::render_fieldtype($fieldtype, $fieldname, $field_config, $val, tabindex(), '');?>
      </div>

      <div id="publish-action" class="footer-controls">
        <input type="submit" class="btn btn-submit" value="Save &amp; Publish" id="publish-submit">
      </div>

  </form>
</div>

<?php

function tabindex()
{
  static $count = 1;

  return $count++;
}

?>