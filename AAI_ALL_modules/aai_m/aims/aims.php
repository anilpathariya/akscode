<?php
/**
 * Implements hook_menu().
 */
function aims_menu() {
  $items = array();

  $items['investors/reports'] =  array (
    'page callback' => 'investors_reports',
    'access callback' => TRUE,
    'type' => MENU_CALLBACK,
    'file' => 'inc/investors_reports.inc',
    'file path' => drupal_get_path('module', 'aims'),
  );
  $items['investors/reports/traffic-news'] =  array (
    'page callback' => 'investors_traffic_reports',
    'access callback' => TRUE,
    'type' => MENU_CALLBACK,
    'file' => 'inc/investors_traffic_reports.inc',
    'file path' => drupal_get_path('module', 'aims'),
  );

  return $items;
}

/**
 * Implements hook_theme().
 */
function aims_theme($existing, $type, $theme, $path) {
  $themes = array(
    'investors_traffic_reports' => array(
      'template' => 'templates/investors_traffic_reports',
    ),
  );

  return $themes;
}

/**
 * Implements hook_block_info().
 */
function aims_block_info() {
  $blocks = array();
  $blocks['passenger_information'] = array (
    'info' => t('Passenger Information'),
  );

  return $blocks;
}

/**
 * Implements hook_block_view().
 */
function aims_block_view($delta = '') {
  $airport = arg(1);
  $block = array();
  switch ($delta) {
    case 'passenger_information':
      $block['subject'] = '';
	    $block['content'] = aims_BLOCK_ABC_CONTENT($airport);
	  /*$variable= array();
	  $variable= aims_BLOCK_ABC_CONTENT();
	  $list = array(
        '#theme' => 'links',
        '#links' => array(),
        '#prefix' => '<div class="my-links"><h2>Related Links</h2>',
        '#suffix' => '</div>',
      );
	  //echo "<pre>";
	//print_r($variable);
	$block_content= "";
	  foreach ($variable as $record) {
		 // print_r($record);
        $list['#links'][] = array('CATEGORY' => $record['AIMS_FLIGHT_CATEGORY'], 'ARRIVED' => $record['AIMS_NOS_PASSENGER_ARRIVED']);
		$block_content .= "Category : ". $record['AIMS_FLIGHT_CATEGORY']."<br> Arrived ". $record['AIMS_NOS_PASSENGER_ARRIVED'];
		
		$block_content .= "<br> <br>";
      }
	  
	  /*$result_set = db_query("SELECT ad.AIMS_FLIGHT_CATEGORY AS AIMS_FLIGHT_CATEGORY, sum(AIMS_NOS_PASSENGER_ARRIVED) AS AIMS_NOS_PASSENGER_ARRIVED, sum(AIMS_NOS_PASSENGER_DEPARTED) AS AIMS_NOS_PASSENGER_DEPARTED, sum(AIMS_NOS_PASSENGER_TRANSIT) AS AIMS_NOS_PASSENGER_TRANSIT, 0.001*sum(AIMS_WT_FREIGHT_IN) AS AIMS_WT_FREIGHT_IN, 0.001*sum(AIMS_WT_FREIGHT_OUT) AS AIMS_WT_FREIGHT_OUT FROM {NEWWEB_AIMS_DATA} ad GROUP BY ad.AIMS_LOCAL_AIRPORT");
	  
	  $block_content= "";
	  while ($result = db_fetch_array($result_set)) {
		 // print_r($record);
        $block_content .= "Category : ". $variables['AIMS_FLIGHT_CATEGORY']." Arrived ". $variables['AIMS_NOS_PASSENGER_ARRIVED'];
      }*/
	  
	  //$block['content'] = $block_content;
	  
      break;
  }

  return $block;
}

function aims_BLOCK_ABC_CONTENT($airport) {
  $block = array();
	$query = db_select('NEWWEB_AIMS_DATA', 'ad');
	$query->groupBy('ad.AIMS_LOCAL_AIRPORT');
	$query->groupBy('ad.AIMS_AIRPORT_TYPE');//GROUP BY user ID
	$query->groupBy('ad.AIMS_FLIGHT_CATEGORY');
	$query->groupBy('ad.AIMS_DATE_TIME');
	$query->addExpression('sum(AIMS_NOS_PASSENGER_ARRIVED)', 'AIMS_NOS_PASSENGER_ARRIVED');
	$query->addExpression('sum(AIMS_NOS_PASSENGER_DEPARTED)', 'AIMS_NOS_PASSENGER_DEPARTED');
	$query->addExpression('sum(AIMS_NOS_PASSENGER_TRANSIT)', 'AIMS_NOS_PASSENGER_TRANSIT');
	$query->addExpression('0.001*sum(AIMS_WT_FREIGHT_IN)', 'AIMS_WT_FREIGHT_IN');
	$query->addExpression('0.001*sum(AIMS_WT_FREIGHT_OUT)', 'AIMS_WT_FREIGHT_OUT');
	$query->addExpression('max(AIMS_LASTUPDATE_DATE_TIME)', 'AIMS_LASTUPDATE_DATE_TIME');
  $query->fields('ad',array('AIMS_FLIGHT_CATEGORY'));//SELECT the fields from node
	$query->where("AIMS_DATE_TIME = '".date('Y-m-d')."' ");
	$query->where("AIMS_LOCAL_AIRPORT = '".$airport."' ");
   // ->orderBy('created', 'DESC')//ORDER BY created
   // ->range(0,2);//LIMIT to 2 records
	//print $query;
    $results = $query->execute()
			->fetchAll();
	 
	 $output2="";
	 $output1="";
	 $output0="";
	 
	foreach ($results as $result) {
	  $items = array();
	  $query = db_select('aims_parameter', 'ap')
				->fields('ap',array('prm_desc'))
				->condition('ap.prm_code', $result->AIMS_FLIGHT_CATEGORY,'=')
				->condition('ap.prm_type', 'FLIGHT_CATEGORY','=');
	 
	  $categories = $query->execute()
			->fetchAll();
	
	  foreach ($categories as $category) {
		  $items[] = ($category->prm_desc);
	  }
	
	  if($result->AIMS_FLIGHT_CATEGORY =='3') {
		  $items[] = ("<span class='text-distance-out'>Freight In </span>: ".round($result->AIMS_WT_FREIGHT_IN,2));
		  $items[] = ("<span class='text-distance-out'>Freight Out </span>: ".round($result->AIMS_WT_FREIGHT_OUT,2));
		
		  if (!empty($items)) {
        $output2 = theme('item_list', array('items' => $items));
		  }
	  } else {
		  if($result->AIMS_FLIGHT_CATEGORY =='1') {
        $items[] = ("<span class='text-distance'>Arrived </span>: ".$result->AIMS_NOS_PASSENGER_ARRIVED);
				$items[] = ("<span class='text-distance'>Departed </span>: ".$result->AIMS_NOS_PASSENGER_DEPARTED);
				$items[] = ("<span class='text-distance'>Transit </span>: ".$result->AIMS_NOS_PASSENGER_TRANSIT);
				if (!empty($items)) {
			    $output1 = theme('item_list', array('items' => $items));
				}
		  }
		  
      if($result->AIMS_FLIGHT_CATEGORY =='0') {
				$items[] = ("<span class='text-distance'>Arrived </span>: ".$result->AIMS_NOS_PASSENGER_ARRIVED);
				$items[] = ("<span class='text-distance'>Departed </span>: ".$result->AIMS_NOS_PASSENGER_DEPARTED);
				$items[] = ("<span class='text-distance'>Transit </span>: ".$result->AIMS_NOS_PASSENGER_TRANSIT);
				if (!empty($items)) {
					$output0 = theme('item_list', array('items' => $items));
				}
		  }
	  }

	  $lastupdate ="Last updated: ".date('d-m-Y H:i A',strtotime($result->AIMS_LASTUPDATE_DATE_TIME));
	  //$lastupdate = date_format($lastupdate, 'd-m-Y H:i:s');
	  //$lastupdate->format('d-M-Y H:i:s');
  }
  //$output = t('No data available.');

	if (empty($output0)) {
		$items = array();
		$items[] =("International");
		$items[] = ("Arrived  : 0");
		$items[] = ("Departed : 0");
		$items[] = ("Transit  : 0");
		if (!empty($items)) {
			$output0 = theme('item_list', array('items' => $items));
		}
	}

	if (empty($output1)) {
		$items = array();
		$items[] =("Domestic");
		$items[] = ("Arrived  : 0");
		$items[] = ("Departed : 0");
		$items[] = ("Transit  : 0");
		if (!empty($items)) {
			$output1 = theme('item_list', array('items' => $items));
		}
	}

	if (empty($output2)) {
		$items = array();
		$items[] =("Cargo");
		$items[] = ("Freight In      : 0");
		$items[] = ("Freight Out     : 0");
		if (!empty($items)) {
			$output2 = theme('item_list', array('items' => $items));
		}
	}

	if(empty($lastupdate)) {
		$lastupdate = "Last updated: " . date('d-M-Y');
	}

  return $output0 . $output1 . $output2 . $lastupdate;
}

/**
 * Implements hook_preprocess_HOOK(&$variables)
 */
function aims_preprocess_investors_traffic_reports(&$variables) {
  // fetch AIMS data for airport.
  $tbl = 'NEWWEB_AIMS_MONTHS';
  $qry = db_select($tbl,'tbl');
  $qry->fields('tbl');
  $rs = $qry->execute();

  // array's to hold report data.
  $recordset = array();
  $recordset['Aircraft_Movements_(In_Nos.)'] = array();
  $recordset['Passengers_(In_Nos.)'] = array();
  $recordset['Freight_(In_Tonnes.)'] = array();

  // array to hold total under different category.
  $rs_total = array();

  while($obj = $rs->fetchObject()) {
    $AIMS_LOCAL_AIRPORT = $obj->AIMS_LOCAL_AIRPORT;
    $AIMS_AIRPORT_TYPE = $obj->AIMS_AIRPORT_TYPE;
    $AIMS_FLIGHT_CATEGORY = $obj->AIMS_FLIGHT_CATEGORY;
    $AIMS_YYYY_MM = $obj->AIMS_YYYY_MM;
    $AIMS_NOS_CRAFT_ARRIVED = $obj->AIMS_NOS_CRAFT_ARRIVED;
    $AIMS_NOS_CRAFT_DEPARTED = $obj->AIMS_NOS_CRAFT_DEPARTED;
    $AIMS_NOS_PASSENGER_ARRIVED = $obj->AIMS_NOS_PASSENGER_ARRIVED;
    $AIMS_NOS_PASSENGER_DEPARTED = $obj->AIMS_NOS_PASSENGER_DEPARTED;
    $AIMS_NOS_PASSENGER_TRANSIT = $obj->AIMS_NOS_PASSENGER_TRANSIT;
    $AIMS_WT_FREIGHT_IN = $obj->AIMS_WT_FREIGHT_IN;
    $AIMS_WT_FREIGHT_OUT = $obj->AIMS_WT_FREIGHT_OUT;

    // consolidate International flights data.
    if ($AIMS_FLIGHT_CATEGORY == 0) {
      $flight_cat = 'int';
    } else {
      $flight_cat = 'dom';
    }

    switch ($AIMS_AIRPORT_TYPE) {
      case 0 : // internation airport
        // get airplanes data
        $tmp_craft = ($AIMS_NOS_CRAFT_ARRIVED + $AIMS_NOS_CRAFT_DEPARTED);
        if (array_key_exists('international', $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat])) {
          $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['international'] += $tmp_craft;
        } else {
          $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['international'] = $tmp_craft;
        }
        $rs_total['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_craft;
        unset($tmp_craft);

        // get passengers data
        $tmp_psng = $AIMS_NOS_PASSENGER_ARRIVED + $AIMS_NOS_PASSENGER_DEPARTED + 
        $AIMS_NOS_PASSENGER_TRANSIT;
        if (array_key_exists('international', $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat])) {
          $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['international'] += $tmp_psng;
        } else {
          $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['international'] = $tmp_psng;
        }
        $rs_total['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_psng;
        unset($tmp_psng);

        // get freight data
        $tmp_freight = $AIMS_WT_FREIGHT_IN + $AIMS_WT_FREIGHT_OUT;
        if (array_key_exists('international', $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat])) {
          $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat]['international'] += $tmp_freight;
        } else {
          $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat]['international'] = $tmp_freight;
        }
        $rs_total['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_freight;
        unset($tmp_freight);
      	break;

      case 1 : // domestic airport
        // get airplanes data
        $tmp_craft = ($AIMS_NOS_CRAFT_ARRIVED + $AIMS_NOS_CRAFT_DEPARTED);
        if (array_key_exists('domestic', $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat])) {
       	  $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['domestic'] += $tmp_craft;
        } else {
          $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['domestic'] = $tmp_craft;
        }
        $rs_total['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_craft;
        unset($tmp_craft);

        // get passengers data
        $tmp_psng = $AIMS_NOS_PASSENGER_ARRIVED + $AIMS_NOS_PASSENGER_DEPARTED + 
        $AIMS_NOS_PASSENGER_TRANSIT;
        if (array_key_exists('domestic', $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat])) {
          $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['domestic'] += $tmp_psng;
        } else {
          $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['domestic'] = $tmp_psng;
        }
        $rs_total['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_psng;
        unset($tmp_psng);

        // get freight data
        $tmp_freight = $AIMS_WT_FREIGHT_IN + $AIMS_WT_FREIGHT_OUT;
        if (array_key_exists('domestic', $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat])) {
          $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat]['domestic'] += $tmp_freight;
        } else {
          $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat]['domestic'] = $tmp_freight;
        }
        $rs_total['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_freight;
        unset($tmp_freight);
      	break;
      	
      case 2 : // JV airport
        // get airplanes data
        $tmp_craft = ($AIMS_NOS_CRAFT_ARRIVED + $AIMS_NOS_CRAFT_DEPARTED);
        if (array_key_exists('jv', $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat])) {
          $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['jv'] += $tmp_craft;
        } else {
          $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['jv'] = $tmp_craft;
        }
        $rs_total['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_craft;
        unset($tmp_craft);

        // get passengers data
        $tmp_psng = $AIMS_NOS_PASSENGER_ARRIVED + $AIMS_NOS_PASSENGER_DEPARTED + 
        $AIMS_NOS_PASSENGER_TRANSIT;
        if (array_key_exists('jv', $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat])) {
          $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['jv'] += $tmp_psng;
        } else {
          $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['jv'] = $tmp_psng;
        }
        $rs_total['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_psng;
        unset($tmp_psng);

        // get freight data
        $tmp_freight = $AIMS_WT_FREIGHT_IN + $AIMS_WT_FREIGHT_OUT;
        if (array_key_exists('jv', $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat])) {
          $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat]['jv'] += $tmp_freight;
        } else {
          $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat]['jv'] = $tmp_freight;
        }
        $rs_total['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_freight;
        unset($tmp_freight);
        break;
 
      case 3 : // custom airport
        // get airplanes data
        $tmp_craft = ($AIMS_NOS_CRAFT_ARRIVED + $AIMS_NOS_CRAFT_DEPARTED);
        if (array_key_exists('custom', $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat])) {
          $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['custom'] += $tmp_craft;
        } else {
          $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['custom'] = $tmp_craft;
        }
        $rs_total['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_craft;
        unset($tmp_craft);

        // get passengers data
        $tmp_psng = $AIMS_NOS_PASSENGER_ARRIVED + $AIMS_NOS_PASSENGER_DEPARTED + 
        $AIMS_NOS_PASSENGER_TRANSIT;
        if (array_key_exists('custom', $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat])) {
       	  $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['custom'] += $tmp_psng;
        } else {
          $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['custom'] = $tmp_psng;
        }
        $rs_total['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_psng;
        unset($tmp_psng);

        // get freight data
        $tmp_freight = $AIMS_WT_FREIGHT_IN + $AIMS_WT_FREIGHT_OUT;
        if (array_key_exists('custom', $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat])) {
          $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat]['custom'] += $tmp_freight;
        } else {
          $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat]['custom'] = $tmp_freight;
        }
        $rs_total['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_freight;
        unset($tmp_freight);
      	break;
      	
      case 4 : // other airports
        // get airplanes data
        $tmp_craft = ($AIMS_NOS_CRAFT_ARRIVED + $AIMS_NOS_CRAFT_DEPARTED);
        if (array_key_exists('other', $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat])) {
          $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['other'] += $tmp_craft;
        } else {
          $recordset['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['other'] = $tmp_craft;
        }
        $rs_total['Aircraft_Movements_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_craft;
        unset($tmp_craft);

        // get passengers data
        $tmp_psng = $AIMS_NOS_PASSENGER_ARRIVED + $AIMS_NOS_PASSENGER_DEPARTED + 
        $AIMS_NOS_PASSENGER_TRANSIT;
        if (array_key_exists('other', $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat])) {
          $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['other'] += $tmp_psng;
        } else {
          $recordset['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat]['other'] = $tmp_psng;
        }
        $rs_total['Passengers_(In_Nos.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_psng;
        unset($tmp_psng);

        // get freight data
        $tmp_freight = $AIMS_WT_FREIGHT_IN + $AIMS_WT_FREIGHT_OUT;
        if (array_key_exists('other', $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat])) {
          $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat]['other'] += $tmp_freight;
        } else {
          $recordset['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat]['other'] = $tmp_freight;
        }
        $rs_total['Freight_(In_Tonnes.)'][$AIMS_YYYY_MM][$flight_cat] += $tmp_freight;
        unset($tmp_freight);
        break;

    } // switch ends
  } // while ends
  
  // Structure the report output
  $out = "<table class = 'aai-reports traffic-summary'>";
    $out .= "<tr>
      <th rowspan = '2' class = 'aai-align-center'>AIRPORT CATEGORY</th>
      <th colspan = '3' class = 'aai-align-center'>FOR THE MONTH OF JANUARY</th>
      <th colspan = '3' class = 'aai-align-center'>TRAFFIC (APRIL - JANUARY)</th>
    </tr>
    <tr>
      <th>2016</th>
      <th>2015</th>
      <th>% CHANGE</th>
      <th>2015-16</th>
      <th>2014-15</th>
      <th>% CHANGE</th>
    </tr>";
    foreach ($recordset as $report_head => $ary) {
      // set report section head
      // International section
      $out .= "<tr><td class = 'report-section-head aai-bold' colspan = '7'>";
      $out .= t(ucwords(str_replace('_', ' ', $report_head))) . "</td></tr>";
      $out .= "<tr><td class = 'report-section-sub-head aai-bold' colspan = '7'>";
      $out .= t(ucwords('International')) . "</td></tr>";
      foreach (array('international', 'jv', 'custom', 'domestic', 'other') as $airport_type) {
      	$out .= "<tr>";
	      $out .= "<td>" . t(ucwords("$airport_type airports")) . "</td>";
	      $out .= "<td class = 'aai-num'>" . $ary['201601']['int'][$airport_type] . "</td>";
	      $out .= "<td class = 'aai-num'>" . $ary['201501']['int'][$airport_type] . "</td>";
	      $_change = _get_percent_difference($ary['201601']['int'][$airport_type], $ary['201501']['int'][$airport_type]);
	      $out .= "<td class = 'aai-num'>" . $_change . "</td>";
	      $out .= "<td class = 'aai-num'>" . $ary['201512']['int'][$airport_type] . "</td>";
	      $out .= "<td class = 'aai-num'>" . $ary['201412']['int'][$airport_type] . "</td>";
	      $_change = _get_percent_difference($ary['201512']['int'][$airport_type], $ary['201412']['int'][$airport_type]);
	      $out .= "<td class = 'aai-num'>" . $_change . "</td>";
        $out .= "<tr>";
      }
      // add row to show category total for international airlines
      $out .= "<tr  class = 'report-total'>";
        $out .= "<td class = 'aai-bold'>TOTAL</td>";
        $out .= "<td class = 'aai-num'>" . $rs_total[$report_head]['201601']['int'] . "</td>";
        $out .= "<td class = 'aai-num'>" . $rs_total[$report_head]['201501']['int'] . "</td>";
        $_change = _get_percent_difference($rs_total[$report_head]['201601']['int'], $rs_total[$report_head]['201501']['int']);
        $out .= "<td class = 'aai-num'>";
          $out .= $_change;
        $out .= "</td>";
        $out .= "<td class = 'aai-num'>";
          $out .= $rs_total[$report_head]['201512']['int'];
        $out .= "</td>";
        $out .= "<td class = 'aai-num'>";
          $out .= $rs_total[$report_head]['201412']['int'];
        $out .= "</td>";
        $_change = _get_percent_difference($rs_total[$report_head]['201512']['int'], $rs_total[$report_head]['201412']['int']);
        $out .= "<td class = 'aai-num'>" . $_change . "</td>";
      $out .= "</tr>";

      // Domestic section
      $out .= "<tr><td class = 'report-section-sub-head aai-bold' colspan = '7'>";
      $out .= t(ucwords('Domestic')) . "</td></tr>";
      foreach (array('international', 'jv', 'custom', 'domestic', 'other') as $airport_type) {
        $out .= "<tr>";
          $out .= "<td>" . t(ucwords("$airport_type airports")) . "</td>";
          $out .= "<td class = 'aai-num'>" . $ary['201601']['dom'][$airport_type] . "</td>";
          $out .= "<td class = 'aai-num'>" . $ary['201501']['dom'][$airport_type] . "</td>";
          $_change = _get_percent_difference($ary['201601']['dom'][$airport_type], $ary['201501']['dom'][$airport_type]);
          $out .= "<td class = 'aai-num'>" . $_change . "</td>";
          $out .= "<td class = 'aai-num'>" . $ary['201512']['dom'][$airport_type] . "</td>";
          $out .= "<td class = 'aai-num'>" . $ary['201412']['dom'][$airport_type] . "</td>";
          $_change = _get_percent_difference($ary['201512']['dom'][$airport_type], $ary['201412']['dom'][$airport_type]);
          $out .= "<td class = 'aai-num'>" . $_change . "</td>";
        $out .= "<tr>";
      }
      // add row to show category total for domestic airlines
      $out .= "<tr class = 'report-total'>";
        $out .= "<td class='aai-bold'>TOTAL</td>";
        $out .= "<td class = 'aai-num'>" . $rs_total[$report_head]['201601']['dom'] . "</td>";
        $out .= "<td class = 'aai-num'>" . $rs_total[$report_head]['201501']['dom'] . "</td>";
        $_change = _get_percent_difference($rs_total[$report_head]['201601']['dom'], $rs_total[$report_head]['201501']['dom']);
        $out .= "<td class = 'aai-num'>" . $_change . "</td>";
        $out .= "<td class = 'aai-num'>" . $rs_total[$report_head]['201512']['dom'] . "</td>";
        $out .= "<td class = 'aai-num'>" . $rs_total[$report_head]['201412']['dom'] . "</td>";
        $_change = _get_percent_difference($rs_total[$report_head]['201512']['dom'], $rs_total[$report_head]['201412']['dom']);
        $out .= "<td class = 'aai-num'>" . $_change . "</td>";
      $out .= "</tr>";
    }
  $out .= "</table>";
  $out .= l(t('Back to Reports'), 'investors/reports');

  // set variable for the template
  $variables['airport_traffic_report'] = serialize($out);
}

/**
 * Function to calculate % difference.
 *
 * @param Number
 *   $num1 first number (greater number).
 * @param Number
 *   $num2 second number.
 *
 * @return Number
 *   $response calculated value.
 */
function _get_percent_difference($num1, $num2) {
  $response = 0;
  if ($num1 && $num2) {
    $response = ( ($num1 - $num2) / $num2 ) * 100;
    return number_format($response, 2);
  } else {
    $response = '-';
  }

  return $response;
}

/**
 * Implementation of hook_views_query_alter
 * @param type $view
 * @param type $query 
 */
function aims_form_alter(&$form, &$form_state, $form_id) {
  if ($form_id == "user_login") {
    Global $base_url;
    $url = trim($base_url);
	  
    $form['name']['#title_display'] = 'invisible';
    $form['pass']['#title_display'] = 'invisible';
		// $form['#suffix'] = '<div style="float:left;width:100%;">Retired Employeee</div>';
	  $form['name']['#attributes']['placeholder'] = t( 'Username' );
        $form['pass']['#attributes']['placeholder'] = t( 'Password' );
		
		  
		$form['name']['#markup'] = '<span class="sb-icon-search"></span>';  
		 

	 $cancel_btn = "<div class='form-actions form-wrapper' id='edit-actions'>";
	 $cancel_btn .= "<input class='form-submit cancelb' type='button' value='Cancel' onClick=javascript:gotohome('$url'); />";
	 $cancel_btn .= "</div>";
	  
 $form['submit']['#suffix'] = $cancel_btn;
 
	 
	 
 
	 
  }
}

function custom_submit_for_this_button($form, &$form_state) {
$form_state['redirect'] = 'node';
}