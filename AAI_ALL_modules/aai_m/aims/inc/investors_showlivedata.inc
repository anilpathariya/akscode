<?php
/**
 * @file
 *   file to list type of reports available under Airport.
 */
function investors_showlivedata_intnl($type) {
  $block = array();
  $query = db_select('NEWWEB_AIMS_DATA', 'ad');
  $query->groupBy('ad.AIMS_LOCAL_AIRPORT');
$query->groupBy('ad.AIMS_AIRPORT_TYPE'); //GROUP BY user ID
$query->groupBy('ad.AIMS_FLIGHT_CATEGORY');
$query->groupBy('ad.AIMS_DATE_TIME');
$query->addExpression('sum(AIMS_NOS_PASSENGER_ARRIVED)', 'AIMS_NOS_PASSENGER_ARRIVED');
$query->addExpression('sum(AIMS_NOS_PASSENGER_DEPARTED)', 'AIMS_NOS_PASSENGER_DEPARTED');
$query->addExpression('sum(AIMS_NOS_PASSENGER_TRANSIT)', 'AIMS_NOS_PASSENGER_TRANSIT');
$query->addExpression('0.001*sum(AIMS_WT_FREIGHT_IN)', 'AIMS_WT_FREIGHT_IN');
$query->addExpression('0.001*sum(AIMS_WT_FREIGHT_OUT)', 'AIMS_WT_FREIGHT_OUT');
$query->addExpression('max(AIMS_LASTUPDATE_DATE_TIME)', 'AIMS_LASTUPDATE_DATE_TIME');
$query->fields('ad',array('AIMS_FLIGHT_CATEGORY')); //SELECT the fields from node

$results = $query->execute()
->fetchAll();
	$output0="";	 
	
if($type == 'international')
{ 
  foreach ($results as $result) {
    $items = array();
	  $query = db_select('aims_parameter', 'ap')
				->fields('ap',array('prm_desc'))
				->condition('ap.prm_code', $result->AIMS_FLIGHT_CATEGORY,'=')
				->condition('ap.prm_type', 'FLIGHT_CATEGORY','='); 
	  $categories = $query->execute()
			->fetchAll();
	  foreach ($categories as $category) {
	  $items[] = ("<div class='heading-data'>".$category->prm_desc."</div>");		 
	  }
  
	 if($result->AIMS_FLIGHT_CATEGORY =='0') {		  
	  if($result->AIMS_FLIGHT_CATEGORY =='0') {
				$items[] = ("<span class='text-distance'>".t('Arrived')."</span>: ".$result->AIMS_NOS_PASSENGER_ARRIVED);
				$items[] = ("<span class='text-distance'>".t('Departed')." </span>: ".$result->AIMS_NOS_PASSENGER_DEPARTED);
				$items[] = ("<span class='text-distance'>".t('Transit')." </span>: ".$result->AIMS_NOS_PASSENGER_TRANSIT);
				if (!empty($items)) {
					$output0 = theme('item_list', array('items' => $items));
				}
		  }
	  }

	 $lastupdate =t('Last updated').date('d-m-Y H:i A',strtotime($result->AIMS_LASTUPDATE_DATE_TIME));
		 
	}
	  //$output = t('No data available.');

	if (empty($output0)) {
		$items = array();
			$items[] = t('International');
		$items[] = t('Arrived')." : 0";
		$items[] = t('Departed')." : 0";
		$items[] = t('Transit')." : 0";
		if (!empty($items)) {
			$output0 = theme('item_list', array('items' => $items));
		}
	}
	if(empty($lastupdate)) {
		$lastupdate = t('Last updated') . date('d-M-Y');
	}

}

if($type == 'domestic')
{
 foreach ($results as $result) {
	$items = array();
	$query = db_select('aims_parameter', 'ap')
	->fields('ap',array('prm_desc'))
	->condition('ap.prm_code', $result->AIMS_FLIGHT_CATEGORY,'=')
	->condition('ap.prm_type', 'FLIGHT_CATEGORY','=');

	$categories = $query->execute()
	->fetchAll();

	foreach ($categories as $category) {
	$items[] = ("<div  class='heading-data'>".$category->prm_desc."</div>");
 
	}
	if($result->AIMS_FLIGHT_CATEGORY =='1') {
		if($result->AIMS_FLIGHT_CATEGORY =='1') {
		$items[] = ("<span class='text-distance'>".t('Arrived')."</span>: ".$result->AIMS_NOS_PASSENGER_ARRIVED);
		$items[] = ("<span class='text-distance'>".t('Departed')." </span>: ".$result->AIMS_NOS_PASSENGER_DEPARTED);
		$items[] = ("<span class='text-distance'>".t('Transit')." </span>: ".$result->AIMS_NOS_PASSENGER_TRANSIT);
			if (!empty($items)) {
			$output0 = theme('item_list', array('items' => $items));
			}
		}
	}
	$lastupdate = t('Last updated') ." : ". date('d-m-Y H:i A',strtotime($result->AIMS_LASTUPDATE_DATE_TIME));
	} 
	if (empty($output0)) {
		$items = array();
		$items[] =  t('Domestic');
		$items[] =  t('Freight In')." : 0";
		$items[] =  t('Departed')." : 0";
		$items[] =  t('Transit')." : 0";
		if (!empty($items)) {
		  $output0 = theme('item_list', array('items' => $items));
		}
	}
	if(empty($lastupdate)) {
	  $lastupdate = t('Last updated')." : ". date('d-M-Y');
	}

}

if($type == 'cargo')
{
 foreach ($results as $result) {
	$items = array();
	$query = db_select('aims_parameter', 'ap')
	  ->fields('ap',array('prm_desc'))
	  ->condition('ap.prm_code', $result->AIMS_FLIGHT_CATEGORY,'=')
	  ->condition('ap.prm_type', 'FLIGHT_CATEGORY','=');
	$categories = $query->execute()
	->fetchAll();

	foreach ($categories as $category) {
	  $items[] = ("<div  class='heading-data'>".$category->prm_desc."</div>");
	}

	if($result->AIMS_FLIGHT_CATEGORY =='3') {
	$items[] = ("<span class='text-distance-out'>".t('Freight In')." </span>: ".round($result->AIMS_WT_FREIGHT_IN,2));
	$items[] = ("<span class='text-distance-out'>".t('Freight Out')." </span>: ".round($result->AIMS_WT_FREIGHT_OUT,2));

	  if (!empty($items)) {
	    $output0 = theme('item_list', array('items' => $items));
	  }
	}  

	$lastupdate = t('Last updated') ." : ". date('d-m-Y H:i A',strtotime($result->AIMS_LASTUPDATE_DATE_TIME));
	}

	if (empty($output0)) {
		$items = array();
		$items[] = t('Cargo');
		$items[] = t('Freight In')." : 0";
		$items[] = t('Freight Out')." : 0";
		if (!empty($items)) {
		  $output0 = theme('item_list', array('items' => $items));
		}
	}
	if(empty($lastupdate)) {
		$lastupdate = t('Last updated') ." : ". date('d-M-Y');
	}				
} 

  return $output0;
}
