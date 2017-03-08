<?php
/**
 * @file
 * Admin page callbacks for the complaint module.
 */
 drupal_add_css(drupal_get_path('module', 'complaint') . '/complaint.css', array('group' => CSS_THEME));

/**
 * Categories/list tab.
 * Subcategories/list tab.
 */
function complaint_category_list() {
    
  $header = array(
    t('Category Name'),
    array('data' => t('Operations'), 'colspan' => 3),
  );
  $rows = array();

  // Get all the complaint categories from the database.
  $categories = db_select('aai_complaint_category', 'c')
    ->addTag('translatable')
    ->fields('c', array('category_id', 'category_name'))
	->condition('status', 1,'=')
    ->orderBy('category_id')
    ->execute()
    ->fetchAll();

  // Loop through the categories and add them to the table.
  foreach ($categories as $category) {
    $rows[] = array(
      check_plain($category->category_name),
      l(t('Edit'), 'admin/structure/complaint/category/edit/' . $category->category_id),
      l(t('Delete'), 'admin/structure/complaint/category/delete/' . $category->category_id),
	  l(t('Add Subcategory'), 'admin/structure/complaint/subcategory/add/' . $category->category_id),
    );
  }

  if (!$rows) {
    $rows[] = array(array(
      'data' => t('No categories available.'),
      'colspan' => 5,
    ));
  }

  $build['category_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );
  return $build;
}


function complaint_subcategory_list(){

	$header = array(
		t('Subcategory Name'),
		t('Category Name'),
		array('data' => t('Operations'), 'colspan' => 2),
	  );
	$rows = array();

	// Get all the complaint categories from the database.
	$query = db_select('aai_complaint_subcategory', 'sc');
	$query->join('aai_complaint_category', 'c', 'sc.category_id = c.category_id');
	$query->fields('sc', array('subcategory_id', 'subcategory_name'));
	$query->fields('c', array('category_name'));
	$query->condition('sc.status', 1,'=');
	$query->condition('c.status', 1,'=');
	$query->orderBy('subcategory_id');
	$result = $query->execute();
	
  // Loop through the categories and add them to the table.
  while($subcategory = $result->fetchAssoc()) {
    $rows[] = array(
      check_plain($subcategory['subcategory_name']),
	  check_plain($subcategory['category_name']),
      l(t('Edit'), 'admin/structure/complaint/subcategory/edit/' . $subcategory['subcategory_id']),
      l(t('Delete'), 'admin/structure/complaint/subcategory/delete/' . $subcategory['subcategory_id']),
    );
  }

  if (!$rows) {
    $rows[] = array(array(
      'data' => t('No subcategories available.'),
      'colspan' => 5,
    ));

}
$build['subcategory_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );
  return $build;
}

function complaint_source_list() {
    
  $header = array(
    t('Complaint Source'),
    array('data' => t('Operations'), 'colspan' => 3),
  );
  $rows = array();

  // Get all the complaint sources from the database.
  $sources = db_select('aai_complaint_source', 'cs')
    ->addTag('translatable')
    ->fields('cs', array('complain_source_id', 'complain_source'))
	->condition('status', 1,'=')
    ->orderBy('complain_source_id')
    ->execute()
    ->fetchAll();

  // Loop through the sources and add them to the table.
  foreach ($sources as $source) {
    $rows[] = array(
      check_plain($source->complain_source),
      l(t('Edit'), 'admin/structure/complaint/source/edit/' . $source->complain_source_id),
      l(t('Delete'), 'admin/structure/complaint/source/delete/' . $source->complain_source_id),
	  
    );
  }

  if (!$rows) {
    $rows[] = array(array(
      'data' => t('No sources available.'),
      'colspan' => 5,
    ));
  }

  $build['category_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );
  return $build;
}


function complaint_list(){

	global $user;
	$user_id = $user->uid; 
	$rows = array();
	$build['complaint_filter_form'] = drupal_get_form('complaint_filter_form');
    $vid = array();
	$vid[] = getAaiAirportVariable('aai_airport_vid');
	$vid[] = getAaiAirportVariable('aai_airlines_vid');
	$vid[] = getAaiAirportVariable('aai_security_vid');
	$vid[] = getAaiAirportVariable('aai_dgca_vid');
	$vid[] = getAaiAirportVariable('aai_custom_vid');
	$vid[] = getAaiAirportVariable('aai_immigration_vid');
	
	$header = array(
		array('data' => t('Complaint No.'), 'field' => 'c.complaint_ticket_number'),
		array('data' => t('Assigned To'), 'field' => 'ap.name'),
		array('data' => t('Received On'), 'field' => 'c.complaint_received_on', 'sort' => 'desc'),
		array('data' => t('Name'), 'field' => 'c.complainer_name'),
		array('data' => t('Email'), 'field' => 'c.complainer_email'),
		array('data' => t('Type'), 'field' => 'cc.category_name'),
		array('data' => t('Source'), 'field' => 'css.complain_source'),
		array('data' => t('Status'), 'field' => 'cs.status'),
		array('data' => t('Operations'), 'colspan' => 4),
	);
	
	$query = db_select('aai_complaint', 'c')->extend('PagerDefault')->extend('TableSort');
	$query->leftJoin('aai_complaint_category', 'cc', 'c.complaint_type = cc.category_id');
	$query->leftJoin('taxonomy_term_data', 'ap', "c.assigned_to_tid = ap.tid AND ap.vid IN (".implode(",",$vid).")");
	$query->leftJoin('aai_complaint_status', 'cs', 'c.complaint_status_id = cs.status_id');
	$query->leftJoin('aai_complaint_source', 'css', 'c.complain_source_id = css.complain_source_id');
	$query->fields('c', array('complaint_ticket_number', 'complainer_name', 'complainer_email','complaint_id','complaint_received_on'));
	$query->addField('cc', 'category_name');
	$query->addField('ap', 'name');
	$query->addField('css', 'complain_source');
	$query->addField('cs', 'status');
	
	$user_roles = getComplaintUserRole($user_id);
	$administrator_role = getAaiAirportVariable('aai_chq_administrator_role_id');
	$aai_chq_complaint_manager_role_id = getAaiAirportVariable('aai_chq_complaint_manager_role_id');
	
	if(in_array($administrator_role, $user_roles) === FALSE && in_array($aai_chq_complaint_manager_role_id, $user_roles) === FALSE){
		$airports = getAirportUserPermission($user_id);
		$query->condition('assigned_to_tid', $airports,'IN');
		$query->condition('visible_to_airport', 1,'=');
	}
		
	if (!empty($_SESSION['complaint_filter']['complaint_ticket_number'])) {
		$query->condition('complaint_ticket_number', $_SESSION['complaint_filter']['complaint_ticket_number'],'=');
	}
	if (!empty($_SESSION['complaint_filter']['complainer_email'])) {
		$query->condition('complainer_email', $_SESSION['complaint_filter']['complainer_email'],'=');
	}
	
	if (!empty($_SESSION['complaint_filter']['date_of_from'])) {
		$query->condition('c.created_at', $_SESSION['complaint_filter']['date_of_from'],'>=');
	}
	if (!empty($_SESSION['complaint_filter']['date_of_to'])) {
		$query->condition('c.created_at', $_SESSION['complaint_filter']['date_of_to'],'<=');
	}
	if (!empty($_SESSION['complaint_filter']['status'])) {
		$query->condition('complaint_status_id', array($_SESSION['complaint_filter']['status']),'IN');
	}
		
	$result = $query
		->limit(50)
		->orderByHeader($header)
		->execute();
		
		//echo '<pre>';
		//var_dump($result);

  foreach ($result as $complainer) {
    $rows[] = array('data' =>
      array(
		  array('data'=>$complainer->complaint_ticket_number,'style' => 'font-size:15px;font-weight:bold',),
		  check_plain($complainer->name),
		  date("d M, y",strtotime($complainer->complaint_received_on)),
		  check_plain($complainer->complainer_name),
		  check_plain($complainer->complainer_email),
		  $complainer->category_name?check_plain($complainer->category_name):'N/A',
		  check_plain($complainer->complain_source!=NULL?$complainer->complain_source:'Web portal'),
		  check_plain($complainer->status),
		  l(t('View/Assign/Reply'), 'admin/structure/complaint/view/' . $complainer->complaint_id),
      ),
    );
  }

  $build['complaint_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No complaints available.'),
  );
  $build['complaint_pager'] = array('#theme' => 'pager');

  return $build;

}


function complaint_filter_form($form){

	$form['filters'] = array(
		'#type' => 'fieldset',
		'#title' => t('Filter complaints'),
		'#collapsible' => TRUE,
		'#collapsed' => empty($_SESSION['complaint_filter']),
	);
  
	$form['filters']['complaint_ticket_number'] = array(
		'#type' => 'textfield',
		'#title' => t('Complaint No.'),
		'#maxlength' => 255,
		'#default_value' => isset($_SESSION['complaint_filter']['complaint_ticket_number'])?$_SESSION['complaint_filter']['complaint_ticket_number']:'',
		'#required' => FALSE,
		'#prefix' => '<div class="form-group" >', 
	  );
	
	$form['filters']['complainer_email'] = array(
		'#type' => 'textfield',
		'#title' => t('Complainer Email'),
		'#maxlength' => 255,
		'#default_value' => isset($_SESSION['complaint_filter']['complainer_email'])?$_SESSION['complaint_filter']['complainer_email']:'',
		'#required' => FALSE,
	);
	
	$form['filters']['date_of_from'] = array(
		'#type' => 'date_popup', 
		'#title' => t('Complain Date From'),
		'#default_value' => isset($_SESSION['complaint_filter']['date_of_from'])?$_SESSION['complaint_filter']['date_of_from']:'',
		'#date_format' => 'Y-m-d',
		'#date_label_position' => 'within',
		'#date_increment' => 15, 
		'#date_year_range' => '0:+2', 
		'#required' => FALSE,
	);
	
	$form['filters']['date_of_to'] = array(
		'#type' => 'date_popup', 
		'#title' => t('Complain Date To'),
		'#default_value' => isset($_SESSION['complaint_filter']['date_of_to'])?$_SESSION['complaint_filter']['date_of_to']:'',
		'#date_format' => 'Y-m-d',
		'#date_label_position' => 'within',
		'#date_increment' => 15, 
		'#date_year_range' => '0:+2', 
		'#required' => FALSE,
		'#suffix' => '</div>',
	);
	
	$form['filters']['status'] = array(
		'#type' => 'select',
		'#title' => t('Status'),
		'#options' => webform_options_status(),
		'#default_value' => isset($_SESSION['complaint_filter']['status'])?$_SESSION['complaint_filter']['status']:'',
		
	);
	
	$form['filters']['actions'] = array(
		'#type' => 'actions',
		'#attributes' => array('class' => array('container-inline')),
	);
	$form['filters']['actions']['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Filter'),
	);
	if (!empty($_SESSION['complaint_filter'])) {
		$form['filters']['actions']['reset'] = array(
			'#type' => 'submit',
			'#value' => t('Reset')
		);
	}
	return $form;
}


function complaint_filter_form_validate($form, &$form_state) {

	
	if (!valid_email_address($form_state['values']['complainer_email']) && !empty($form_state['values']['complainer_email'])) {
		form_set_error('complainer_email', t('You must enter a valid e-mail address.'));
	}
	
	if ($form_state['values']['date_of_from'] > $form_state['values']['date_of_to']) {
		form_set_error('date_of_from', t('Date from must be greater than Date To'));
	}
}

function complaint_filter_form_submit($form, &$form_state) {

  $op = $form_state['values']['op'];
  switch ($op) {
    case t('Filter'):
        if (isset($form_state['values']['complaint_ticket_number'])) {
          $_SESSION['complaint_filter']['complaint_ticket_number'] = $form_state['values']['complaint_ticket_number'];
		}
		
		if (isset($form_state['values']['complainer_email'])) {
		  $_SESSION['complaint_filter']['complainer_email'] = $form_state['values']['complainer_email'];
        }
		
		if (isset($form_state['values']['date_of_from'])) {
		  $_SESSION['complaint_filter']['date_of_from'] = $form_state['values']['date_of_from'];
        }
		if (isset($form_state['values']['date_of_to'])) {
		  $_SESSION['complaint_filter']['date_of_to'] = $form_state['values']['date_of_to'];
        }
		if (isset($form_state['values']['status'])) {
		  $_SESSION['complaint_filter']['status'] = $form_state['values']['status'];
        }
      break;
    case t('Reset'):
      $_SESSION['complaint_filter'] = array();
      break;
  }
  
}


function complaint_view($id){

	$complaint_id = (int) arg(4);
	
	$vid = array();
	$vid[] = getAaiAirportVariable('aai_airport_vid');
	$vid[] = getAaiAirportVariable('aai_airlines_vid');
	$vid[] = getAaiAirportVariable('aai_security_vid');
	$vid[] = getAaiAirportVariable('aai_dgca_vid');
	$vid[] = getAaiAirportVariable('aai_custom_vid');
	$vid[] = getAaiAirportVariable('aai_immigration_vid');
	
	$query = db_select('aai_complaint', 'c')->extend('PagerDefault')->extend('TableSort');
	$query->leftJoin('aai_complaint_category', 'cc', 'c.complaint_type = cc.category_id');
	
	$query->leftJoin('taxonomy_term_data', 'ap', 'c.assigned_to_tid = ap.tid AND ap.vid IN ( '.implode(",",$vid).')');
	$query->leftJoin('aai_complaint_status', 'cs', 'c.complaint_status_id = cs.status_id');
	$query->leftJoin('aai_complaint_source', 'css', 'c.complain_source_id = css.complain_source_id');
	$query->fields('c', array('complaint_ticket_number', 'complainer_name', 'complainer_email','complaint_id','complaint_received_on','complaint_details','complainer_contact_no','document_number','assigned_to_tid','assigned_to','pnr'));
	$query->addField('cc', 'category_name');
	$query->addField('ap', 'name');
	$query->addField('ap', 'tid');
	$query->addField('css', 'complain_source');
	$query->addField('cs', 'status');
	$query->addField('cs', 'status_id');
	$query->condition('c.complaint_id', $complaint_id,'=');
	$result = $query->execute()->fetchAssoc();
		
	//echo '<pre>';
	//var_dump($result);
	
	$rows = array(
	array(
        array('data' => t('Complaint No.'),'style' => 'font-size:12px;font-weight:bold'),
        array('data'=>$result['complaint_ticket_number'],'style' => 'font-size:15px;font-weight:bold',),
      ),
	  array(
        array('data' => t('Complain Detail'),'style' => 'font-size:12px;font-weight:bold'),
        check_plain($result['complaint_details']),
      ),
	  array(
        array('data' => t('Source'),'style' => 'font-size:12px;font-weight:bold'),
        check_plain($result['complain_source']!=NULL?$result['complain_source']:'Web Portal'),
      ),
	  array(
        array('data' => t('Date'),'style' => 'font-size:12px;font-weight:bold'),
        date("d M, Y H:i",strtotime(check_plain($result['complaint_received_on']))),
      ),
	  array(
        array('data' => t('Assigned To'),'style' => 'font-size:12px;font-weight:bold'),
        check_plain($result['name']),
      ),
	  array(
        array('data' => t('Status'),'style' => 'font-size:12px;font-weight:bold'),
        check_plain($result['status']),
      ),
	  array(
        array('data' => t('Document/CPGRAM No'),'style' => 'font-size:12px;font-weight:bold'),
        $result['document_number']?check_plain($result['document_number']):'N/A',
      ),
	   array(
        array('data' => t('Complaint Type'),'style' => 'font-size:12px;font-weight:bold'),
        $result['category_name']?check_plain($result['category_name']):'N/A',
      ),
	    
      array(
        array('data' => t('Name'),'style' => 'font-size:12px;font-weight:bold'),
        check_plain($result['complainer_name']),
      ),
	  
	  array(
        array('data' => t('Email'),'style' => 'font-size:12px;font-weight:bold'),
        check_plain($result['complainer_email']),
      ),
	  array(
        array('data' => t('Contact No.'),'style' => 'font-size:12px;font-weight:bold'),
        check_plain($result['complainer_contact_no']),
      ),
	  array(
        array('data' => t('PNR'),'style' => 'font-size:12px;font-weight:bold'),
        $result['pnr']?check_plain($result['pnr']):'N/A',
      ),
    );
	
    $build['complaint_view_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => array('class' => array('complaint-view')),
	  '#caption' => "Complaint Details",
    );
	
		
	$header_history = array(
		array('data' => t('Assigned To'), 'field' => 'ap.name'),
		array('data' => t('Action Date'), 'field' => 'ch.created_at', 'sort' => 'asc'),
		array('data' => t('Actioned by'), 'field' => 'ch.comment'),	
		array('data' => t('Comment'), 'field' => 'ch.comment'),		
		array('data' => t('Status'), 'field' => 'cs.status'),
	);

	$query_history = db_select('aai_complaint_history', 'ch')->extend('PagerDefault')->extend('TableSort');
	$query_history->leftJoin('taxonomy_term_data', 'ap', 'ch.assigned_to_tid = ap.tid AND ap.vid IN ( '.implode(",",$vid).')');
	$query_history->leftJoin('aai_complaint_status', 'cs', 'ch.complaint_status_id = cs.status_id');
	$query_history->fields('ch', array('comment', 'created_at', 'updated_by','request_ip'));
	$query_history->addField('ap', 'name');
	
	$query_history->addField('cs', 'status');
	$query_history->condition('complaint_id', $complaint_id,'=');

	$result_history = $query_history->orderByHeader($header_history)->execute();
	//echo '<pre>';
	//var_dump($result);

	foreach ($result_history as $history) {
		$rows_history[] = array('data' =>
			array(
				check_plain($history->name),
				date("d M, Y H:i",strtotime($history->created_at)),
				format_username(user_load((int) $history->updated_by))=='Anonymous'?check_plain($result['complainer_name']). ' (Complainer)':format_username(user_load((int) $history->updated_by)),
				check_plain($history->comment),
				check_plain($history->status),
			),
		);
	}

	$build['complaint_table_history'] = array(
		'#theme' => 'table',
		'#header' => $header_history,
		'#rows' => $rows_history,
		'#caption' => "Complaint History",
		'#attributes' => array('class' => array('complaint-history')),
	);
		
	if($result['status_id']!=3){
		if(complaintAdminPageAccess()){
			$build['complaint_filter_assign'] = drupal_get_form('complaint_assign',$result['tid'],$result['complaint_id']);
		}
		$build['complaint_filter_reply'] = drupal_get_form('complaint_reply','complaint_reply',$result['assigned_to_tid'], $result['assigned_to'],$result['complaint_id']);
	}
	return $build;
}

function complaint_assign($form,$form_state){
	
	if(isset($form_state['values']['assigned_to'])) { $assigned_to = $form_state['values']['assigned_to']; }else $assigned_to='';
	
	// Get an array of the priority.
	$priority_slect = array(""=>"Select");
	$priority_without_select = db_select('aai_complaint_priority', 'cp')
	->addTag('translatable')
	->condition('status', 1,'=')
	->fields('cp', array('priority', 'description'))
	->orderBy('sequence')
	->execute()
	->fetchAllKeyed();
	$priority = $priority_slect+$priority_without_select;
		
	$form['assign'] = array(
		'#type' => 'fieldset',
		'#title' => t('Assign/Forward complain'),
		'#collapsible' => TRUE,
		'#collapsed' => TRUE,
    );
  
	$form['assign']['complaint_id'] = array(
		'#type' => 'value',
		'#value' => $form_state['build_info']['args'][1],
	);
	
	$form['assign']['assigned_to'] = array(
		'#type' => 'select',
		'#title' => t('Assign to'),
		'#options' =>  array (
		  '' => t('Select'),
		  '1' => t('Airport'),
		  '2' => t('Airlines'),
		  '3' => t('DGCA'),
		  '4' => t('Customs'),
		  '5' => t('Security'),
		  '6' => t('Immigration'),
		),
		'#required' => TRUE,
		'#ajax' => array(
			'event' => 'change',
			'callback' => 'grievance_related_ajax_callback',
			'wrapper' => 'grievance_related_replace',
		),
	);
		
	$form['assign']['assigned_to_tid'] = array(
		'#type' => 'select',
		'#title' => t('Please choose'),
		'#options' => grievance_for_ajax($assigned_to),
		'#required' => TRUE,
		'#prefix' => '<span id="grievance_related_replace">',
		'#suffix' => '</span>',
	);
		
	$form['assign']['comment'] = array(
		'#type' => 'textarea',
		'#title' => t('Interim Reply'),
		'#maxlength' => 255,
		'#default_value' => '',
		'#required' => TRUE,
	);
	
	$form['assign']['priority'] = array(
    '#type' => 'select',
    '#title' => t('Priority'),
    '#options' => $priority,
    '#required' => TRUE,
	'#prefix' => '<div id="priority">',
	);
	
	$form['assign']['send_sms'] = array(
	'#type' =>
	'checkbox', 
	  '#title' => t('Send SMS.'),
	  '#description'=>'A sms will send to assignee',
	  '#prefix' => '</div>',
	);
	
	$form['assign']['actions']['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit'),
	);
	return $form;
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

function grievance_related_ajax_callback($form, $form_state){
	return $form['assign']['assigned_to_tid'];
}

function complaint_assign_validate($form, &$form_state) {
  
  if(!validateInput($form_state['values']['comment'])){
		form_set_error('comment', t('Invalid characters contains in complaint details'));
	}
}

function complaint_assign_submit($form, &$form_state) {

  db_insert('aai_complaint_history')
  ->fields(array(
    'complaint_id' => $form_state['values']['complaint_id'],
    'assigned_to_tid' => $form_state['values']['assigned_to_tid'],
	'assigned_to' => $form_state['values']['assigned_to'],
	'comment' => $form_state['values']['comment'],
	'complaint_status_id' => 2,
	'created_at' =>complaintUserInformation('created_at'),
	'updated_by' =>complaintUserInformation('updated_by'),
	'request_ip' =>complaintUserInformation('request_ip'),
  ))
  ->execute();
  
   db_update('aai_complaint') 
  ->fields(array(
	'assigned_to_tid' => $form_state['values']['assigned_to_tid'],
	'assigned_to' => $form_state['values']['assigned_to'],
	'complaint_status_id' => 2,
  ))
  ->condition('complaint_id', $form_state['values']['complaint_id'], '=')
  ->execute();
  
  drupal_set_message(t("Complaint has been assigned successfully."));
  
}

function complaint_reply($form, $form_state){

	$form['forward']['assigned_to_tid'] = array(
		'#type' => 'value',
		'#value' => $form_state['build_info']['args'][1],
	);
	
	$form['forward']['assigned_to'] = array(
		'#type' => 'value',
		'#value' => $form_state['build_info']['args'][2],
	);
	
	$form['forward']['complaint_id'] = array(
		'#type' => 'value',
		'#value' => $form_state['build_info']['args'][3],
	);

	$form['reply'] = array(
		'#type' => 'fieldset',
		'#title' => t('Reply to complainer (Close)'),
		'#collapsible' => TRUE,
		'#collapsed' => TRUE,
	);

	$form['reply']['comment'] = array(
		'#type' => 'textarea',
		'#title' => t('Comment'),
		'#maxlength' => 255,
		'#default_value' => '',
		'#required' => TRUE,
		'#prefix' => '<div class="form-group" >', 
	);

	$form['reply']['actions'] = array(
		'#type' => 'actions',
		'#attributes' => array('class' => array('container-inline')),
	);
		$form['reply']['actions']['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit'),
	);
	return $form;
}


function complaint_reply_validate($form, &$form_state){
	if(validateInput($form_state['values']['comment'])){
		form_set_error('comment', t('Invalid characters contains in comment'));
	}
}

function complaint_reply_submit($form, &$form_state){
  global $user;
  $user_id = $user->uid;  
  db_insert('aai_complaint_history')
  ->fields(array(
    'complaint_id' => $form_state['values']['complaint_id'],
	'assigned_to_tid' => $form_state['values']['assigned_to_tid'],
	'assigned_to' => $form_state['values']['assigned_to'],
	'comment' => $form_state['values']['comment'],
	'updated_by' => $user_id,
	'complaint_status_id' => 3,
	'created_at' =>complaintUserInformation('created_at'),
	'updated_by' =>complaintUserInformation('updated_by'),
	'request_ip' =>complaintUserInformation('request_ip'),
  ))
  ->execute();
  
   db_update('aai_complaint') 
  ->fields(array(
	'complaint_status_id' => 3,
  ))
  ->condition('complaint_id', $form_state['values']['complaint_id'], '=')
  ->execute();
  
  //Trigger notification email
  $result = db_select('aai_complaint', 'ac')
  ->fields('ac' , array('complainer_contact_no','complainer_email','complaint_id','complaint_ticket_number', 'complain_source_id'))
  ->condition('complaint_id', $form_state['values']['complaint_id'])
  ->execute();
  $finaldata = $result->fetchAssoc();
 
  $send_notification = trigger_notification(
  	$finaldata['complaint_id'],
    $finaldata['complaint_ticket_number'],
    $finaldata['complain_source_id'],
    'res',
    $finaldata['complainer_contact_no'],
    $finaldata['complainer_email'],
    3
  );

  drupal_set_message(t("Complaint has been disposed successfully."));
}


/**
 * Form constructor for the category edit form.
 *
 * @param $category
 *   An int describing the category to be edited. May be null for new
 *
 * @see complaint_category_edit_form_validate()
 * @see complaint_category_edit_form_submit()
 */
 
 
function complaint_category_edit_form($form, &$form_state, $category = null) {

  $category_data = getCategory((int) $category);
  $form['category_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Category'),
    '#maxlength' => 255,
    '#default_value' => $category_data['category_name'],
    '#description' => t("Example: 'Please add new or edit existing category name"),
    '#required' => TRUE,
  );
  
  $form['category_id'] = array(
    '#type' => 'value',
    '#value' => $category_data['category_id'],
  );
  
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Form validation handler for complaint_category_edit_form().
 *
 * @see complaint_category_edit_form_submit()
 */
function complaint_category_edit_form_validate($form, &$form_state) {

  //complaint form, make sure that the given category is unique.
  $category_name = (string) $form_state['values']['category_name'];
  $query = db_select('aai_complaint_category', 'c')->condition('c.category_name', $category_name, '=')->condition('status', 1,'=');
  if (!empty($form_state['values']['category_id'])) {
    $query->condition('c.category_id', $form_state['values']['category_id'], '<>');
  }
  if ($query->countQuery()->execute()->fetchField()) {
    form_set_error('category_name', t('A complaint form with category %category already exists.', array('%category' => $category_name)));
  }
  
}

/**
 * Form submission handler for complaint_category_edit_form().
 *
 * @see complaint_category_edit_form_validate()
 */
function complaint_category_edit_form_submit($form, &$form_state) {
  
  if (empty($form_state['values']['category_id'])) {
  db_insert('aai_complaint_category')
	  ->fields(array(
		'category_name' => $form_state['values']['category_name'],
		'created_at' =>complaintUserInformation('created_at'),
		'updated_at' =>complaintUserInformation('updated_at'),
		'updated_by' =>complaintUserInformation('updated_by'),
		'request_ip' =>complaintUserInformation('request_ip'),
	  ))
  ->execute();
  }
  else {
	   db_update('aai_complaint_category') 
	  ->fields(array(
		'category_name' => $form_state['values']['category_name'],
		'updated_at' =>complaintUserInformation('updated_at'),
		'updated_by' =>complaintUserInformation('updated_by'),
		'request_ip' =>complaintUserInformation('request_ip'),
	  ))
	  ->condition('category_id', $form_state['values']['category_id'], '=')
	  ->execute();	
  }

  drupal_set_message(t('Complaint category %category has been saved.', array('%category' => $form_state['values']['category_name'])));
  watchdog('complaint', 'Category %category has been saved.', array('%category' => $form_state['values']['category_name']), WATCHDOG_NOTICE, l(t('Edit'), 'admin/structure/complaint/category/edit/' . $form_state['values']['category_id']));
  $form_state['redirect'] = 'admin/structure/complaint/category';
}

/**
 * Form constructor for the complaint category deletion form.
 *
 * @param $category
 * @see complaint_menu()
 * @see complaint_category_delete_form_submit()
 */
function complaint_category_delete_form($form, &$form_state,  $category) {

  $category_data = getCategory((int) $category);
  $form['category_id'] = array(
    '#type' => 'value',
    '#value' => $category,
  );
  $form['category_name'] = array(
    '#type' => 'value',
    '#value' => $category_data['category_name'],
  );

  return confirm_form(
    $form,
    t('Are you sure you want to delete %category?', array('%category' => $category_data['category_name'])),
    'admin/structure/complaint/category',
    t('%category will remove from complaint form category listing.', array('%category' => $category_data['category_name'])),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Form submission handler for complaint_category_delete_form().
 */
function complaint_category_delete_form_submit($form, &$form_state) {
  $category_id = $form['category_id']['#value'];
  $category_name = $form['category_name']['#value'];
  
  db_update('aai_complaint_category')
	  ->fields(array(
		'status' => 0,
		'updated_at' =>complaintUserInformation('created_at'),
		'updated_by' =>complaintUserInformation('updated_by'),
		'request_ip' =>complaintUserInformation('request_ip'),
	  ))
    ->condition('category_id', $category_id)
    ->execute();

  drupal_set_message(t('Category %category has been deleted.', array('%category' => $category_name)));
  watchdog('complaint', 'Category %category has been deleted.', array('%category' => $category_name), WATCHDOG_NOTICE);

  $form_state['redirect'] = 'admin/structure/complaint/category';
}

/**
 * get categories.
 */
function getCategory($category_id) {
  $category = db_select('aai_complaint_category', 'c')
    ->addTag('translatable')
    ->fields('c' , array('category_id', 'category_name'))
    ->condition('category_id', $category_id)
    ->execute()
    ->fetchAssoc();
	if(empty($category)){
		return  array(
		'category_name' => '',
		'category_id' => NULL,
	);
  }else{
	return $category;
  }
}


/**
 * Form constructor for the subcategory edit form.
 *
 * @param $subcategory
 *   An int describing the category to be edited. May be null for new
 *
 * @see complaint_subcategory_edit_form_validate()
 * @see complaint_subcategory_edit_form_submit()
 */
 
 
function complaint_subcategory_edit_form($form, &$form_state, $subcategory = null) {

  //var_dump(arg(5)); die;
  
  $subcategory_data = getsubCategory((int) $subcategory);
   
  // Get an array of the categories.
  $categories = db_select('aai_complaint_category', 'c')
    ->addTag('translatable')
    ->fields('c', array('category_id', 'category_name'))
	->condition('status', 1,'=')
    ->orderBy('category_id')
    ->orderBy('category_name')
    ->execute()
    ->fetchAllKeyed();
	
	$categories = $categories;
    $default_category = $subcategory_data['category_id'];
    
  
  $form['category_id'] = array(
    '#type' => 'select',
    '#title' => t('Category'),
    '#options' =>  $categories,
	'#default_value' => arg(5)!=''?arg(5):'',
    '#required' => TRUE,
  );
  
  $form['subcategory_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Subcategory'),
    '#maxlength' => 255,
    '#default_value' => $subcategory_data['subcategory_name'],
    '#description' => t("Example: 'Please add new or edit existing subcategory name"),
    '#required' => TRUE,
  );
  
  $form['subcategory_id'] = array(
    '#type' => 'value',
    '#value' => $subcategory_data['subcategory_id'],
  );
  
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Form validation handler for complaint_category_edit_form().
 *
 * @see complaint_category_edit_form_submit()
 */
function complaint_subcategory_edit_form_validate($form, &$form_state) {

  //complaint form, make sure that the given subcategory is unique.
  $subcategory_name = (string) $form_state['values']['subcategory_name'];
  $query = db_select('aai_complaint_subcategory', 'sc')->condition('sc.subcategory_name', $subcategory_name, '=')->condition('status', 1,'=');
  if (!empty($form_state['values']['subcategory_id'])) {
    $query->condition('sc.subcategory_id', $form_state['values']['subcategory_id'], '<>');
  }
  if ($query->countQuery()->execute()->fetchField()) {
    form_set_error('subcategory_name', t('A complaint form with Subcategory %subcategory already exists.', array('%subcategory' => $subcategory_name)));
  }
  
}

/**
 * Form submission handler for complaint_category_edit_form().
 *
 * @see complaint_category_edit_form_validate()
 */
function complaint_subcategory_edit_form_submit($form, &$form_state) {
  
  if (empty($form_state['values']['subcategory_id'])) {
  db_insert('aai_complaint_subcategory')
  ->fields(array(
	'subcategory_name' => $form_state['values']['subcategory_name'],
	'category_id' => $form_state['values']['category_id'],
	'created_at' =>complaintUserInformation('created_at'),
	'updated_at' =>complaintUserInformation('updated_at'),
	'updated_by' =>complaintUserInformation('updated_by'),
	'request_ip' =>complaintUserInformation('request_ip'),
  ))
  ->execute();
  }
  else {
   db_update('aai_complaint_subcategory') 
  ->fields(array(
    'subcategory_name' => $form_state['values']['subcategory_name'],
	'category_id' => $form_state['values']['category_id'],
	'updated_at' =>complaintUserInformation('updated_at'),
	'updated_by' =>complaintUserInformation('updated_by'),
	'request_ip' =>complaintUserInformation('request_ip'),
  ))
  ->condition('subcategory_id', $form_state['values']['subcategory_id'], '=')
  ->execute();	
  }
  drupal_set_message(t('Complaint subcategory %subcategory has been saved.', array('%subcategory' => $form_state['values']['subcategory_name'])));
  watchdog('complaint', 'subcategory %subcategory has been saved.', array('%subcategory' => $form_state['values']['subcategory_name']), WATCHDOG_NOTICE, l(t('Edit'), 'admin/structure/complaint/subcategory/edit/' . $form_state['values']['subcategory_id']));
  $form_state['redirect'] = 'admin/structure/complaint/subcategory';
}

/**
 * Form constructor for the complaint category deletion form.
 *
 * @param $category
 * @see complaint_menu()
 * @see complaint_category_delete_form_submit()
 */
function complaint_subcategory_delete_form($form, &$form_state,  $subcategory) {

  $subcategory_data = getsubCategory((int) $subcategory);
  $form['subcategory_id'] = array(
    '#type' => 'value',
    '#value' => $subcategory,
  );
  $form['subcategory_name'] = array(
    '#type' => 'value',
    '#value' => $subcategory_data['subcategory_name'],
  );

  return confirm_form(
    $form,
    t('Are you sure you want to delete %subcategory?', array('%subcategory' => $subcategory_data['subcategory_name'])),
    'admin/structure/complaint/subcategory',
    t('%subcategory will remove from complaint form subcategory listing.', array('%subcategory' => $subcategory_data['subcategory_name'])),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Form submission handler for complaint_category_delete_form().
 */
function complaint_subcategory_delete_form_submit($form, &$form_state) {
  $subcategory_id = $form['subcategory_id']['#value'];
  $subcategory_name = $form['subcategory_name']['#value'];
  
  db_update('aai_complaint_subcategory')
	  ->fields(array(
		'status' => 0,
		'updated_at' =>complaintUserInformation('updated_at'),
		'updated_by' =>complaintUserInformation('updated_by'),
		'request_ip' =>complaintUserInformation('request_ip'),
	  ))
    ->condition('subcategory_id', $subcategory_id)
    ->execute();

  drupal_set_message(t('Subcategory %subcategory has been deleted.', array('%subcategory' => $subcategory_name)));
  watchdog('complaint', 'Subcategory %subcategory has been deleted.', array('%subcategory' => $subcategory_name), WATCHDOG_NOTICE);

  $form_state['redirect'] = 'admin/structure/complaint/subcategory';
}

/**
 * get categories.
 */
function getsubCategory($subcategory_id) {
  $subcategory = db_select('aai_complaint_subcategory', 'sc')
    ->addTag('translatable')
    ->fields('sc' , array('subcategory_id','category_id', 'subcategory_name'))
    ->condition('subcategory_id', $subcategory_id)
    ->execute()
    ->fetchAssoc();
	if(empty($subcategory)){
		return  array(
		'subcategory_name' => '',
		'subcategory_id' => NULL,
		'category_id' => NULL,
	);
  }else{
	return $subcategory;
  }
}


function complaint_source_edit_form($form, &$form_state, $source_id = null) {

  $source_data = getSource((int) $source_id);
  $form['complain_source'] = array(
    '#type' => 'textfield',
    '#title' => t('Source'),
    '#maxlength' => 255,
    '#default_value' => $source_data['complain_source'],
    '#required' => TRUE,
  );
  
  $form['email_sent_to_complainer'] = array(
	'#type' => 'select',
	'#title' => t('Response Email send to the complainer'),
	'#options' =>  array (
	  '' => t('Select'),
	  '0' => t('No'),
	  '1' => t('Yes'),
	),
	'#default_value' => isset($source_data['email_sent_to_complainer'])?$source_data['email_sent_to_complainer']:'',
	'#required' => TRUE,
	'#description' =>t('To select No : Response be sending to complainer while complaint is generated; To select Yes :Response will be sending to complainer while complaint is generated;'),
 );
 
 $form['dispose_off_response_sent_to_complainer'] = array(
	'#type' => 'select',
	'#title' => t('Dispose off message send to complainer'),
	'#options' =>  array (
	  '' => t('Select'),
	  '0' => t('No'),
	  '1' => t('Yes'),
	),
	'#default_value' => isset($source_data['dispose_off_response_sent_to_complainer'])?$source_data['dispose_off_response_sent_to_complainer']:'',
	'#required' => TRUE,
	'#description' =>t('To select No : Dispose off message will be sending to complainer while complaint is generated; To select Yes :Dispose off message will be will be sending to complainer while complaint is generated;'),
 );
 
 $form['complaint_assign_forward'] = array(
	'#type' => 'select',
	'#title' => t('Visible Assign/Forward option'),
	'#options' =>  array (
	  '' => t('Select'),
	  '0' => t('No'),
	  '1' => t('Yes'),
	),
	'#default_value' => isset($source_data['complaint_assign_forward'])?$source_data['complaint_assign_forward']:'',
	'#required' => TRUE,
	'#description' =>t('To select No : Assign/Forward will not be visible for source; To select Yes: Assign/Forward will be visible for source;'),
 );
  
 $form['complain_source_id'] = array(
    '#type' => 'value',
    '#value' => $source_data['complain_source_id'],
  );
  
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

function complaint_source_edit_form_validate($form, &$form_state) {

  //complaint form, make sure that the given source is unique.
  $source_name = (string) $form_state['values']['complain_source'];
  $query = db_select('aai_complaint_source', 'c')->condition('c.complain_source', $source_name, '=')->condition('status', 1,'=');
  
  if (!empty($form_state['values']['complain_source_id'])) {
    $query->condition('c.complain_source_id', $form_state['values']['complain_source_id'], '<>');
  }
  
  if ($query->countQuery()->execute()->fetchField()) {
    form_set_error('complain_source', t('A complaint form with source %source already exists.', array('%source' => $source_name)));
  }
  
}

/**
 * Form submission handler for complaint_source_edit_form().
 *
 * @see complaint_source_edit_form_validate()
 */
function complaint_source_edit_form_submit($form, &$form_state) {
  
  if (empty($form_state['values']['complain_source_id'])) {
  db_insert('aai_complaint_source')
  ->fields(array(
    'complain_source' => $form_state['values']['complain_source'],
	'created_at' =>complaintUserInformation('created_at'),
	'updated_at' =>complaintUserInformation('updated_at'),
	'updated_by' =>complaintUserInformation('updated_by'),
	'request_ip' =>complaintUserInformation('request_ip'),
  ))
  ->execute();
  }
  else {
   db_update('aai_complaint_source') 
  ->fields(array(
    'complain_source' => $form_state['values']['complain_source'],
	'updated_at' =>complaintUserInformation('updated_at'),
	'updated_by' =>complaintUserInformation('updated_by'),
	'request_ip' =>complaintUserInformation('request_ip'),
  ))
  ->condition('complain_source_id', $form_state['values']['complain_source_id'], '=')
  ->execute();	
  }

  drupal_set_message(t('Complaint source %source has been saved.', array('%source' => $form_state['values']['complain_source'])));
  watchdog('complaint', 'Source %source has been saved.', array('%source' => $form_state['values']['complain_source']), WATCHDOG_NOTICE, l(t('Edit'), 'admin/structure/complaint/source/edit/' . $form_state['values']['complain_source_id']));
  $form_state['redirect'] = 'admin/structure/complaint/source';
}


function complaint_source_delete_form($form, &$form_state,  $source_id) {

  $source_data = getSource((int) $source_id);
  $form['complain_source_id'] = array(
    '#type' => 'value',
    '#value' => $source_id,
  );
  $form['complain_source'] = array(
    '#type' => 'value',
    '#value' => $source_data['complain_source'],
  );

  return confirm_form(
    $form,
    t('Are you sure you want to delete %source?', array('%source' => $source_data['complain_source'])),
    'admin/structure/complaint/source',
    t('%source will remove from complaint form source listing.', array('%source' => $source_data['complain_source'])),
    t('Delete'),
    t('Cancel')
  );
}


function complaint_source_delete_form_submit($form, &$form_state) {
  $source_id = $form['complain_source_id']['#value'];
  $source_name = $form['source_name']['#value'];
  
  db_update('aai_complaint_source')
	  ->fields(array(
		'status' => 0,
		'updated_at' =>complaintUserInformation('updated_at'),
		'updated_by' =>complaintUserInformation('updated_by'),
		'request_ip' =>complaintUserInformation('request_ip'),
	  ))
    ->condition('complain_source_id', $source_id)
    ->execute();

  drupal_set_message(t('Source %source has been deleted.', array('%source' => $source_name)));
  watchdog('complaint', 'Source %source has been deleted.', array('%source' => $source_name), WATCHDOG_NOTICE);

  $form_state['redirect'] = 'admin/structure/complaint/source';
}

function getSource($source_id) {
  $source = db_select('aai_complaint_source', 'cs')
    ->addTag('translatable')
    ->fields('cs' , array('complain_source_id', 'complain_source','email_sent_to_complainer','dispose_off_response_sent_to_complainer','complaint_assign_forward'))
    ->condition('complain_source_id', $source_id)
    ->execute()
    ->fetchAssoc();
	if(empty($source)){
		return  array(
		'complain_source' => '',
		'complain_source_id' => NULL,
	);
  }else{
	return $source;
  }
}


/**
 * get user permissions.
 */
function getAirportUserPermission($user_id) {
	$user_airports_lists = array();
	$result = db_select('aai_complaint_permission', 'acp')
	->addTag('translatable')
	->fields('acp' , array('tid'))
	->condition('uid', $user_id)
	->execute();
		while($user_airports_list = $result->fetchAssoc()){
			$user_airports_lists[] = $user_airports_list['tid'];
		}
		
	return $user_airports_lists;
}


/**
* get complaint status
*/

function getComplaintStatus(){
	$subcategory = db_select('aai_complaint_subcategory', 'sc')
    ->addTag('translatable')
    ->fields('sc' , array('subcategory_id','category_id', 'subcategory_name'))
    ->condition('subcategory_id', $subcategory_id)
    ->execute()
    ->fetchAssoc();
	if(empty($subcategory)){
		return  array(
		'subcategory_name' => '',
		'subcategory_id' => NULL,
		'category_id' => NULL,
	);
  }else{
	return $subcategory;
  }

}



function complaint_offline_edit_form(){
	$_SESSION['aaiuser_complaint_form_entry'] = 'Y';
	drupal_goto("/complaint");
}


function complaint_manager(){

	$rows = array();
	$build['complaint_permission_filter_form'] = drupal_get_form('complaint_permission_filter_form');
  
	$header = array(
		array('data' => t('Airport/Region'), 'field' => 'ap.name'),
		array('data' => t('User'), 'field' => 'u.uid'),
		array('data' => t('Operations'), 'colspan' => 4),
	);

	$query = db_select('aai_complaint_permission', 'cp')->extend('PagerDefault')->extend('TableSort');
	$query->leftJoin('users', 'u', 'cp.uid = u.uid');
	$query->leftJoin('taxonomy_term_data', 'ap', 'cp.tid = ap.tid AND ap.vid IN ( '.getAaiAirportVariable('aai_airport_vid').','.getAaiAirportVariable('aai_region_vid').','.getAaiAirportVariable('aai_airlines_vid').')');
	$query->fields('cp', array('tid', 'uid', 'status','updated_by'));
	$query->addField('u','name');
	$query->addField('ap', 'name');
	
	if (!empty($_SESSION['complaint_permission_filter']['airport'])) {
		$airport_val = explode(",",$_SESSION['complaint_permission_filter']['airport']);
		$query->condition('cp.tid', $airport_val[1],'=');
	}
	
	$result = $query
		->limit(50)
		->orderByHeader($header)
		->execute();
		

  foreach ($result as $complain_permission) {
    $rows[] = array('data' =>
      array(
		  array('data'=>$complain_permission->ap_name),
		  check_plain($complain_permission->name),
		  l(t('Revoke'), 'admin/structure/complaint/view/' . $complain_permission->uid),
      ),
    );
  }
  
  $build['complaint_permission_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
	'#attributes' => array('class' => array('complaint_permission_table')),
    '#empty' => t('No permissible users available for the airport.'),
	'#suffix' => l(t('Assign Complaint Manager'), 'admin/structure/complaint/complaint_manager/assign'),
  );
  
  $build['complaint_permission_pager'] = array('#theme' => 'pager');
  return $build;
}


function complaint_permission_filter_form($form){
	//echo '<pre>';
	//var_dump(arg()); die;
	if(arg(4)=='assign_user_msg' && !empty(arg(5))){
		if(arg(5)==1){
			drupal_set_message(t('You have assigned permissions successfully!'));
		}elseif(arg(5)==2){
			drupal_set_message(t('Permission for the selected complaint manager with respect to airport; already exists!'),'error');
		}
	}
	
	// Get all the regions from the database.	
	$airportswithselect = array(""=>"ALL");
	
	$airports_region_result = db_select('taxonomy_term_data', 'ap')
	->addTag('translatable')
	->fields('ap', array('tid', 'name'))
	->condition('vid', getAaiAirportVariable('aai_region_vid'),'=')
	->orderBy('name')
	->execute();
	 while($airports_region = $airports_region_result->fetchAssoc()) {
		$airports_region_index = '1'.','.$airports_region['tid'];
		$airports_regions[$airports_region_index] = $airports_region['name'];
	 }
	 $airportswithselect = array(""=>"ALL");
	
	// Get all the airlines from the database.	
	$airlines = array();
	$airlines_tag = array("airlines"=>"...Airlines...");
	$airlines_result = db_select('taxonomy_term_data', 'ar')
	->addTag('translatable')
	->fields('ar', array('tid', 'name'))
	->condition('vid', getAaiAirportVariable('aai_airlines_vid'),'=')
	->orderBy('name')
	->execute();
	 while($airline = $airlines_result->fetchAssoc()) {
		$airlines_index = '3'.','.$airline['tid'];
		$airlines[$airlines_index] = $airline['name'];
	 }
	
	if(!empty($airlines)) {$airlines = $airlines_tag+$airlines;}
	
	// Get all the airports from the database.
	$airports = array();
	$airports_tags = array("airports"=>"...Airports...");
	$airports_result = db_select('taxonomy_term_data', 'ap')
	->addTag('translatable')
	->fields('ap', array('tid', 'name'))
	->condition('vid', getAaiAirportVariable('aai_airport_vid'),'=')
	->orderBy('name')
	->execute();
	 while($airport = $airports_result->fetchAssoc()) {
		$airports_index = '2'.','.$airport['tid'];
		$airports[$airports_index] = $airport['name'];
	}
	
	if(!empty($airports)) {$airports = $airports_tags+$airports;}
	
	$airports = $airportswithselect+$airports_regions+$airlines+$airports;
	
	$form['filters'] = array(
		'#type' => 'fieldset',
		'#title' => t('Filter Complaint Manager Airport Wise'),
		'#collapsible' => TRUE,
		'#collapsed' => FALSE,
		'#attributes' => array('class' => array('complaint_permission_table')),
	);
	$form['filters']['airport'] = array(
		'#type' => 'select',
		'#title' => t('Airport/Region'),
		'#options' => $airports,
		'#required' => TRUE,
		'#default_value' => isset($_SESSION['complaint_permission_filter']['airport'])?$_SESSION['complaint_permission_filter']['airport']:'',
		
	);
	$form['filters']['actions'] = array(
		'#type' => 'actions',
		'#attributes' => array('class' => array('container-inline')),
	);
	$form['filters']['actions']['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit'),
	);
	if (!empty($_SESSION['complaint_permission_filter'])) {
		$form['filters']['actions']['reset'] = array(
			'#type' => 'submit',
			'#value' => t('Reset')
		);
	}
	$form['#action'] = url('admin/structure/complaint/complaint_manager');
	return $form;
}


function complaint_permission_filter_form_validate($form, &$form_state) {

}

function complaint_permission_filter_form_submit($form, &$form_state) {

  $op = $form_state['values']['op'];
  switch ($op) {
    case t('Submit'):
        if (isset($form_state['values']['airport'])) {
          $_SESSION['complaint_permission_filter']['airport'] = $form_state['values']['airport'];
		}
      break;
    case t('Reset'):
      $_SESSION['complaint_permission_filter'] = array();
      break;
  }
  
}

function complaint_manager_assign_form($form, $form_state){
	//print_r(arg(6));
	if(arg(5)=='error' && !empty(arg(6))){
		if(arg(6)==1){
			drupal_set_message(t('Please select complaint manager and airport/region to continue'),'error');
		} else if(arg(6)==2) {
			drupal_set_message(t('Please select complaint manager to continue'),'error');
		} else if(arg(6)==3) {
			drupal_set_message(t('Please select airport to continue'),'error');
		}
	}

	// Get all the regions from the database.	
	$airportswithselect = array(""=>"ALL");
	
	
	$airports_region_result = db_select('taxonomy_term_data', 'ap')
	->addTag('translatable')
	->fields('ap', array('tid', 'name'))
	->condition('vid', getAaiAirportVariable('aai_region_vid'),'=')
	->orderBy('name')
	->execute();
	 while($airports_region = $airports_region_result->fetchAssoc()) {
		$airports_region_index = '1'.','.$airports_region['tid'];
		$airports_regions[$airports_region_index] = $airports_region['name'];
	 }
	 $airportswithselect = array(""=>"ALL");
	
	// Get all the airlines from the database.	
	$airlines = array();
	$airlines_tag = array("airlines"=>"...Airlines...");
	$airlines_result = db_select('taxonomy_term_data', 'ar')
	->addTag('translatable')
	->fields('ar', array('tid', 'name'))
	->condition('vid', getAaiAirportVariable('aai_airlines_vid'),'=')
	->orderBy('name')
	->execute();
	 while($airline = $airlines_result->fetchAssoc()) {
		$airlines_index = '3'.','.$airline['tid'];
		$airlines[$airlines_index] = $airline['name'];
	 }
	
	if(!empty($airlines)) {$airlines = $airlines_tag+$airlines;}
	
	// Get all the airports from the database.
	$airports = array();
	$airports_tags = array("airports"=>"...Airports...");
	
	$airports_result = db_select('taxonomy_term_data', 'ap')
	->addTag('translatable')
	->fields('ap', array('tid', 'name'))
	->condition('vid', getAaiAirportVariable('aai_airport_vid'),'=')
	->orderBy('name')
	->execute();
	 while($airport = $airports_result->fetchAssoc()) {
		$airports_index = '2'.','.$airport['tid'];
		$airports[$airports_index] = $airport['name'];
	 }
	
	if(!empty($airports)) {$airports = $airports_tags+$airports;}
	$airports = $airportswithselect+$airports_regions+$airlines+$airports;
		
	// Get all the complaint maintainer from the database.
	$query = db_select('users_roles', 'usr');
	$query->join('users', 'u', 'usr.uid = u.uid');
	$query->fields('u', array('uid','name'));
	$query->condition('usr.rid', getAaiAirportVariable('aai_airport_complaint_manager_role_id'),'=');
	$query->orderBy('name');
	$users_result = $query->execute();
	$users[''] = "Select";
	
	while($user = $users_result->fetchAssoc()) {
		$users[$user['uid']] = $user['name'];
	}
	
	$form['assign_permission'] = array(
		'#type' => 'fieldset',
		'#title' => t('Assign Airport/Region To Complaint Manager'),
		'#collapsible' => TRUE,
		'#collapsed' => FALSE,
		'#attributes' => array('class' => array('complaint_permission_table')),
	);
	
	$form['assign_permission']['airport'] = array(
		'#type' => 'select',
		'#title' => t('Airport/Region'),
		'#options' => $airports,
		'#required' => TRUE,
		'#default_value' => isset($_SESSION['complaint_permission_filter']['airport'])?$_SESSION['complaint_permission_filter']['airport']:'',
	);
	
	$form['assign_permission']['user'] = array(
		'#type' => 'select',
		'#title' => t('Complaint Manager'),
		'#options' => $users,
		'#required' => TRUE,
		'#default_value' => isset($_SESSION['complaint_permission_filter']['user'])?$_SESSION['complaint_permission_filter']['user']:'',
	);
	
	$form['assign_permission']['actions'] = array(
		'#type' => 'actions',
		'#attributes' => array('class' => array('container-inline')),
	);
	
	$form['assign_permission']['actions']['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit'),
	);
	
	$form['#action'] = url('admin/structure/complaint/complaint_manager/assign/aasign_confirm');
	return $form;
}

function aasign_confirm_form($form, &$form_state){
	//echo '<pre>';
	//print_r($form_state['input']);
	$user = (int) $form_state['input']['user'];
	$airport = $form_state['input']['airport'];
	
	$_SESSION['complaint_permission_filter']['airport'] = $airport;
	$_SESSION['complaint_permission_filter']['user'] = $user;
	
	if(empty($user) || empty($airport)){
		
		if(empty($user) && empty($airport)){
			$error = 1;
		}
		else if(empty($user)){
			$error = 2;
		}
		else if(empty($airport)){
			$error = 3;
		}
		drupal_goto("admin/structure/complaint/complaint_manager/assign/error/".$error);
		return;
	}
		
	$form['user'] = array(
		'#type' => 'hidden',
		'#value' => $user,
	);
	  
	$form['airport'] = array(
		'#type' => 'hidden',
		'#value' => $airport,
	);
	
	// Get airports/regions from the database.
	
	$airport_val = explode(",",$form_state['input']['airport']);
	$vid = array();
	$vid[] = getAaiAirportVariable('aai_airport_vid');
	$vid[] = getAaiAirportVariable('aai_region_vid');
	$airports = db_select('taxonomy_term_data', 'ap')
	->addTag('translatable')
	->fields('ap', array('tid', 'name'))
	->condition('vid', $vid,'IN')
	->condition('tid', $airport_val[1],'=')
	->execute()
	->fetchAssoc();
	
	// Get complaint maintainer from the database.
	$query = db_select('users_roles', 'usr');
	$query->join('users', 'u', 'usr.uid = u.uid');
	$query->fields('u', array('uid','name'));
	$query->condition('usr.rid', getAaiAirportVariable('aai_airport_complaint_manager_role_id'),'=');
	$query->condition('usr.uid', $user,'=');
	$users = $query->execute()->fetchAssoc();
	
	return confirm_form(
		$form,
		t('Are you sure you want to give permission %user to manage %airport ?', array('%user' => $users['name'],'%airport'=>$airports['name'])),
		'admin/structure/complaint/complaint_manager/assign/aasign',
		t('%user will have permission to manage %airport complaint.', array('%user' => $users['name'],'%airport'=>$airports['name'])),
		t('Assign now'),
		t('Assign later?')
	);
}

function aasign_confirm_form_validate($form, &$form_state) {
//var_dump($form_state);
	
}

function aasign_confirm_form_submit($form, &$form_state) {
	//echo '<pre>';
	//print_r($form_state['values']); die;

	$user = (int) $form_state['values']['user'];
	$airport_val = explode(",",$form_state['input']['airport']);
	$airport = (int) $airport_val[1];
	$type = (int) $airport_val[0];
	
	$permission_list = db_select('aai_complaint_permission', 'acp')
	->fields('acp', array('tid'))
	->condition('tid', $airport,'=')
	->condition('uid', $user,'=')
	->condition('type', $type,'=')
	->condition('status', 1,'=')
	->execute()
	->fetchAssoc();
	
	//var_dump($permission_list['tid']); die;
	
	if(empty($permission_list['tid'])){
	
		$lastinsertid = db_insert('aai_complaint_permission')
		->fields(array(
			'tid' => $airport,
			'type' => $type,
			'uid' => $user,
			'status' => 1,
			'created_at' =>complaintUserInformation('created_at'),
			'updated_by' =>complaintUserInformation('updated_by'),
			'request_ip' =>complaintUserInformation('request_ip'),
			)
		)->execute();
		
		$succ = 1;
	
	} else{
		$succ = 2;
	}
	drupal_goto("admin/structure/complaint/complaint_manager/assign_user_msg/".$succ);
}


function webform_options_status(){

$status = 
    array (
	  '' => t('Select'),
	  '1' => t('New'),
      '2' => t('Re-assigned'),
      '3' => t('Closed'),
	  '1,2' => t('Pending (New+re-assigned)'),
  );
  return $status;

}
