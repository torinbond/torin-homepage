<div id="screen">
  <h1><?php echo Localization::fetch('error')?></h1>

  <?php if ($flash['error']): ?>
  <div id="flash-msg-still" class="error">
    <span class="icon">c</span>
    <span class="msg"><?php print $flash['error']; ?></p>
  </div>
  <?php endif ?>

</div>
