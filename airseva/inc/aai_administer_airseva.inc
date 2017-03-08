<?php
/**
 * @File
 *   Inc file for airseva configurations.
 */

/**
 * Function for airseva configuration form.
 */
function airseva_administer_configuration() {
  // Miscellaneous configuration
  $lnks = "<ul><li>";
  $lnks .= l(t('Manage Complaint Related to'), 'admin/structure/taxonomy/complaint_related_to');
  $lnks .= "</li><li>";
  $lnks .= l(t('Manage Complaint Manager'), 'administer/structure/complaint/complaint_manager');
  $lnks .= "</li><li>";
  $lnks .= l(t('Manage Complaint Type'), 'administer/structure/complaint/category');
  $lnks .= "</li><li>";
  $lnks .= l(t('Manage Source'), 'administer/structure/complaint/source');
  $lnks .= "</li><li>";
  $lnks .= l(t('Manage Priority'), 'administer/structure/priority');
  $lnks .= "</li>";
  $lnks .= "</ul>";

  $form["airseva_misc_fs"] = array(
    '#type' => 'fieldset',
    '#title' => t('Miscellaneous Configurations'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form["airseva_misc_fs"]['airseva_misc'] = array(
    '#type' => 'markup',
    '#suffix' => $lnks,
  );

  // fieldset for sms api details
  $form["airseva_fs_sms_api"] = array(
    '#type' => 'fieldset',
    '#title' => t('SMS API Configuration'),
    '#collapsible' => true,
    '#collapsed' => TRUE,
  );
  $form["airseva_fs_sms_api"]['airseva_sms_enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable SMS'),
    '#description' => t('Mark to enable SMS notifications.'),
    '#size' => 40,
    '#default_value' => variable_get('airseva_sms_enabled', FALSE),
  );
  $form["airseva_fs_sms_api"]['airseva_sms_uri'] = array(
    '#type' => 'textfield',
    '#title' => t('Url'),
    '#description' => t('Please provide sms api url'),
    '#size' => 40,
    '#default_value' => variable_get('airseva_sms_uri', ''),
  );
  $form["airseva_fs_sms_api"]['airseva_sms_key'] = array(
    '#type' => 'textfield',
    '#title' => t('Key'),
    '#description' => t('Please add api key if any'),
    '#size' => 40,
    '#default_value' => variable_get('airseva_sms_key', ''),
  );

  // generate tokens element
  $tokens_ary = unserialize(variable_get('airseva_tokens'));
  $tokens = "<ul class = 'airseva-tokens'>";
  foreach($tokens_ary as $token) {
    $tokens .= "<li>$token</li>";
  }
  $tokens .= "</ul>";

 // fieldset for sms template
  $form["airseva_fs_sms"] = array(
    '#type' => 'fieldset',
    '#title' => t('SMS Templates'),
    '#collapsible' => true,
    '#collapsed' => TRUE,
  );
  $form["airseva_fs_sms"]['airseva_sms_ack_bdy'] = array(
    '#type' => 'textarea',
    '#title' => t('SMS Acknowledgement Template'),
    '#description' => t('Max characters allowed 60<br/> See bottom of form for available tokens'),
    '#size' => 40,
    '#attributes' => array('maxlength' => 60),
    '#default_value' => variable_get('airseva_sms_ack_bdy', ''),
  );
  $form["airseva_fs_sms"]['airseva_sms_res_bdy'] = array(
    '#type' => 'textarea',
    '#title' => t('SMS Response Template'),
    '#description' => t('Max characters allowed 60<br/> See bottom of form for available tokens'),
    '#size' => 40,
    '#attributes' => array('maxlength' => 60),
    '#default_value' => variable_get('airseva_sms_res_bdy', ''),
  );
  $form["airseva_fs_sms"]['airseva_sms_rem_bdy'] = array(
    '#type' => 'textarea',
    '#title' => t('SMS Reminder Template'),
    '#description' => t('Max characters allowed 60<br/> See bottom of form for available tokens'),
    '#size' => 40,
    '#attributes' => array('maxlength' => 60),
    '#default_value' => variable_get('airseva_sms_rem_bdy', ''),
  );

  // show available tokens
  $form["airseva_fs_sms"]['tokens_fs'] = array(
    '#type' => 'fieldset',
    '#title' => t('Tokens'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form["airseva_fs_sms"]['tokens_fs']['airseva_tokens_sms'] = array(
    '#type' => 'markup',
    '#suffix' => $tokens,
  );


 // fieldset for  email template
  $form["airseva_email"] = array(
    '#type' => 'fieldset',
    '#title' => t('Email Templates'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  // get mail from detail
  $form["airseva_email"]['airseva_email_from'] = array(
    '#type' => 'textfield',
    '#title' => t('Email From'),
    '#description' => t('Please provide sender email'),
    '#size' => 90,
    '#default_value' => variable_get('airseva_email_from', ''),
  );

  // fieldset for acknowledgement email template
  $form["airseva_email"]["airseva_fs_ack"] = array(
    '#type' => 'fieldset',
    '#title' => t('Acknowledgement'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form["airseva_email"]["airseva_fs_ack"]['airseva_email_ack_sub'] = array(
    '#type' => 'textfield',
    '#title' => t('Email Subject'),
    '#description' => t('Please provide Subject Of Email'),
    '#size' => 40,
    '#default_value' => variable_get('airseva_email_ack_sub', ''),
  );
  $form["airseva_email"]["airseva_fs_ack"]['airseva_email_ack_bdy'] = array(
    '#type' => 'textarea',
    '#title' => t('Acknowledgement Template'),
    '#description' => t('Max characters allowed 500<br/> See bottom of form for available tokens'),
    '#size' => 40,
    '#attributes' => array('maxlength' => 500),
    '#default_value' => variable_get('airseva_email_ack_bdy', ''),
  );

  // fieldset for Response email template
  $form["airseva_email"]["airseva_fs_email"] = array(
    '#type' => 'fieldset',
    '#title' => t('Response'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form["airseva_email"]["airseva_fs_email"]['airseva_email_res_sub'] = array(
    '#type' => 'textfield',
    '#title' => t('Email Subject'),
    '#description' => t('Please provide Subject Of Email'),
    '#size' => 40,
    '#default_value' => variable_get('airseva_email_res_sub', ''),
  );
  $form["airseva_email"]["airseva_fs_email"]['airseva_email_res_bdy'] = array(
    '#type' => 'textarea',
    '#title' => t('Response Template'),
    '#description' => t('Max characters allowed 500<br/> See bottom of form for available tokens'),
    '#size' => 40,
    '#attributes' => array('maxlength' => 500),
    '#default_value' => variable_get('airseva_email_res_bdy', ''),
  );

// fieldset for Reminder email template
  $form["airseva_email"]["airseva_fs_rem"] = array(
    '#type' => 'fieldset',
    '#title' => t('Reminder'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form["airseva_email"]["airseva_fs_rem"]['airseva_email_rem_sub'] = array(
    '#type' => 'textfield',
    '#title' => t('Email Subject'),
    '#description' => t('Please provide Subject Of Email'),
    '#size' => 40,
    '#default_value' => variable_get('airseva_email_rem_sub', ''),
  );
  $form["airseva_email"]["airseva_fs_rem"]['airseva_email_rem_bdy'] = array(
    '#type' => 'textarea',
    '#title' => t('Reminder Template'),
    '#description' => t('Max characters allowed 500<br/> See bottom of form for available tokens'),
    '#size' => 40,
    '#attributes' => array('maxlength' => 500),
    '#default_value' => variable_get('airseva_email_rem_bdy', ''),
  );

  // show available tokens
  $form["airseva_email"]['tokens_fs'] = array(
    '#type' => 'fieldset',
    '#title' => t('Tokens'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form["airseva_email"]['tokens_fs']['airseva_tokens_email'] = array(
    '#type' => 'markup',
    '#suffix' => $tokens,
  );

  // add configuration field for notification frequency
  $form["airseva_notice"] = array(
    '#type' => 'fieldset',
    '#title' => t('Notification'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form["airseva_notice"]['airseva_notice_frequency'] = array(
    '#type' => 'textfield',
    '#title' => t('Notification Limit'),
    '#description' => t('Defines the maximum number of notification that can be sent to a user. <br/> 0 for no limit'),
    '#default_value' => variable_get('airseva_notice_frequency', 0),
  );

  return system_settings_form($form);
}