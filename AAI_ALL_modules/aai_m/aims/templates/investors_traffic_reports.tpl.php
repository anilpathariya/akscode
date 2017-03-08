<?php
/**
 * @file
 *   Themes Airport Category Wise Traffic Report.
 */
?>

<?php if (isset($variables['airport_traffic_report'])) : ?>
  <div class = 'aai-report-wrapper'>
    <div><?php print unserialize($variables['airport_traffic_report']); ?></div>
  </div>
<?php endif; ?>