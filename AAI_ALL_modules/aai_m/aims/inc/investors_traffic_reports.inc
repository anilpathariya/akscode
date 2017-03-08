<?php
/**
 * @file
 *   file to list type of reports available under investors.
 */

/**
 * Function to return themed list of investor reports.
 */
function investors_traffic_reports() {
  drupal_set_title(t('AIRPORT CATEGORY WISE TRAFFIC SUMMARY'));
  return theme('investors_traffic_reports');
}
