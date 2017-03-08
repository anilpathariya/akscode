<?php
/**
 * @file
 * Page callbacks for the complaint module.
 */

/**
 * Form constructor for the site-wide complaint form.
 *
 * @see complaint_site_form_validate()
 * @see complaint_site_form_submit()
 */
 
drupal_add_css(drupal_get_path('module', 'complaint') . '/complaint_site.css', array('group' => CSS_THEME));
drupal_add_js(drupal_get_path('module', 'complaint') . '/complaint.js');

function complaint_site_form($form, &$form_state) {
   
   $title_grievance_related = '';
   if(isset($form_state['values']['assigned_to'])) { 
		$assigned_to = $form_state['values']['assigned_to'];
		if($assigned_to==1) {
			$title_grievance_related = "Kindly Choose Airport";
		}
		if($assigned_to==2) {
			$title_grievance_related = "Kindly Choose Airlines";
		}
		
   }else{
			$assigned_to='';
			$title_grievance_related = "";
		}
   
   $assigned_to = isset($form_state['values']['assigned_to']) ? $form_state['values']['assigned_to'] : '';
   $_SESSION['complaint_number'] = NULL;
  
  // Get an array of the sources.
  $sources = db_select('aai_complaint_source', 'cs')
    ->addTag('translatable')
	->condition('status', 1,'=')
    ->fields('cs', array('complain_source_id', 'complain_source'))
    ->orderBy('complain_source')
    ->execute()
    ->fetchAllKeyed();	
   
  $form['#attributes']['id'] = 'complaint-site-form';
  if(isset($_SESSION['aaiuser_complaint_form_entry']) && $_SESSION['aaiuser_complaint_form_entry']=='Y'){
	   $form['complain_source_id'] = array(
		'#type' => 'select',
		'#title' => t('Source'),
		'#options' => $sources,
		'#required' => TRUE,
		);
    }
  
  if(isset($_SESSION['aaiuser_complaint_form_entry']) && $_SESSION['aaiuser_complaint_form_entry']=='Y'){
	   $form['source_document_number'] = array(
		'#type' => 'textfield',
		'#title' => t('Document/File/CPGram No'),
		'#maxlength' => 255,
		'#required' => TRUE,
	 );
   }
	
  if(isset($_SESSION['aaiuser_complaint_form_entry']) && $_SESSION['aaiuser_complaint_form_entry']=='Y'){
	   $form['complaint_received_on'] = array(
		'#type' => 'date_popup', 
		'#title' => t('Received ON'),
		'#date_format' => 'Y-m-d',
		'#date_label_position' => 'within',
		'#date_increment' => 15, 
		'#date_year_range' => '0:+2', 
		'#required' => TRUE,
	   );
    }
	
	    $form['assigned_to'] = array(
		'#type' => 'select',
		'#title' => t('Grievance Related To'),
		'#options' =>  array (
		  '' => t('Select'),
		  '1' => t('Airport'),
		  '2' => t('Airlines'),
		  '3' => t('DGCA (Regulator)'),
		  '4' => t('Customs'),
		  '5' => t('Security'),
		  '6' => t('Immigration'),
		  '7' => t('BCAS'),
		),
		'#required' => TRUE,
		'#ajax' => array(
			'event' => 'change',
			'callback' => 'grievance_related_ajax_callback',
			'wrapper' => array('grievance_related_replace','category_replace'),
		),
	);
    
    $form['assigned_to_tid'] = array(
		'#type' => 'select',
		'#title' => t($title_grievance_related),
		'#options' => grievance_for_ajax($assigned_to),
		'#required' => FALSE,
		'#attributes' => array('class' => array('grievance_for')),
		'#prefix' => '<div id="grievance_related_replace">',
		'#suffix' => '</div>',
    );

    $form['complaint_type'] = array(
		'#type' => 'select',
		'#title' => t('Grievance type'),
		'#options' => get_ajax_type($assigned_to),
		'#required' => FALSE,
		'#prefix' => '<div id="category_replace">',
		'#suffix' => '</div>',
    );
  
	$form['complainer_name'] = array(
		'#type' => 'textfield',
		'#title' => t('Name'),
		'#maxlength' => 255,
		'#required' => TRUE,
    );
	
	$form['complainer_email'] = array(
		'#type' => 'textfield',
		'#title' => t('Email'),
		'#maxlength' => 255,
		'#required' => TRUE,
    );
	
	$form['complainer_contact_no'] = array(
		'#type' => 'textfield',
		'#title' => t('Contact Number'),
		'#maxlength' => 255,
		'#required' => FALSE,
    );
    
    $form['date_of_journey'] = array(
		'#type' => 'date_popup', 
		'#title' => t('Date Of Journey'),
		'#date_format' => 'Y-m-d',
		'#date_label_position' => 'within',
		'#date_increment' => 15, 
		'#date_year_range' => '0:+2', 
		'#required' => FALSE,
    );

    $form['pnr'] = array(
		'#type' => 'textfield',
		'#title' => t('PNR'),
		'#maxlength' => 255,
		'#required' => FALSE,
    );
 
	$form['complaint_details'] = array(
		'#type' => 'textarea',
		'#title' => t('Report Your Grievance/Issue'),
		'#maxlength' => 255,
		'#required' => TRUE,
	);
  
	$form['attachment1'] = array(
		'#type' => 'file',
		'#title' => t('Attach File1 (If any)'),
		'#required' => FALSE,    
	);
	
	$form['attachment2'] = array(
		'#type' => 'file',
		'#title' => t('Attach File2 (If any)'),
		'#required' => FALSE,    
	);
  
	$form['actions'] = array('#type' => 'actions');
	$form['actions']['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit'),
	);
    return $form;
}


function get_ajax_type($assigned_to){
$type_with_select = array("0"=>"--Select--");
if($assigned_to==1 || $assigned_to==2){
	 $type_result = db_select('aai_complaint_category', 'cc')
	->addTag('translatable')
	->fields('cc', array('category_id', 'category_name'))
	->condition('category_related_to', $assigned_to,'=')   
	->orderBy('category_name')
	->execute();
	while($type = $type_result->fetchAssoc()){
			$types[$type['category_id']] = $type['category_name'];
	}
		return $type_with_select+$types;
		
	}else{ 
		return $type_with_select;
	}
}

function grievance_for_ajax($assigned_to){

	if($assigned_to==1){
		$airports_with_select = array(""=>"--Select--");
		$airports_result = db_select('taxonomy_term_data', 'ap')
		->addTag('translatable')
		->fields('ap', array('tid', 'name'))
		->condition('vid', getAaiAirportVariable('aai_airport_vid'),'=')   
		->orderBy('name')->execute();
		while($airport = $airports_result->fetchAssoc()){
			$airports[$airport['tid']] = $airport['name'];
		}
		
		return $airports_with_select+$airports;
		
	}
	elseif($assigned_to==2){
		$airlines_with_select = array(""=>"--Select--");
		$airlines_result = db_select('taxonomy_term_data', 'ap')
		->addTag('translatable')
		->fields('ap', array('tid', 'name'))
		->condition('vid', getAaiAirportVariable('aai_airlines_vid'),'=')   
		->orderBy('name')
		->execute();
		while($airline = $airlines_result->fetchAssoc()){
			$airlines[$airline['tid']] = $airline['name'];
		}
		
		return $airlines_with_select+$airlines;
	}
	elseif($assigned_to==3){
		return $airports = db_select('taxonomy_term_data', 'ap')
		->addTag('translatable')
		->fields('ap', array('tid', 'name'))
		->condition('vid', getAaiAirportVariable('aai_dgca_vid'),'=')   
		->orderBy('name')
		->execute()
		->fetchAllKeyed();
	}
	elseif($assigned_to==4){
		return $airports = db_select('taxonomy_term_data', 'ap')
		->addTag('translatable')
		->fields('ap', array('tid', 'name'))
		->condition('vid', getAaiAirportVariable('aai_custom_vid'),'=')   
		->orderBy('name')
		->execute()
		->fetchAllKeyed();
	}
	elseif($assigned_to==5){
		return $airports = db_select('taxonomy_term_data', 'ap')
		->addTag('translatable')
		->fields('ap', array('tid', 'name'))
		->condition('vid', getAaiAirportVariable('aai_security_vid'),'=')   
		->orderBy('name')
		->execute()
		->fetchAllKeyed();
	}
	elseif($assigned_to==6){
		return $airports = db_select('taxonomy_term_data', 'ap')
		->addTag('translatable')
		->fields('ap', array('tid', 'name'))
		->condition('vid', getAaiAirportVariable('aai_immigration_vid'),'=')   
		->orderBy('name')
		->execute()
		->fetchAllKeyed();
	}
	else{
		return array();
	}
}


function grievance_related_ajax_callback($form, &$form_state){
	return array(
    '#type' => 'ajax',
    '#commands' => array(
      ajax_command_replace("#grievance_related_replace", render($form['assigned_to_tid'])),
      ajax_command_replace("#category_replace", render($form['complaint_type']))
    )
  );

}


/**
 * Form validation handler for complaint_site_form().
 *
 * @see complaint_site_form_submit()
 */
function complaint_site_form_validate($form, &$form_state) {

	//echo '<pre>';
	//print_r($form_state['values']); die;
	
	if(!validateInput($form_state['values']['complainer_name'])){
		form_set_error('complainer_name', t('You must enter a valid name.'));
	}
	
	if (!valid_email_address($form_state['values']['complainer_email'])) {
		form_set_error('complainer_email', t('You must enter a valid e-mail address.'));
	}
		
	if(!validateInput($form_state['values']['assigned_to']) && is_numeric($form_state['values']['assigned_to']) == FALSE){
		form_set_error('assigned_to', t('You must enter a valid grievance related to information.'));
	}
	
	if(!validateInput($form_state['values']['date_of_journey']) && !empty($form_state['values']['date_of_journey'])){
		form_set_error('nationality', t('You must enter a valid date of journey.'));
	}
		
	
	if($form_state['values']['assigned_to']==1 || $form_state['values']['assigned_to']==2){
		
		if($form_state['values']['assigned_to_tid']==''){
			form_set_error('assigned_to_tid', t('You must enter a airport/airlines.'));
		}
		elseif(!validateInput($form_state['values']['assigned_to_tid']) && is_numeric($form_state['values']['assigned_to_tid']) == FALSE){
			form_set_error('assigned_to_tid', t('You must enter a airport/airlines.'));
		}
		
		if($form_state['values']['complaint_type']==''){
			form_set_error('complaint_type', t('You must enter a complaint type.'));
		}
		elseif(!validateInput($form_state['values']['complaint_type']) && is_numeric($form_state['values']['complaint_type']) == FALSE){
			form_set_error('complaint_type', t('You must enter a complaint type.'));
		}
		
	}
	
	if(count(preg_split('/\s+/', trim($form_state['values']['complaint_details'])))>100) {
		form_set_error('complaint_details', t('Maximum 100 words are allowed for grievance.'));
	
	}
	
	$attachment = file_save_upload('attachment', array(
			'file_validate_extensions' => array('png gif jpg jpeg pdf'),
		)
	);

	if ($attachment) {
		if ($attachment = file_move($attachment, 'private://complaint')) {
			$form_state['values']['attachment'] = basename($attachment->uri);
		}
		else {
			form_set_error('attachment', t('Failed to write the uploaded file'));
		}
	}  
}

/**
 * Form submission handler for complaint_site_form().
 *
 * @see complaint_site_form_validate()
 */
function complaint_site_form_submit($form, &$form_state) {

   
	$year = date("Y");
	$month = date("m");
	$day = date("d");
  
	$lastinsertid = db_insert('aai_complaint')
	->fields(array(
		'assigned_to_tid' => filterComplaintInput($form_state['values']['assigned_to_tid']),
		'assigned_to' => filterComplaintInput($form_state['values']['assigned_to']),
		'complaint_type' => filterComplaintInput($form_state['values']['complaint_type']),
		'complainer_name' => filterComplaintInput($form_state['values']['complainer_name']),
		'complainer_email' => filterComplaintInput($form_state['values']['complainer_email']),
		'complainer_contact_no' => filterComplaintInput($form_state['values']['complainer_contact_no']),
		'complain_source_id' => isset($form_state['values']['complain_source_id'])?filterComplaintInput($form_state['values']['complain_source_id']):NULL,
		'document_number' => isset($form_state['values']['document_number'])?filterComplaintInput($form_state['values']['document_number']):NULL,
		'date_of_journey' => date("Y-m-d",strtotime(filterComplaintInput($form_state['values']['date_of_journey']))),
		'complaint_details' => filterComplaintInput($form_state['values']['complaint_details']),
		'pnr' => filterComplaintInput($form_state['values']['pnr']),
		'complaint_status_id' => 1,
		'complaint_received_on' => isset($form_state['values']['complaint_received_on'])?filterComplaintInput($form_state['values']['complaint_received_on']):date("Y-m-d H:i:s"),
		'attachment' =>  filterComplaintInput($form_state['values']['attachment']),
		)
	)->execute();
  
  $ticket_number = $year.$month.$day.add_leading_zero($lastinsertid);
  db_update('aai_complaint') 
  ->fields(array(
    'complaint_ticket_number' => $ticket_number,
  ))
  ->condition('complaint_id', $lastinsertid, '=')
  ->execute();
  
  db_insert('aai_complaint_history')
  ->fields(array(
    'complaint_id' => $lastinsertid,
	'assigned_to_tid' => filterComplaintInput($form_state['values']['assigned_to_tid']),
	'assigned_to' => filterComplaintInput($form_state['values']['assigned_to']),
	'complaint_status_id' => 1,
  ))
  ->execute();
  drupal_set_message(t("Thanks for registering complaint. Your complaint number is : $ticket_number We shall revert back to you as soon as possible! We have sent an email with complaint number to track the status on this website."));
  
 }

function add_leading_zero($value, $threshold = 5){
	return sprintf('%0' . $threshold . 's', $value);
}

function complaint_track_login($form, &$form_state){

 $_SESSION['complaint_number'] = NULL;
 $form['complaint_number'] = array(
    '#type' => 'password',
    '#title' => t('Complaint No'),
    '#maxlength' => 255,
    '#required' => TRUE,
  );
  
  $form['actions'] = array('#type' => 'actions','#attributes'=> array('style' => 'padding-top:25px; margin-right:200px;float:right'),);
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );
  return $form;
}

function complaint_track_login_validate($form, &$form_state){
	
	if(is_numeric($form_state['values']['complaint_number'])==FALSE ){
		form_set_error('complaint_number', t('You must enter a valid complaint number.'));
	}
}

function complaint_track_login_submit($form, &$form_state){

	$complaint_number = filterComplaintInput($form_state['values']['complaint_number']);
	$complaint = db_select('aai_complaint', 'c')
	->fields('c', array('complaint_ticket_number'))
	->condition('complaint_ticket_number', $complaint_number,'=')
	->execute()
	->fetchAssoc();
	if ($complaint['complaint_ticket_number'] && !empty($complaint['complaint_ticket_number'])) {
		$_SESSION['complaint_number'] = $complaint['complaint_ticket_number'];
		$form_state['redirect'] = 'complaint/track/detail';
	}
	else{
		form_set_error('complaint_number', t('You must enter a valid complaint number.'));
	}
	
}

function complaint_detail(){

	if(empty($_SESSION['complaint_number']) || $_SESSION['complaint_number']==''){
		drupal_goto("complaint/track");
	}
	$complaint_number = $_SESSION['complaint_number'];
	
	$query = db_select('aai_complaint', 'c');
	$query->fields('c', array('complaint_ticket_number', 'complaint_details', 'complaint_received_on', 'complaint_id', 'complainer_name'));
	$query->condition('c.complaint_ticket_number', $complaint_number,'=');
	$result = $query->execute()->fetchAssoc();
		
	$rows = array(
	array(
        array('data' => t('Complaint No.'), 'header' => TRUE),
        array('data'=>$result['complaint_ticket_number'],'style' => 'font-size:15px;font-weight:bold',),
      ),
	  array(
        array('data' => t('Date'), 'header' => TRUE),
        date("d M, y H:i",strtotime($result['complaint_received_on'])),
      ),
	  array(
        array('data' => t('Complain Detail'), 'header' => TRUE),
        check_plain($result['complaint_details']),
      ),
    );
	
    $build['complaint_view_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => array('class' => array('complaint-view')),
	  '#caption' => "Complaint Details",
    );
		
	$header_history = array(
		array('data' => t('Assigned To')),
		array('data' => t('Date'), 'sort' => 'asc'),
		array('data' => t('Actioned by')),		
		array('data' => t('Status')),
	);

	$vid = array();
	$vid[] = getAaiAirportVariable('aai_airport_vid');
	$vid[] = getAaiAirportVariable('aai_airlines_vid');
	$vid[] = getAaiAirportVariable('aai_security_vid');
	$vid[] = getAaiAirportVariable('aai_dgca_vid');
	$vid[] = getAaiAirportVariable('aai_custom_vid');
	$vid[] = getAaiAirportVariable('aai_immigration_vid');
	
	$query_history = db_select('aai_complaint_history', 'ch')->extend('PagerDefault')->extend('TableSort');
	$query_history->leftJoin('taxonomy_term_data', 'ap', 'ch.assigned_to_tid = ap.tid AND ap.vid IN ( '.implode(",",$vid).')');
	$query_history->leftJoin('aai_complaint_status', 'cs', 'ch.complaint_status_id = cs.status_id');
	$query_history->fields('ch', array('comment', 'created_at', 'updated_by','request_ip'));
	$query_history->addField('ap', 'name');
	
	$query_history->addField('cs', 'status');
	$query_history->condition('complaint_id', $result['complaint_id'],'=');

	$result_history = $query_history->orderByHeader($header_history)->execute();
	//echo '<pre>';
	//var_dump($result);

	foreach ($result_history as $history) {
		$rows_history[] = array('data' =>
			array(
				check_plain($history->name),
				date("d M, Y H:i",strtotime($history->created_at)),
				format_username(user_load((int) $history->updated_by))=='Anonymous'?check_plain($result['complainer_name'].' (You)'):format_username(user_load((int) $history->updated_by)),
				check_plain($history->status),
			),
		);
	}
	
	$query_comment = db_select('aai_complaint', 'c');
	$query_comment->Join('aai_complaint_history', 'ch', 'c.complaint_id = ch.complaint_id AND c.complaint_status_id = ch.complaint_status_id');
	$query_comment->fields('c', array('complaint_ticket_number'));
	$query_comment->addField('ch', 'comment');
	$query_comment->condition('c.complaint_ticket_number', $complaint_number,'=');
	$query_comment->condition('c.complaint_status_id', 3,'=');
	$query_comment->condition('ch.complaint_status_id', 3,'=');
	$result_comment = $query_comment->execute()->fetchAssoc();
	
	$complaint_comment = '';
	if(!empty($result_comment['comment']))
	$complaint_comment = '<div class="complaint_comment"><label>Comment :</label>'.check_plain($result_comment['comment']).'</div>';
	
	//echo '<pre>';
	//var_dump($result_comment); die;
	
	$build['complaint_table_history'] = array(
		'#theme' => 'table',
		'#header' => $header_history,
		'#rows' => $rows_history,
		'#caption' => "Complaint History",
		'#attributes' => array('class' => array('complaint-history')),
		'#suffix' => t($complaint_comment),
	);
	
	$build['complaint_feedback'] = drupal_get_form('complaint_feedback',$result['complaint_id']);
	return $build;
}

function complaint_feedback($form, &$form_state){
	
	/*
	$form['complaint_id'] = array(
		'#type' => 'value',
		'#value' => $form_state['build_info']['args'][1],
	);
	*/
	
	$form['complaint_feedback'] = array(
		'#type' => 'fieldset',
		'#title' => t('Feedback'),
		'#collapsible' => TRUE,
		'#collapsed' => FALSE,
    );
	
	$form['complaint_feedback']['actions'] = array(
		'#type' => 'actions',
		'#attributes' => array('class' => array('container-inline')),
	);
	
	$form['complaint_feedback']['satisfied_from_issue_resolve'] = array(
		'#type' => 'radios',
		'#title' => t('Is your issue resolved?'),
		'#maxlength' => 1,
		'#options' => array( 1 => 'Yes', 0 => 'No'),
		'#required' => TRUE,
	); 
	
	$form['complaint_feedback']['satisfied_with_response'] = array(
		'#type' => 'radios',
		'#title' => t('How satisfied are you with the response?'),
		'#maxlength' => 1,
		'#options' => array( 1 => 'Satisfied', 2 => 'Overall satisfied', 3 => 'Not satisfied'),
		'#required' => TRUE,
	); 
	
	$form['complaint_feedback']['satisfied_with_response'][1] = array(
    '#prefix' => '<div class="satisfied">',
    '#suffix' => '</div>'
	);
	$form['complaint_feedback']['satisfied_with_response'][2] = array(
    '#prefix' => '<div class="overall-satisfied">',
    '#suffix' => '</div>'
	);
	$form['complaint_feedback']['satisfied_with_response'][3] = array(
    '#prefix' => '<div class="notsatisfied">',
    '#suffix' => '</div>'
	);
	
	$form['complaint_feedback']['actions']['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit'),
	);
	return $form;
}

function complaint_ajax_callback($form, $form_state){
	return $form['complaint_subcategory_id'];
}