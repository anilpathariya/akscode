<?php
/**
 * @file
 *   Create a filter page to select flight details
 */

/**
 * Implements function to return flight filter form
 */
function flights_detail() {
  // add JS files
  drupal_add_js(drupal_get_path('module', 'fids') . '/js/jquery.dataTables.min.js', array(
    'type' => 'file',
    'scope' => 'footer',
    'group' => JS_THEME,
    'every_page' => FALSE,
    'weight' => -1,
  ));
  drupal_add_js(drupal_get_path('module', 'fids') . '/js/fids_flight_detail.js', array(
    'type' => 'file',
    'scope' => 'footer',
    'group' => JS_THEME,
    'every_page' => FALSE,
    'weight' => -1,
  ));
  // add jquery ui tabs library
  drupal_add_library ( 'system' , 'ui.tabs' );
  //drupal_add_js('http://code.jquery.com/ui/1.12.0/jquery-ui.js');
  if(arg(1)== 'flights-schedule'){
    $functions = AAI::getInstance();
      return $functions->aaiGetFIDSScheduleTabs();
  }else{
    $functions = AAI::getInstance();
      return $functions->aaiGetFIDSTabs();
  }
}



/**
 * function to return searched flights detail, for FIDS search
 */
function fids_search_flight_detail() {
  $date = $airport_code = $flight_no = '';

  // get request parameters
  $home_airport_code = base64_decode($_REQUEST['home_airport_code']);
  $type = trim($_REQUEST['type']);

  // form condition for the query function
  $condition = array(
    'arrival_departure' => $type,
  );

  if (isset($_REQUEST['airport-code'])) {
    $airport_code = trim($_REQUEST['airport-code']);
    if ($airport_code != '') {
      if ($type == 'departure') {
        $condition['destination_airport_code'] = $airport_code;
      } else {
        $condition['local_airport_code'] = $airport_code;
      }
    }
  }

  if (isset($_REQUEST['fids-date'])) {
    $date = trim($_REQUEST['fids-date']);
    if ($date != '') {
      $condition['from_date'] = $date;
    }
  }

  if (isset($_REQUEST['flight-no'])) {
    $flight_no = trim($_REQUEST['flight-no']);
    if ($flight_no != '') {
      $condition['flight_number'] = $flight_no;
    }
  }

 //print_r($condition);
 //print "Anil";
  // call the function with passed arguments
  $functions = AAI::getInstance();

  $fids_flights = $functions->aaiGetFilghtDetail($home_airport_code, $condition);
  if (is_array($fids_flights)) {
    if (count($fids_flights)) {
      $out = $functions->aaiThemeFIDSOutput($fids_flights, $type, $home_airport_code);

      echo count($fids_flights) . t(' flight detail(s) found.') . '~' . $out;
    } else {
      echo t("No flight detail found.");
    }
  } else {
    echo "<div class='aai-noresult'>" . t('No record found. Please check spelling/flight code or try more generic search!') . "</div>";
  }
}

/**
 * function to return searched flights schedule detail, for FIDS search
 */
function fids_search_flight_schedule_detail() {
  $date = $airport_code = '';

  // get request parameters
  $home_airport_code = base64_decode($_REQUEST['home_airport_code']);
  $type = trim($_REQUEST['type']);
  // form condition for the query function
  $condition = array(
    'arrival_departure' => $type,
  );

  if (isset($_REQUEST['airport-code'])) {
    $airport_code = trim($_REQUEST['airport-code']);
    if ($airport_code != '') {
      if ($type == 'departure') {
        $condition['destination_airport_code'] = $airport_code;
      } else {
        $condition['local_airport_code'] = $airport_code;
      }
    }
  }

  if (isset($_REQUEST['fids-date'])) {
    $date = trim($_REQUEST['fids-date']);
    if ($date != '') {
      $condition['from_date'] = $date;
    }
  }

  

 
  // call the function with passed arguments
  $functions = AAI::getInstance();

  $fids_flights = $functions->aaiGetFilghtScheduleDetail($home_airport_code, $condition);
 // out($fids_flights)
  if (is_array($fids_flights)) {
    if (count($fids_flights)) {
      $out = $functions->aaiThemeFIDSScheduleOutput($fids_flights, $type, $home_airport_code);
      // die('virendra');
   
      echo count($fids_flights) . t(' flight schedule(s) found.') . '~' . $out;
    } else {
      echo t("No flight schedule found.");
    }
  } else {
    echo "<div class='aai-noresult'>" . t('No record found. Please check spelling or try more generic search!') . "</div>";
  }
}

/**
 * Function to get list of airlines opertaing from an airport.
 */
function fids_airlines_detail() {
  $functions = AAI::getInstance();
  $airport_code = base64_decode($_REQUEST['home_airport_code']);
  $fids_flights = $functions->aaiGetAirlinesServedList($airport_code);
  $flights_str = implode(",", $fids_flights);
  print views_embed_view('airports_airlines', 'block', $flights_str);
}
