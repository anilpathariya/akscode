<?php
/**
 * This file contains all the commonly used utility functions.
 * @author
 *  Anil Kumar Pathariya
 */

// include database file
require_once("database.php");

class AAI {

  private static $instance;

  // variable to set system environment i.e local, dev, stag, prod.
  private static $environment;

  // variable to hold system key used for handshaking in case of critical functions.
  private static $system_key = 'Ab@rTGQ#Bs28dg$6r^*!5H#Dsdd*@gRSkiz';

  private function __construct() {}

  public function __clone()
  {
    trigger_error('Clone is not allowed.', E_USER_ERROR);
  }

  public static function getInstance() {
    if(!isset(self::$instance)) {
      $c = __CLASS__;
      self::$instance = new $c;
    }
    return self::$instance;
  }

  /**
   * Implements watchdog entry function
   *
   * @param
   *  String $msg, message for watchdog entry
   *  Constant $severity, defines the entry severity
   */
  public function aaiWatchdog($msg, $severity = WATCHDOG_ERROR) {
    watchdog('airports', $msg, array(), $severity, NULL);
  }

  /**
  *  Implements function to get term ID by name
  *
  * @param
  *   String $term_name name of the term to search for
  *   String $vocab_name (optional) vocabulary machine name to look into
  *
  * @return
  *   Array of matched term ID's
  */
  public function aaiGetTermIdFromName($term_name = NULL, $vocab_name = NULL) {
    if(is_null($term_name)) {
      return 0;
    }
    return array_keys(taxonomy_get_term_by_name($term_name, $vocab_name));
  }

  /**
   * Implements function to Drop table from DB.
   */
  public function aaiDropTable($tbl) {
    $database = DB::dbInstance();
    return $database->dbDropTable($tbl);
  }

  /**
   * Implements function to fetch vocabulary detail.
   *
   * @param
   *   String $vocab_name Name of the vocabulary
   * @param
   *   Array $detail array of details to be fetched.
   *   ex. array(vid, name, machine_name, description, language, i18n_mode, weight)
   *
   * @return
   *   Array associative array of values, returns full array for the vocabulary if $detail
   *   is blank array.
   */
  public function aaiGetVocabDetailByName($vocab_name, $detail = array()) {
    if ($vocab_name == '') {
      return array();
    }

    $output = array();
    $vocabs = taxonomy_get_vocabularies();

    foreach ($vocabs as $val) {
      if ($val->name == $vocab_name) {
        $output = $val;
        unset($val);
        break;
      }
    }

    if (!count($detail)) {
      return $output;
    }

    $tmp = array();
    foreach ($detail as $val) {
      $tmp[$val] = $output->$val;
    }

    return $tmp;
  }

  /**
   * Implements function to get current language code.
   *
   * @return
   *   String $code, current language code
   */
  public function aaiCurrentLang() {
    global $language;
    $code = $language->language;

    /*if ($code == 'en') {
      $code = LANGUAGE_NONE;
    }*/

    return $code;
  }

  /**
   * Function to check if a node exists.
   *
   * @param
   *   String table name
   * @param,
   *   Array associative array of conditions
   *
   * @return
   *   Number count of the number of records found
   */
  public function aaiCheckIfNodeExists($tbl , $condition_flds = array()) {
    // get database class instance
    $database = DB::dbInstance();

    return $database->dbConditionalRecordCount($tbl, $condition_flds);
  }

  
   /**
    * Function to check if department node already exists.
    *
    * @param
    *   Number department tid
    * @param
    *   Number headquarter tid
    * @param
    *   Number region tid
    *
    * @return
    *   Number count of number of records found
    */
   public function aaiCheckIfDeptExists($nodeLang, $dept, $hq_tid, $region = 0) {
    $tbl = 'node';
    $joins[] = array(
      'join' => 'innerjoin',
      'tbl' => 'taxonomy_index',
      'alias' => 'ti',
      'on' => "tbl.nid = ti.nid",
      'condition' => array('ti.tid' => $dept)
    );

    $joins[] = array(
      'join' => 'leftjoin',
      'tbl' => 'taxonomy_index',
      'alias' => 'ti2',
      'on' => "tbl.nid = ti2.nid AND ti2.tid != $dept",
      'condition' => array('ti2.tid' => $hq_tid)
    );
   
    if ($region) {
      $joins[] = array(
        'join' => 'leftjoin',
        'tbl' => 'taxonomy_index',
        'alias' => 'ti3',
        'on' => "tbl.nid = ti3.nid AND ti3.tid != $hq_tid",
        'condition' => array('ti3.tid' => $region)
      );
    }

    $conditions = array(
      'type' => 'aai_department',
      'status' => 1,
      'language' => $nodeLang,
    );

    $flds_to_select = array('title', 'nid');
    // get database class instance
    $database = DB::dbInstance();

    $rs = $database->dbSelectWithJoin($tbl, $joins, $conditions, $flds_to_select);
    return $rs->rowCount();
  }

  /**
   * Function to check if airport detail exists or not.
   */
  public function aaiAirportExists($airport_name, $bundle = 'airports') {
    $lang = $this->aaiCurrentLang();

    // get database class instance
    $database = DB::dbInstance();

    // get airport Term ID
    $vocab_det = array('vid', 'machine_name');
    $airport_vocab_det_ary = $this->aaiGetVocabDetailByName('Airport', $vocab_det);
    $airport_vocab_mac_name = $airport_vocab_det_ary['machine_name'];
    $airport_tid_ary = $this->aaiGetTermIdFromName($airport_name, $airport_vocab_mac_name);
    $airport_tid = $airport_tid_ary[0];

    // check if node/data exists for current airport.
    $tbl = 'field_data_field_related_airport';
    $conditions = array('entity_type' => 'node',
      'bundle' => $bundle,
      'deleted' => 0,
      'field_related_airport_tid' => $airport_tid,
      'language' => $lang,
    );
    $fld_name = array('entity_id', 'language');
    $details_exist = $database->dbConditionalSelect($tbl, $conditions, $fld_name);

    $res = array();
    if (count($details_exist)) {
      $res['entity_id'] = $details_exist[0]['entity_id'];
      $res['language'] = $details_exist[0]['language'];
    } else if ($lang != 'en') {
      $conditions['language'] = 'en';
      $details_exist = $database->dbConditionalSelect($tbl, $conditions, $fld_name);
      if (count($details_exist)) {
        $res['entity_id'] = $details_exist[0]['entity_id'];
        $res['language'] = $details_exist[0]['language'];
      }
    }

    return $res;
  }

  /**
   * Function to return term id.
   *
   * @param
   *   String term name
   * @param
   *   String Vocabulary Name to look into (Not Machine name)
   *
   * @return
   *   Number term ID
   */
  public function aaiGetTermID($term_name, $vocab_name) {
    $vocab_det = array('vid', 'machine_name');
    $vocab_det_ary = $this->aaiGetVocabDetailByName($vocab_name, $vocab_det);
    $vocab_mac_name = $vocab_det_ary['machine_name'];
    $tid_ary = $this->aaiGetTermIdFromName($term_name, $vocab_mac_name);
    return $tid_ary[0];
  }

  /**
   * Function to return tid's for which data is available
   */
  public function aaiGetTermsForWhichDataIsAvailable($entity_id, $fc_fld, $term_fld, $lang = '') {
    if ($lang == '') {
      $lang = $this->aaiCurrentLang();
    }

    // get database class instance
    $database = DB::dbInstance();

    $fc_fld_tbl = 'field_data_' . $fc_fld;
    $fc_fld_vlaue = $fc_fld . '_value';

    $term_tbl = 'field_data_' . $term_fld;
    $term_fld_val = $term_fld . '_tid';

    $joins[] = array(
      'join' => 'leftjoin',
      'tbl' => $fc_fld_tbl,
      'alias' => 'fc_tbl',
      'on' => "fc_tbl.$fc_fld_vlaue = tbl.entity_id",
      'condition' => array(
        'fc_tbl.entity_id' => $entity_id,
        'fc_tbl.language' => $lang,
        'fc_tbl.deleted' => 0,
      ),
    );

    $conditions = array(
      'language' => $lang,
      'deleted' => 0,
    );
    $flds_to_select = array($term_fld_val);

    $details = $database->dbSelectWithJoin($term_tbl, $joins, $conditions, $flds_to_select);

    $tids = array();
    if($details->rowCount()) {
      while ($obj = $details->fetchObject()) {
        $tids[] = $obj->{$term_fld_val};
      }
    }
    return $tids;
  }
  /**
   * Function to get airports submenu.
   */
  public function getFirstlinkId($content_type = 'city-info') {
    Global $base_url;
    $lang = $this->aaiCurrentLang();
    $details_exist = 0;

    if (arg(2)) {
      $airport_name = arg(2);
    } else {
      $airport_name = arg(1);
    }
    $airport_tid = 0;

    // bundle to check for airport detail
    $airport_bundle = array(
      'city-info' => 'airport_city_information',
      'passenger-info' => 'airport_passenger_information_fa',
    );

    // check if airport details exist/added
    $bundle = $airport_bundle[$content_type];
    $details_exist = $this->aaiAirportExists($airport_name, $bundle);

    if (count($details_exist)) {
      // get airport tid
      $airport_tid = $this->aaiGetTermID(ucwords($airport_name), 'Airport');

      $details_exist = $details_exist['entity_id'];

      //get vocab name and first item in term list
      switch (strtolower($content_type)) {
        case 'city-info' :
          $vocab_name = 'Airport City Information';
          $vid_ary = $this->aaiGetVocabDetailByName($vocab_name, array('vid'));
          $vid = $vid_ary['vid'];
          // details to check if what all terms data is available
          $fc_fld = 'field_city_info';
          $term_fld = 'field_city_info_type';
          break;
        case 'passenger-info' :
          $vocab_name = 'Airport Passenger Info';
          $vid_ary = $this->aaiGetVocabDetailByName($vocab_name, array('vid'));
          $vid = $vid_ary['vid'];
          // details to check if what all terms data is available
          $fc_fld = 'field_passenger_info';
          $term_fld = 'field_passenger_info_type';
          break;
      }

      // get all terms for which data is available
      $frst_term_name = $frst_term_tid = '';
      $all_terms_ary = taxonomy_get_tree($vid);
      $allowed_terms_tid = array();

      $data_for_terms = $this->aaiGetTermsForWhichDataIsAvailable($details_exist, $fc_fld, $term_fld, $lang);

      // check if any data is added, if yes then get terms to be shown on
      // left sub menu
      if (count($data_for_terms)) {
        foreach ($data_for_terms as $val) {
          foreach ($all_terms_ary as $index => $v) {
            if ($v->tid == $val) {
              $parent_exists = $v->parents[0];
              if ($parent_exists) {
                $allowed_terms_tid[$index] = $parent_exists;
              } else {
                $allowed_terms_tid[$index] = $val;
              }
              break;
            }
          }
        }
        // sort the array on key
        ksort($allowed_terms_tid);
        $tmp_ary = array_values($allowed_terms_tid);
        $frst_term_tid = $tmp_ary[0];
      }

      $tab_tid = $frst_term_tid;
      $tab_vocab = strtolower(str_replace(' ', '-', $vocab_name));

      $sub_menu = "";
      $taxonomy_tree = taxonomy_get_tree($vid);

      if (count($taxonomy_tree)) {
        $sub_menu .= "<ul class='dropdown-menu mega-dropdown-menu row' style='display: none;'>";
        $sub_menu .= "<li class='col-sm-5'><ul>";
        $first_item = 1;
        $fixtermid = "";
        foreach ($taxonomy_tree as $indx => $value) {
          $parent_exists = $value->parents[0];
          if (!$parent_exists) {
            // check if term exists in list of terms for which data is
            // available in DB.
            $term_id = $value->tid;
            if (array_search($term_id, $allowed_terms_tid) !== FALSE) {
              $name = t($value->name);
                if($fixtermid == ""){
                  $fixtermid = $term_id;
               }            
            }
          }
        } 
      }
      return $fixtermid;
    }
  }

   /**
   * Function to get airports submenu.
   */
  public function aaiGetMobileSubMenu($content_type = 'city-info') {

    Global $base_url;
    $lang = $this->aaiCurrentLang();
    $details_exist = 0;

    if (arg(2)) {
      $airport_name = arg(2);
    } else {
      $airport_name = arg(1);
    } 
    $airport_tid = 0;

    // bundle to check for airport detail
    $airport_bundle = array(
      'city-info' => 'airport_city_information',
      'passenger-info' => 'airport_passenger_information_fa',
    );

    // check if airport details exist/added
    $bundle = $airport_bundle[$content_type];

 
    $details_exist = $this->aaiAirportExists($airport_name, $bundle);

    if (count($details_exist)) {
      // get airport tid
      $airport_tid = $this->aaiGetTermID(ucwords($airport_name), 'Airport');

      $details_exist = $details_exist['entity_id'];

      //get vocab name and first item in term list
      switch (strtolower($content_type)) {
        case 'city-info' :
          $vocab_name = 'Airport City Information';
          $vid_ary = $this->aaiGetVocabDetailByName($vocab_name, array('vid'));
          $vid = $vid_ary['vid'];
          // details to check if what all terms data is available
          $fc_fld = 'field_city_info';
          $term_fld = 'field_city_info_type';
          break;
        case 'passenger-info' :
          $vocab_name = 'Airport Passenger Info';
          $vid_ary = $this->aaiGetVocabDetailByName($vocab_name, array('vid'));
          $vid = $vid_ary['vid'];
          // details to check if what all terms data is available
          $fc_fld = 'field_passenger_info';
          $term_fld = 'field_passenger_info_type';
          break;
      }

      // get all terms for which data is available
      $frst_term_name = $frst_term_tid = '';
      $all_terms_ary = taxonomy_get_tree($vid);
      $allowed_terms_tid = array();

      $data_for_terms = $this->aaiGetTermsForWhichDataIsAvailable($details_exist, $fc_fld, $term_fld, $lang);

      // check if any data is added, if yes then get terms to be shown on
      // left sub menu
      if (count($data_for_terms)) {
        foreach ($data_for_terms as $val) {
          foreach ($all_terms_ary as $index => $v) {
            if ($v->tid == $val) {
              $parent_exists = $v->parents[0];
              if ($parent_exists) {
                $allowed_terms_tid[$index] = $parent_exists;
              } else {
                $allowed_terms_tid[$index] = $val;
              }
              break;
            }
          }
        }
        // sort the array on key
        ksort($allowed_terms_tid);
        $tmp_ary = array_values($allowed_terms_tid);
        $frst_term_tid = $tmp_ary[0];
      }

      $tab_tid = $frst_term_tid;
      $tab_vocab = strtolower(str_replace(' ', '-', $vocab_name));

      $sub_menu = "";
      $taxonomy_tree = taxonomy_get_tree($vid);

      if (count($taxonomy_tree)) {
  
        $sub_menu .= "<ul>";
        $first_item = 1;

        foreach ($taxonomy_tree as $indx => $value) {
          $parent_exists = $value->parents[0];
          if (!$parent_exists) {
            // check if term exists in list of terms for which data is
            // available in DB.
            $term_id = $value->tid;
            if (array_search($term_id, $allowed_terms_tid) !== FALSE) {
              $name = t($value->name);
              if ($first_item) {
                $sub_menu .= "<li class = 'active'>";
                $first_item = 0;
              } else {
                $sub_menu .= "<li class = >";
              }
              $term_name_4_url = str_replace(' ', '-', $value->name);

              $sub_menu .=  "<a href='/$lang/airports/$content_type/$airport_name/$term_name_4_url'>".t($name)."</a>";
              $sub_menu .= "</li>";
            }
          }
        }

        $sub_menu .= "</ul></li>";

  
        $countslide = 1; 
     
      }
      return $sub_menu;
    }
  }
  

  /**
   * Function to get airports submenu.
   */
  public function aaiGetSubMenu($content_type = 'city-info') {
    Global $base_url;
    $lang = $this->aaiCurrentLang();
    $details_exist = 0;

    if (arg(2)) {
      $airport_name = arg(2);
    } else {
      $airport_name = arg(1);
    }
    $airport_tid = 0;

    // bundle to check for airport detail
    $airport_bundle = array(
      'city-info' => 'airport_city_information',
      'passenger-info' => 'airport_passenger_information_fa',
    );

    // check if airport details exist/added
    $bundle = $airport_bundle[$content_type];
    $details_exist = $this->aaiAirportExists($airport_name, $bundle);

    if (count($details_exist)) {
      // get airport tid
      $airport_tid = $this->aaiGetTermID(ucwords($airport_name), 'Airport');

      $details_exist = $details_exist['entity_id'];

      //get vocab name and first item in term list
      switch (strtolower($content_type)) {
        case 'city-info' :
          $vocab_name = 'Airport City Information';
          $vid_ary = $this->aaiGetVocabDetailByName($vocab_name, array('vid'));
          $vid = $vid_ary['vid'];
          // details to check if what all terms data is available
          $fc_fld = 'field_city_info';
          $term_fld = 'field_city_info_type';
          break;
        case 'passenger-info' :
          $vocab_name = 'Airport Passenger Info';
          $vid_ary = $this->aaiGetVocabDetailByName($vocab_name, array('vid'));
          $vid = $vid_ary['vid'];
          // details to check if what all terms data is available
          $fc_fld = 'field_passenger_info';
          $term_fld = 'field_passenger_info_type';
          break;
      }

      // get all terms for which data is available
      $frst_term_name = $frst_term_tid = '';
      $all_terms_ary = taxonomy_get_tree($vid);
      $allowed_terms_tid = array();

      $data_for_terms = $this->aaiGetTermsForWhichDataIsAvailable($details_exist, $fc_fld, $term_fld, $lang);

      // check if any data is added, if yes then get terms to be shown on
      // left sub menu
      if (count($data_for_terms)) {
        foreach ($data_for_terms as $val) {
          foreach ($all_terms_ary as $index => $v) {
            if ($v->tid == $val) {
              $parent_exists = $v->parents[0];
              if ($parent_exists) {
                $allowed_terms_tid[$index] = $parent_exists;
              } else {
                $allowed_terms_tid[$index] = $val;
              }
              break;
            }
          }
        }
        // sort the array on key
        ksort($allowed_terms_tid);
        $tmp_ary = array_values($allowed_terms_tid);
        $frst_term_tid = $tmp_ary[0];
      }

      $tab_tid = $frst_term_tid;
      $tab_vocab = strtolower(str_replace(' ', '-', $vocab_name));

      $sub_menu = "";
      $taxonomy_tree = taxonomy_get_tree($vid);

      if (count($taxonomy_tree)) {
        $sub_menu .= "<ul class='dropdown-menu mega-dropdown-menu row' style='display: none;'>";
        $sub_menu .= "<li class='col-sm-5'><ul>";
        $first_item = 1;

        foreach ($taxonomy_tree as $indx => $value) {
          $parent_exists = $value->parents[0];
          if (!$parent_exists) {
            // check if term exists in list of terms for which data is
            // available in DB.
            $term_id = $value->tid;
            if (array_search($term_id, $allowed_terms_tid) !== FALSE) {
              $name = t($value->name);
              if ($first_item) {
                $sub_menu .= "<li class = 'active'>";
                $first_item = 0;
              } else {
                $sub_menu .= "<li class = >";
              }
              $term_name_4_url = str_replace(' ', '-', $value->name);

              $sub_menu .=  "<a href='/$lang/airports/$content_type/$airport_name/$term_name_4_url' data-target='#tab-$term_id' data-hover='tab'>".t($name)."</a>";
              $sub_menu .= "</li>";
            }
          }
        }

        $sub_menu .= "</ul></li>";
        $sub_menu .= "<li class='col-sm-7 hidden-xs'>
                       <div class='tab-content'>";
  
        $countslide = 1; 
        foreach ($taxonomy_tree as $indx => $value) {
          $term_id = $value->tid;
          $term = taxonomy_term_load($term_id);

          $img = '';
          if (isset($term->field_term_default_image[$lang])) {
            $img = $term->field_term_default_image[$lang][0]['uri'];
            $img = file_create_url($img);
          } else {
            $img = $base_url."/sites/default/files/aai-board-image.jpg";
          }

          $parent_exists = $value->parents[0];
          if (!$parent_exists) {
            // check if term exists in list of terms for which data is
            // available in DB.
            $term_id = $value->tid;
            if (array_search($term_id, $allowed_terms_tid) !== FALSE) {
              $name = t($value->name);
              if($countslide == 1) {
                $currentdiv = "active";
              } else {
                $currentdiv = "";
              }
              $sub_menu .= "<div class='tab-pane $currentdiv' id='tab-$term_id'><img class='img-responsive img-style' src='$img' alt='$value->name'></div>";
            }
          }
          $countslide++;
        } 
        $sub_menu .= "</ul>";
      }
      return $sub_menu;
    }
  }
  
  /**
   * Implements function to get airport details. 
   *
   * @param
   *   Number, indicates if called from menu click or its an ajax 
   *   call(0), in which case only inner detais are required
   */
  public function aaiAirportDetails($from_menu = 1, $request = array()) {
    $lang = $this->aaiCurrentLang();
    $details_exist = 0;

    // get arguments and structure output accordingly
    if ($from_menu) {
      $content_type = arg(1);
      $airport_name = arg(2);
      $airport_tid = 0;

      // bundle to check for airport detail
      $airport_bundle = array(
        'transport' => 'airport_transportation',
        'city-info' => 'airport_city_information',
        'passenger-info' => 'airport_passenger_information_fa',
      );

      // check if airport details exist/added
      $bundle = $airport_bundle[$content_type];
      $details_exist = $this->aaiAirportExists($airport_name, $bundle);

      if (count($details_exist)) {
        if ($details_exist['language'] != $lang) {
          $lang = $details_exist['language'];
          $_SESSION['airport_lang'] = $lang;
        } else {
          unset($_SESSION['airport_lang']);
        }

        $details_exist = $details_exist['entity_id'];

        // get airport tid
        $airport_tid = $this->aaiGetTermID(ucwords($airport_name), 'Airport');

        //get vocab name and first item in term list
        switch (strtolower($content_type)) {
          case 'transport' :
            $vocab_name = 'Airport Transports';
            $vid_ary = $this->aaiGetVocabDetailByName($vocab_name, array('vid'));
            $vid = $vid_ary['vid'];
            // details to check if what all terms data is available
            $fc_fld = 'field_transportation';
            $term_fld = 'field_transportation_type';
            break;
          case 'city-info' :
            $vocab_name = 'Airport City Information';
            $vid_ary = $this->aaiGetVocabDetailByName($vocab_name, array('vid'));
            $vid = $vid_ary['vid'];
            // details to check if what all terms data is available
            $fc_fld = 'field_city_info';
            $term_fld = 'field_city_info_type';
            break;
          case 'passenger-info' :
            $vocab_name = 'Airport Passenger Info';
            $vid_ary = $this->aaiGetVocabDetailByName($vocab_name, array('vid'));
            $vid = $vid_ary['vid'];
            // details to check if what all terms data is available
            $fc_fld = 'field_passenger_info';
            $term_fld = 'field_passenger_info_type';
            break;
        }

        // get all terms for which data is available
        $frst_term_name = $frst_term_tid = '';
        $all_terms_ary = taxonomy_get_tree($vid);
        $allowed_terms_tid = array();

        $data_for_terms = $this->aaiGetTermsForWhichDataIsAvailable($details_exist, $fc_fld, $term_fld, $lang);

        // check if any data is added, if yes then get terms to be shown on
        // left sub menu
        if (count($data_for_terms)) {
          foreach ($data_for_terms as $val) {
            foreach ($all_terms_ary as $index => $v) {
              if ($v->tid == $val) {
                $parent_exists = $v->parents[0];
                if ($parent_exists) {
                  $allowed_terms_tid[$index] = $parent_exists;
                } else {
                  $allowed_terms_tid[$index] = $val;
                }
                break;
              }
            }
          }
          // sort the array on key
          ksort($allowed_terms_tid);
          $tmp_ary = array_values($allowed_terms_tid);
          $frst_term_tid = $tmp_ary[0];
        }

        $tab_tid = $frst_term_tid;
        $tab_vocab = strtolower(str_replace(' ', '-', $vocab_name));
      }
    } else {  // if its ajax call
      $content_type = $request['type'];
      $airport_name = $request['airport'];
      $airport_tid = $this->aaiGetTermID(ucwords($airport_name), 'Airport');
      $tab_term_name = $request['tt'];
      $tab_vocab = $request['vocab'];
      $vocab_name = ucwords(str_replace('-', ' ', $tab_vocab));
      // get vid
      $vid_ary = $this->aaiGetVocabDetailByName($vocab_name, array('vid'));
      $vid = $vid_ary['vid'];

      // get the tab term id
      $tab_vocab_mac_name = strtolower(str_replace('-', '_', $tab_vocab));
      $tab_vocab_ary = $this->aaiGetTermIdFromName(ucwords($tab_term_name), $tab_vocab_mac_name);
      $tab_tid = $tab_vocab_ary[0];

      // in case of internal redirection, its clear that airport details will be
      // available
      $details_exist = 1;
      // set lang variable, according to the language for which
      // content is available
      if (isset($_SESSION['airport_lang'])) {
        $lang = $_SESSION['airport_lang'];
      }
    }

    // If no detail found for the airport, redirect user to page-not-found
    if (!$details_exist) {
      $tmp = ucwords($airport_name);
      drupal_set_message( t('Data not available for @airport airport.', array('@airport' => $tmp)) );
      unset($tmp);
      drupal_goto('page-not-found');
      exit;
    }

    // get airport section details
    //$airport_details = $this->aaiGetAirportTabDetail($content_type, $airport_tid, $tab_tid, $lang);

    // THEME THE OUTPUT
    $inner_detail = "";
    if (count($airport_details)) {
      foreach ($airport_details as $ary) {
        $inner_detail .= "<div class = 'aai-airport-detail-row col-md-12'>";
        // array variable to get field values, so it can be rearranged
        $data_row = array();
        $data_row[] = '';

        foreach ($ary as $col_fld => $v) {
          if (strpos($col_fld, '_type')) {
            /*$type = taxonomy_term_load($v);
            $type = t($type->name);
            $data_row['type'] = "<div class = 'aai-facility-type col-md-9'>" . $type . "</div>";*/
          } else if (strpos($col_fld, '_title')) {
            $data_row['title'] = "<div class = 'aai-facility-type title'>" . t($v) . "</div>";
          } else if (strpos($col_fld, '_image')) {
            $img_uri = $v;
            $config = array(
              "style_name" => "medium",
              "path" => $img_uri,
              "height" => NULL,
              "width" => NULL,
            );
            $img = theme_image_style($config);
            $data_row['img'] = "<div class = 'aai-facility-img col-md-3'>" . $img . "</div>";
          } else {
            $data_row[] = "<div class = 'aai-facility-desc col-md-9'>" . t($v) . "</div>";
          }
        }

        // set the records in order/chronology for display
        if (isset($data_row['title'])) {
          $inner_detail .= $data_row['title'];
        }
        if (isset($data_row['img'])) {
          $inner_detail .= $data_row['img'];
        }
        foreach ($data_row as $ky => $vl) {
          if ($ky != 'title' && $ky != 'img' && $ky !== 0) {
            $inner_detail .= $vl;
          }
        }
        unset($data_row);

        $inner_detail .= "</div>";
      }
    }

    if (!$from_menu) {
      return $inner_detail;
    }

    // build left sub-menu
    $sub_menu = "";
    $taxonomy_tree = taxonomy_get_tree($vid);
    if (count($taxonomy_tree)) {
      $sub_menu .= "<div class = 'aai-submenu-wrapper'>";
      $sub_menu .= "<ul>";
      $first_item = 1;

      foreach ($taxonomy_tree as $indx => $value) {
        $parent_exists = $value->parents[0];
        if (!$parent_exists) {
          // check if term exists in list of terms for which data is
          // available in DB.
          $term_id = $value->tid;
          if (array_search($term_id, $allowed_terms_tid) !== FALSE) {
            $name = t($value->name);
            if ($first_item) {
              $sub_menu .= "<li class = 'active'>";
              $first_item = 0;
            } else {
              $sub_menu .= "<li class = >";
            }

            $sub_menu .= "<a onclick = \"javascript:aai_get_tab_detail(this, '$airport_name', '$content_type', '$tab_vocab', '$name');\">$name</a>";
            $sub_menu .= "</li>";
          }
        }
      }
      $sub_menu .= "</ul></div>";
    }

    // club the entities to formulate output
    $output = "<div class = 'aai-section-detail-wrapper'>";
    $output .= "<div class = 'aai-submenu-main-container col-md-3'>";
    $output .= $sub_menu;
    $output .= "</div>";
    $output .= "<div class = 'aai-section-details col-md-9'>";
    $output .= $inner_detail;
    $output .= "</div>";
    $output .= "</div>";

    return $output;
  }

  /**
   * Function to return data for the airport page tabs.
   *
   * @param
   *   String content type name
   * @param
   *   Number airport term ID
   * @param
   *   Number page tab term ID
   *
   * @return
   *   Array associative array of all field values
   */
  private function aaiGetAirportTabDetail($content_type, $airport_tid, $tab_tid, $lang = '') {
    if ($lang == '') {
      $lang = $this->aaiCurrentLang();
    }

    // get database class instance
    $database = DB::dbInstance();

    // variable for content type name and respective field colection name
    $bundle = $col_name = $vocab_name = '';

    switch (strtolower($content_type)) {
      case 'transport' :
        $bundle = 'airport_transportation';
        $col_name = 'field_transportation';
        $vocab_name = 'Airport Transports';
        break;
      case 'city-info' :
        $bundle = 'airport_city_information';
        $col_name = 'field_city_info';
        $vocab_name = 'Airport City Information';
        break;
      case 'passenger-info' :
        $bundle = 'airport_passenger_information_fa';
        $col_name = 'field_passenger_info';
        $vocab_name = 'Airport Passenger Info';
        break;
    }

    // get all tid's that are to be selected/allowed for data display
    $allowed_tids = array();

    $vid_ary = $this->aaiGetVocabDetailByName($vocab_name, array('vid'));
    $vid = $vid_ary['vid'];
    $taxonomy_tree = taxonomy_get_tree($vid, $tab_tid);

    if (count($taxonomy_tree)) {
      foreach ($taxonomy_tree as $key => $value) {
        $allowed_tids[$value->tid] = $value->tid;
      }
    } else { 
      // in case sub term passed, fetch parent tid and allow all childs
      // of the parent term
      $tmp = taxonomy_get_parents($tab_tid);
      if (count($tmp)) {
        foreach ($tmp as $val) {
          $tmp_tid = $val->tid;
        }
        $taxonomy_tree = taxonomy_get_tree($vid, $tmp_tid);
        foreach ($taxonomy_tree as $key => $value) {
          $allowed_tids[$value->tid] = $value->tid;
        }
      } else {
        $allowed_tids[$tab_tid] = $tab_tid;
      }
    }

    // get airport specific node of the respective bundle
    $tbl = 'field_data_field_related_airport';
    $condition_flds = array('entity_type' => 'node',
      'bundle' => $bundle,
      'deleted' => 0,
      'field_related_airport_tid' => $airport_tid,
      //'language' => $lang,
    );
    $flds_to_select = array('entity_id');
    $rs = $database->dbConditionalSelect($tbl, $condition_flds, $flds_to_select);

    // if no record found return 0;
    if (!count($rs)) {
      return array();
    }

    $tab_node_id = $rs[0]['entity_id'];
    $tab_node = node_load($tab_node_id);
    $tab_node_meta = entity_metadata_wrapper('node', $tab_node);

    /*// get fields of the respective field collection
    $col_fields = $this->aaiGetFCFields($tab_node, $col_name);
    $col_field_values = array();

    $col_item_count = count($tab_node_meta->$col_name);
    for ($i = 0; $i < $col_item_count; $i++) {
      // variable to check if current data to display meet tid requirement
      // if it matches the allowed tid, then only we have to consider it for
      // display
      $tid_matches = 1;

      $col_obj = $tab_node_meta->{$col_name}[$i]->value();
      $col_tmp = array();

      foreach ($col_fields as $col_fld) {
        if (count($col_obj->{$col_fld})) {
          if (strpos($col_fld, '_type')) {
            $item_tid = $col_obj->{$col_fld}[$lang][0]['tid'];
            if (array_key_exists($item_tid, $allowed_tids)) {
              $col_tmp[$col_fld] = $item_tid;
            } else {
              $tid_matches = 0;
              break;
            }
          } else if (strpos($col_fld, '_image')) {
            $col_tmp[$col_fld] = $col_obj->{$col_fld}[$lang][0]['uri'];
          } else {
            $col_tmp[$col_fld] = $col_obj->{$col_fld}[$lang][0]['value'];
          }
        }
      }

      if ($tid_matches) {
        $col_field_values[] = $col_tmp;
      }
      unset($col_tmp);
    }*/
    
    // updated code to fetch data from revision tables for field collection
    // items, as they have known issue with respect to multilinguistic setup
    $tab_node_data = get_object_vars($tab_node_meta->value());
    // get fields of the respective field collection
    $col_fields = $this->aaiGetFCFields($tab_node, $col_name);
    $col_field_values = array();
    $col_item_count = count($tab_node_data[$col_name][$lang]);
    if (count($col_item_count)) {
      foreach ($tab_node_data[$col_name][$lang] as $value) {
        // variable to check if current data to display meet tid requirement
        // if it matches the allowed tid, then only we have to consider it for
        // display
        $tid_matches = 1;
        $col_tmp = array();

        // FC entity id & revision id
        $fc_entity_id = $value['value'];
        $fc_revision_id = $value['revision_id'];

        $condition_flds = array(
          'entity_id' => $fc_entity_id,
          'revision_id' => $fc_revision_id,
          'bundle' => $col_name,
          'language' => $lang,
          'deleted' => 0,
        );

        foreach ($col_fields as $col_fld) {
          $col_tbl = 'field_revision_' . $col_fld;

          if (strpos($col_fld, '_type')) {
            $id_fld = $col_fld . '_tid';
            $item_tid_ary = $database->dbGetFieldValue($col_tbl, $condition_flds, $id_fld);
            if (count($item_tid_ary)) {
              $item_tid = $item_tid_ary[0];
              if (array_key_exists($item_tid, $allowed_tids)) {
                $col_tmp[$col_fld] = $item_tid;
              } else {
                $tid_matches = 0;
                break;
              }
            } else {
              break;
            }
          } else if (strpos($col_fld, '_image')) {
            $id_fld = $col_fld . '_fid';
            $item_fid_ary = $database->dbGetFieldValue($col_tbl, $condition_flds, $id_fld);
            if (count($item_fid_ary)) {
              $item_fid = $item_fid_ary[0];
              $file_obj = file_load($item_fid);
              $col_tmp[$col_fld] = $file_obj->uri;
            }
          } else {
            $id_fld = $col_fld . '_value';
            $item_val_ary = $database->dbGetFieldValue($col_tbl, $condition_flds, $id_fld);
            if (count($item_val_ary)) {
              $item_val = $item_val_ary[0];
              $col_tmp[$col_fld] = $item_val;
            }
          }
        }

        if ($tid_matches) {
          $col_field_values[] = $col_tmp;
        }
        unset($col_tmp);
      }
    }
    return $col_field_values;
  }

  /**
   * Function to fetch all filed names of a field collection.
   *
   * @param
   *   Object node object
   * @param
   *   String field collection name
   * @param
   *   String entity type
   *
   * @return
   *   Array associative array of field names
   */
  public function aaiGetFCFields($node, $collection_name, $entity_type = 'node') {
    // variable to keep field names
    $flds = array();

    $items = field_get_items($entity_type, $node, $collection_name);
    
    foreach ($items as $fields) {
      $tmp = entity_load('field_collection_item', array($fields['value']));
      $tmp = current($tmp);
      foreach ($tmp as $k => $v) {
        if (strpos($k, 'field_') !== FALSE && $k != 'field_name') {
          $flds[] = $k;
        }
      }
      break;
    }

    return $flds;
  }

  /**
   * Implements function to get URI of a file for any content type.
   *
   * @param
   *   String bundel name / node type
   * @param
   *   String name of table that holds the file FID
   * @param
   *   Array array to add additional conditions
   *
   * @return
   *   Object mysql resultset
   */
  public function aaiGetFileURI($bundle, $fld_tbl, $cond = array()) {
    $database = DB::dbInstance();

    $tbl = 'node';

    if (count($cond)) {
      $conditions = array('type' => $bundle, 'status' => 1);
      foreach ($cond as $k => $v) {
        $conditions[$k] = $v;
      }
    } else {
      $conditions = array('type' => $bundle, 'status' => 1);
    }

    $fld_name = trim(substr($fld_tbl, 11) . '_fid');

    $joins[] = array(
      'tbl' => $fld_tbl,
      'alias' => 'fbv',
      'on' => "fbv.entity_id = tbl.nid",
      'fields' => array($fld_name),
    );
    $joins[] = array(
      'tbl' => 'file_managed ',
      'alias' => 'fm',
      'on' => "fm.fid = fbv.$fld_name",
      'fields' => array('uri'),
    );

    return $database->dbSelectWithJoin($tbl, $joins, $conditions);
  }

  /**
   * Function to return default banner image.
   */
  private function aaiGetDefaultBanner() {
    // get database class instance
    $database = DB::dbInstance();

    $condition_flds = array('type' => 'aai_inner_page_banner', 'title' => 'default');
    $select_fld = 'nid';
    $default_page_nid = $database->dbGetFieldValue('node', $condition_flds, $select_fld);
    $default_page_nid = $default_page_nid[0];

    $img_fld = 'field_inner_page_image';
    $img_fld_tbl = 'field_data_' . $img_fld;

    // get image uri
    $banner_image_uri_rs = $this->aaiGetFileURI('aai_inner_page_banner', $img_fld_tbl, array('nid' => $default_page_nid));
    $banner_image_uri_rs = $banner_image_uri_rs->fetchObject();
    return $banner_image_uri = $banner_image_uri_rs->uri;
  }

  /**
   * Function to return page banner.
   *
   * @return
   *   Image themed image
   */
  public function aaiGetInnerPageBanner() {
    // get database class instance
    $database = DB::dbInstance();

    $banner_image_uri = '';

    // get the current url
    $current_url = '';
    foreach (arg() as $v) {
      if ($current_url == '') {
        $current_url = $v;
      } else {
        $current_url .= '/' . $v;
      }
    }

    if ($current_url == '') {
      return FALSE;
    }

    // get url alias in case of node page
    if (arg(0) == 'node' && is_numeric(arg(1)) && !arg(2)) {
      $current_url = drupal_get_path_alias("$current_url");
    }

    // variable to store image field and image table name
    $img_fld = 'field_inner_page_image';
    $img_fld_tbl = 'field_data_' . $img_fld;

    // check if current page is landing page
    $condition_flds = array('type' => 'aai_inner_page_banner', 'title' => $current_url);
    $select_fld = 'nid';
    $landing_page_nid = $database->dbGetFieldValue('node', $condition_flds, $select_fld);
    $landing_page_nid = $landing_page_nid[0];

    // check if current page is listing page.
    $banner_image_uri_rs = '';
    if ($landing_page_nid) {
      // get image uri
      $banner_image_uri_rs = $this->aaiGetFileURI('aai_inner_page_banner', $img_fld_tbl, array('nid' => $landing_page_nid));
      $banner_image_uri_rs = $banner_image_uri_rs->fetchObject();
      $banner_image_uri = $banner_image_uri_rs->uri;

      // get default image if image not found
      if ($banner_image_uri == '') {
        $banner_image_uri = $this->aaiGetDefaultBanner();
      }
    } else {
      $banner_image_uri = $this->aaiGetDefaultBanner();
    }

    $config = array(
      "style_name" => "inner_page_banner",
      "path" => $banner_image_uri,
      "height" => NULL,
      "width" => NULL,
    );
    
    return theme_image_style($config);
  }

  /**
   * Function to return Airport page menu.
   */
  public function aaiGetAirportMenu() {
    $arg_0 = arg(0);
    $airport_name = arg(2);

    $arg_2 = arg(1);
    
    if(empty(arg(2))) {
      $airport_name = arg(1);
    }

    // set active class for menu
    $active_home = $active_flights = $active_pas_info = $active_transport = 
    $active_city_info = $active_fact_sheet = $active_sec_info = '';

    // menu icon default class
    $menu_class_default = 'air-menu';

    // check for active menu
    switch($arg_2) {
      case $airport_name :
        $active_home = 'active';
        break;
      case 'flights' :
        $active_flights = 'active';
        break;
      case 'passenger-info' :
        $active_pas_info = 'active';
        break;
      case 'transport' :
        $active_transport = 'active';
        break;
      case 'city-info' :
        $active_city_info = 'active';
        break;
      case 'fact-sheet' :
        $active_fact_sheet = 'active';
        break;
      case 'security-info' :
        $active_sec_info = 'active';
        break;
      default :
        $active_home = 'active';
    }

    // formulate menu links array 
    $menu_links = array();
    $menu_links['home'] = array('Home', 'air_home', "$arg_0/$airport_name");
    $menu_links['flights'] = array('Flights', 'air_plane', "$arg_0/flights/$airport_name");
    $menu_links['pas_info'] = array('Passenger Information', 'air_passenger', "$arg_0/passenger-info/$airport_name");
    $menu_links['transport'] = array('Transport', 'air_transport', "$arg_0/transport/$airport_name");
    $menu_links['city_info'] = array('City Information', 'air_citiinfo', "$arg_0/city-info/$airport_name");
    $menu_links['fact_sheet'] = array('Fact Sheet', 'air_factsheet', "$arg_0/fact-sheet/$airport_name");
    $menu_links['sec_info'] = array('Security Information', 'air_security', "$arg_0/security-info/$airport_name");

    // loop through links to build menu items
    $menu = "<ul class = 'menu'>";
    foreach ($menu_links as $k => $v) {
      $menu_active = ${'active_' . $k};

      $menu_label = $v[0];
      $menu_icon = $v[1];
      $menu_lnk = $v[2];

      $menu .= "<li class = '$menu_active'>";
      $menu .= "<i class='$menu_class_default $menu_icon'></i>";
      $menu .= l(t("$menu_label"), $menu_lnk);
      $menu .= "</li>";
      unset($menu_lnk);
      unset($menu_label);
      unset($menu_icon);
    }
    $menu .= "</ul>";
    return $menu;
  }  

  /**
   * Function to return nids According to Arguments.
   *
   * @return
   *   Nids Related to terms
   */
  public function aaiGetBasicPageNids($term) {
    $node_array = taxonomy_select_nodes($term);
    return $node_array;
  }

  /**
   * Function to return Airport Footer menu.
   */
  public function aaiGetAirportFooterMenu() {
    $arg_0 = arg(0);
    $airport_name = arg(2);

    $arg_2 = arg(1);
    
    if(empty(arg(2))) {
      $airport_name = arg(1);
    }
    $database = DB::dbInstance();
    $lang = $this->aaiCurrentLang();
    $airport_tid = $this->aaiGetTermID(ucwords($airport_name), 'Airport');
     // get airport specific node of the respective bundle
    $tbl = 'field_data_field_related_airport';
    $condition_flds = array('entity_type' => 'node',
      'bundle' => 'airports',
      'deleted' => 0,
      'field_related_airport_tid' => $airport_tid,
      //'language' => $lang,
    );
    $flds_to_select = array('entity_id');
    $rs = $database->dbConditionalSelect($tbl, $condition_flds, $flds_to_select);
    $node_id = $rs[0]['entity_id'];
    $tbl2 = 'field_data_field_airport_information';
    $condition_flds2 = array('bundle' => 'airports',
      'entity_id' => $node_id,
      'language' => $lang,
    );
    $flds_to_select2 = array('field_airport_information_value','field_airport_information_revision_id');
    $result = $database->dbConditionalSelect($tbl2, $condition_flds2, $flds_to_select2);

    $url = '';
    foreach ($result as $key => $value) {
      
      $url = "/content/introduction";
      $tbl3 = 'field_revision_field_information_type';
      $condition_flds3 = array('entity_id' => $value['field_airport_information_value'],
      'revision_id' => $value['field_airport_information_revision_id'],
      'language' => $lang,
      );
      $flds_to_select3 = array('field_information_type_value');
      $rows = $database->dbConditionalSelect($tbl3, $condition_flds3, $flds_to_select3);
     
          foreach($rows as $value){
               //out($value['field_information_type_value']);
            if($value['field_information_type_value'] == 'cargo'){
              $url =  $arg_0.'/cargo/'.$airport_name;
              break;
            }
            else{
                  $url = '/services/cargo/aaiclas';
                  break;
            }
            if($value['field_information_type_value'] == 'term-condition'){
              $term_val =  'resuls_found';
              break;
            }
          }
          /*if ($url != '/content/introduction') {
            break;
          }*/
    }
  
    // set active class for menu
    $active_media_center = $active_faq = $active_cargo = $active_complaint = $active_feedback = $active_terms_conditions = '';

    // menu icon default class
    $menu_class_default = 'air-menu';

    // check for active menu
    switch($arg_2) {
      case 'terms-conditions' :
        $active_terms_conditions = 'active';
        break;
      case 'faq' :
        $active_faq = 'active'; 
        break;
      case 'cargo' :
        $active_cargo = 'active';
        break;
      case 'complaint' :
        $active_complaint = 'active';
        break;
      case 'feedback' :
        $active_feedback = 'active';
        break;
      default :
        $active_media_center = 'active';
    }

    // formulate menu links array 
    $menu_links = array();
    $menu_links['media_center'] = array('Media Center', 'air_media', "$arg_0/image-gallery/$airport_name");
    $menu_links['faq'] = array('Faq', 'air_faq', "$arg_0/faq/$airport_name");
    $menu_links['cargo'] = array('Cargo', 'air_cargo', $url);
    // $menu_links['complaint'] = array('Complaint', 'air_complaint', "$arg_0/complaint/$airport_name");
    // $menu_links['feedback'] = array('Feedback', 'air_feedback', "$arg_0/feedback/$airport_name");
    if(isset($term_val)){
     $menu_links['terms_conditions'] = array('Terms & Conditions', 'air_terms_conditions', "$arg_0/terms-conditions/$airport_name");
   }
    // loop through links to build menu items
    $menu = "<ul class = 'menu'>";
    foreach ($menu_links as $k => $v) {
      $menu_active = ${'active_' . $k};

      $menu_label = $v[0];
      $menu_icon = $v[1];
      $menu_lnk = $v[2];

      $menu .= "<li class = '$menu_active'>";
      //$menu .= "<i class='$menu_class_default $menu_icon'></i>";
      $menu .= l(t("$menu_label"), $menu_lnk);
     
     
      $menu .= "</li>";
      unset($menu_lnk);
      unset($menu_label);
      unset($menu_icon);
    }
    $menu .= "</ul>";
    return $menu;
  }


  /**
   * Function to return Airport Term Details (Airport Code etc.)
   *
   * @param String $term_name
   *   Name of term for which values are searched
   * @param Array $term_details
   *   array of details to be selected.
   *
   * @return Array $result
   *   associative array of requested values, or full term object
   *   if details array is blank.
   * Function to return Airport Term Details (Airport Code etc.).
   */
  public function aaiGetAirportTermDetails($term_name, $term_details = array()) {
    $lang = $this->aaiCurrentLang();

    // get Airport vocabulary details
    $vocab_det = array('vid', 'machine_name');
    $airport_vocab_det_ary = $this->aaiGetVocabDetailByName('Airport', $vocab_det);
    $airport_vocab_mac_name = $airport_vocab_det_ary['machine_name'];

    // get airport term ID
    $airport_tid_ary = $this->aaiGetTermIdFromName($term_name, $airport_vocab_mac_name);
    $term = taxonomy_term_load($airport_tid_ary[0]);

    if (count($term_details)) {
      foreach ($term_details as $value) {
        if($value == 'tid') {
          $term_ary['tid'] = $term->tid;
        } elseif($value == 'field_region') {
          $term_ary['field_region'] = $term->field_region[$lang][0]['tid']; 
        } elseif($value == 'field_airport_code') {
          $term_ary['field_airport_code'] = $term->field_airport_code[$lang][0]['value'];
        }
      }
      return $term_ary;
    } else {
      return $term;
    }    
  }

/**
   * Function to return Airport Lists By Region Tid(Airport Code etc.)
   *
   * @param String $term_name
   *   Name of term for which values are searched
   * @param Array $term_details
   *   array of details to be selected.
   *
   * @return Array $result
   *   associative array of requested values, or full term object
   *   if details array is blank.
   * Function to return Airport Term Details (Airport Code etc.).
   */
  public function aaiGetAirportList($term_id, $term_details = array()) {
    $database = DB::dbInstance();
    $tbl3 = 'field_data_field_region';
    $condition_flds3 = array('field_region_tid' => $term_id,
     'bundle' => 'auto_created_voc9_635',
    );
    $flds_to_select3 = array('entity_id');
    $rows = $database->dbConditionalSelect($tbl3, $condition_flds3, $flds_to_select3);
    foreach($rows as $value){
      $airports_list[] = $value['entity_id'];
    }  
    return $airports_list;
  }

/**
   * Function to return Airport Lists By Region Tid(Airport Code etc.)
   *
   * @param Array $airports
   *   array of airports Tid.
   *
   * @return Array $result
   *   associative array of requested values, or full term object
   *   if details array is blank.
   * Function to return Users with publisher role for selected airports (Airport Code etc.).
   */
  public function aaiGetAirportsPublishers($airports) {   
    $database = DB::dbInstance();
    foreach ($airports as $value) {
       $airportId = $value;
       $tbl_airport = 'field_data_field_airport';
       $condition_flds3 = array('field_airport_tid' => $airportId,
         'bundle' => 'user',
       );
       $flds_to_select3 = array('entity_id');
       $rows = $database->dbConditionalSelect($tbl_airport, $condition_flds3, $flds_to_select3);
    
        foreach($rows as $user){
          $users_array[] = $user['entity_id'];
        }  
    }
 $users_array = array_unique($users_array);
    return $users_array;
  }
  /**
   * function to get Airport name from airport code
   *
   * @param String
   *   airport code
   *
   * @return String
   *   airport name
   */
  public function aaiGetAirportNameFromCode($airport_code = '') {
    if ($airport_code == '') {
      return false;
    }
    $airport_code = strtoupper($airport_code);
    $airport_codes = unserialize(variable_get('airport_codes'));
    if (isset($airport_codes[$airport_code])) {
      if (is_array($airport_codes[$airport_code])) {
        $airport_name = ucwords($airport_codes[$airport_code]['name']);
      } else {
        $airport_name = ucwords($airport_codes[$airport_code]);
      }
    } else {
      $airport_name = strtoupper($airport_code);
    }
    return $airport_name;
  }

  /**
   * Function to return Airport Term Details (Airport Code etc.).
   *
   * @param String
   *   Three character home airport code (for which search is performed)
   * @param Array
   *   Array to gt all coditions for record filtering
   *   $condition = array(
   *     flight_number => ''
   *     arrival_departure => '' * // arrival, departure
   *     flight_type => '' // i, d
   *     from_date => ''
   *     to_date => ''
   *     destination_airport_code => '' // to get flights to a destination
   *     local_airport_code => '' // to get flights from a given souce
   *     limit => '' // set number of records to select, starting from 0
   *   )
   * @param Number
   *   Boolean value indicating whether to replace airport code with full
   *   airport name
   *
   * @return Array
   *   associative array of fields and respective values.
   */
  public function aaiGetFilghtDetail($airport_code, $condition = array(), $expand_airport_code = 0) {
    $database = DB::dbInstance();
    $airport_code = strtoupper($airport_code);
    $from_dt = $to_date = '';
    $tbl = variable_get('aai_fids_active_table');
    $expression = '';
    $con_flds = array();
    $fld_to_select = array();
    $limit = array();

    // add airport code condition
   //  $con_flds['FIDS_DELETE_FLAG'] = 0;
    // check whether flight is arriving or departing
    $arr_dep = strtolower($condition['arrival_departure']);

    if ($arr_dep == 'departure') {
      $con_flds['LOCAL_AIRPORT'] = "$airport_code";
       $con_flds['ARRIVAL_DEPARTURE_FLAG'] = 2;
      
    } else {
      $con_flds['ARRIVAL_DEPARTURE_FLAG'] = 1;
      $con_flds['LOCAL_AIRPORT'] = "$airport_code";
    }

    foreach ($condition as $k => $v) {
      switch($k) {
        case 'arrival_departure':
          if (!empty($v) && !is_null($v)) {
            $v = strtolower($v);
            $fld_to_select = array(
                  'LOCAL_AIRPORT',
                  'ARRIVAL_DEPARTURE_FLAG',
                  'INTERNATIONAL_DOMESTIC_FLAG',
                  'AIRLINE_CODE',
                  'FLIGHT_NUMBER',
                  'SOURCE_DESTINATION',
                  'VIA',
                  'VIA1',
                  'SCHED_DATE',
                  'SCHED_TIME',
                  'EST_DATE',
                  'EST_TIME',
                  'ACT_DATE',
                  'ACT_TIME',
                  'FLIGHT_STATUS',
                  'TERMINAL',
                  'GATES',
                  'COUNTERS',
                  'BELT_NUMBER',
                  'dateval',
                );           
          }
          break;
        case 'flight_number':
          if (!empty($v) && !is_null($v)) {
            $v = strtoupper($v);

            // flight number condition is dependent on arrival/departure flight
            $con_flds['FLIGHT_NUMBER'] = "$v";
          }
          break;
        case 'flight_type':
          if (!empty($v) && !is_null($v)) {
            $v = strtoupper($v);

            // flight type condition is dependent on arrival/departure flight
            $arr_dep = strtolower($condition['arrival_departure']);
            $con_flds['ARRIVAL_DEPARTURE_FLAG'] = "$v";
          }
          break;
        case 'from_date':
          if (!empty($v) && !is_null($v)) {
            $from_dt = $v;
          }
          break;
        case 'ACT_TIME':
          /*if (!empty($v) && !is_null($v)) {
            $to_date = $v;
          }*/
                $v = strtoupper($v);
          break;
        case 'TERMINAL':
          /*if (!empty($v) && !is_null($v)) {
            $to_date = $v;
          }*/
                $v = strtoupper($v);
          break;          
        case 'destination_airport_code':
          // get all flights where searched airport is part of travel route,
          // for flights departing from home airport
          $v = strtoupper($v);
          $con_flds['source_destination'] = array("$v", 'LIKE', 'OR');
          break;
        case 'local_airport_code':
          // to search for flights arriving from a given airport to home airport
          $v = strtoupper($v);
          $con_flds['LOCAL_AIRPORT'] = $airport_code;
          break;
        case 'limit':
          if (!empty($v) && !is_null($v)) {
            $limit = array(0, $v);
          }
          break;
      }
    }
    
    // add query condition for date range
    if ($from_dt == '') { // || $to_date == '') {
      $arr_dep = strtolower($condition['arrival_departure']);
          $con_flds['ACT_DATE'] = array("DATE_SUB(NOW(), INTERVAL 48 HOUR)", '>=', 'where');
          $con_flds['~!ACT_DATE'] = array("DATE_ADD(NOW(), INTERVAL 48 HOUR)", '<=', 'where');
    } else  {
      $search_date = $from_dt;
      $tmp = explode('-', $search_date);
      $tmp = mktime(0, 0, 0, $tmp[1], $tmp[2] + 1, $tmp[0]);
      $search_date2 = date('Y-m-d', $tmp);
      $arr_dep = strtolower($condition['arrival_departure']);
        $con_flds['ACT_DATE'] = array($search_date, '>=');
          $con_flds['~!ACT_DATE'] = array($search_date2, '<=');
    }

    // set order by clause
    $groupby = '';
    // set orderby clause
    $orderas = 'DESC';

    $arr_dep = strtolower($condition['arrival_departure']);
    $orderby = 'ACT_DATE';

    // execute query and get result
    $rs = $database->dbSelectWithExpression($tbl,$expression,$con_flds,$fld_to_select,$groupby,$orderby,$orderas,$limit); 
    // check if airport code is to be replaced with airport name 
    if ($expand_airport_code) {
      $code_key = '';
      $code_key = 'SOURCE_DESTINATION';

      $airport_names = '';
      $selected_codes = $rs[$code_key];
      if (strpos($selected_codes, ',') != FALSE) {
        $selected_codes = explode(',', $selected_codes);
        foreach ($selected_codes as $v) {
          if ($airport_names == '') {
            $airport_names = $this->aaiGetAirportNameFromCode($v);
          } else {
            $airport_names .= ', ' . $this->aaiGetAirportNameFromCode($v);
          }
        }
      } else {
        $airport_names = $this->aaiGetAirportNameFromCode($v);
      }

      $rs[$code_key] = $airport_names;
    }

    return $rs;
  }
  
  /**
   * Functon to return Schedule of flights according Input Date 
   */
  public function aaiGetFilghtScheduleDetail($airport_code, $condition = array(), $expand_airport_code = 0){
  $database = DB::dbInstance();
    $local_airport_code = strtoupper($airport_code);
  $day_week = date('w', strtotime($condition['from_date']));
  if($day_week == '0'){
    $day_week = '7';
  }
  if($condition['arrival_departure'] == 'departure'){
    $arrival_departure_flag = '2';
  }else{
    $arrival_departure_flag = '1';
  }
  $from_dt = $to_date = '';
    $tbl = 'aai_fids_schedule';
    $expression = '';
    $con_flds = array();
    $fld_to_select = array();
    $limit = array();
  $destination_airport = $condition['destination_airport_code'];
  if($condition['arrival_departure'] == 'departure'){
    $query = db_select('aai_fids_schedule', 'afs');
    $query->fields('afs', array('airline_code','frequency','sched_time','flight_number',  'source_destination_airport','eff_dt_from','eff_dt_till'));
    $query->condition('local_airport', $local_airport_code);
    $query->condition('arrival_departure_flag', $arrival_departure_flag);
    $query->condition('source_destination', $condition['destination_airport_code']);
    $query->condition('eff_dt_from', $condition['from_date'],'<=');
    $query->condition('eff_dt_till', $condition['from_date'],'>=');
    $query->condition('frequency', '%'.db_like($day_week). '%', 'LIKE');
    $results = $query->execute()->fetchAll();
    $flight_schedule_result = array();
    foreach($results as $flightDetails){
      $flight_schedule_result[$flightDetails->flight_number]['frequency'] = $flightDetails->frequency;
    $flight_schedule_result[$flightDetails->flight_number]['flight_number'] = $flightDetails->flight_number;
    $flight_schedule_result[$flightDetails->flight_number]['source_destination_airport'] = $flightDetails->source_destination_airport;
    $flight_schedule_result[$flightDetails->flight_number]['eff_dt_from'] = $flightDetails->eff_dt_from;
    $flight_schedule_result[$flightDetails->flight_number]['eff_dt_till'] = $flightDetails->eff_dt_till;
    $flight_schedule_result[$flightDetails->flight_number]['sched_time'] = $flightDetails->sched_time;
    $flight_schedule_result[$flightDetails->flight_number]['airline_code'] = $flightDetails->airline_code;
    }
    return $flight_schedule_result;
  } 
  
  if($condition['arrival_departure'] == 'arrival'){
    $query = db_select('aai_fids_schedule', 'afs');
    $query->fields('afs', array('airline_code','frequency','sched_time','flight_number','source_destination_airport','eff_dt_from','eff_dt_till'));
    $query->condition('local_airport', $local_airport_code);
    $query->condition('arrival_departure_flag', $arrival_departure_flag);
    $query->condition('source_destination', $condition['local_airport_code']);
    $query->condition('eff_dt_from', $condition['from_date'],'<=');
    $query->condition('eff_dt_till', $condition['from_date'],'>=');
    $query->condition('frequency', '%'.db_like($day_week). '%', 'LIKE');
    $results = $query->execute()->fetchAll();
    $flight_schedule_result = array();
    foreach($results as $flightDetails){
      $flight_schedule_result[$flightDetails->flight_number]['frequency'] = $flightDetails->frequency;
    $flight_schedule_result[$flightDetails->flight_number]['flight_number'] = $flightDetails->flight_number;
    $flight_schedule_result[$flightDetails->flight_number]['source_destination_airport'] = $flightDetails->source_destination_airport;
    $flight_schedule_result[$flightDetails->flight_number]['eff_dt_from'] = $flightDetails->eff_dt_from;
    $flight_schedule_result[$flightDetails->flight_number]['eff_dt_till'] = $flightDetails->eff_dt_till;
    $flight_schedule_result[$flightDetails->flight_number]['sched_time'] = $flightDetails->sched_time;
    $flight_schedule_result[$flightDetails->flight_number]['airline_code'] = $flightDetails->airline_code;
    }
    return $flight_schedule_result;
  } 
  }
  /**
   * Function to build airport search form, used on airport homepage
   */
  public function aaiGetFilghtSearchForm() {
    $airport = strtolower(arg(1));
    $action = "/airports/flights/$airport/?type=a";
    $form = "<section id='search'>
      <form action='$action'>
        <div class='search_panel'>
          <input type='text' id='search-input' name='search' class='col-md-8' placeholder='Search Flight' tabindex='1'>
          <button type='submit' class = 'btn btn-primary search_btn'>
            <i class='fa fa-search' aria-hidden='true'></i>
          </button>
        </div>
      </form>
    </section>";
    return $form;
  }

  /*
   * Function to generate XML for tender
   */
  public function aaiGenerateTenderXML($node, $tendername) {
    Global $base_url;
    $functions = AAI::getInstance();
    $lang = $functions->aaiCurrentLang();
    // getting airport location
    $tenderAirVAl = $node->field_airport[$lang][0]['tid'];
    $tenderAirArr = taxonomy_term_load($tenderAirVAl);
    $tenderAirName = $tenderAirArr->name;
    //Opening date
    $openingDate = $node->field_opening_of_tender[$lang][0]['value'];
    $openingDateformated = date('d-m-Y',strtotime($openingDate));
    $openingTimeformated = date('H:i',strtotime($openingDate));

    //Opening date
    $saleDate = $node->field_sale_of_tender[$lang][0]['value'];
    $saleDateformated = date('d-m-Y',strtotime($saleDate));
    $saleTimeformated = date('H:i',strtotime($saleDate));
 
    // created
    $createDate = $node->created;
    $createDateformated = format_date($createDate, 'd-m-Y');

    $nidpath = 'node/' . $node->nid;
    $nodeUrl = $base_url . '/' . drupal_get_path_alias($nidpath);

    $tender_val = $node->field_tender_estimate_cost[$lang][0]['value'];
    
    //Creating Xml to save for the new tender
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= "\n <NOOFTENDER>";
    $xml .= "\n\t <TENDERS>";
    $xml .= "\n\t\t <ORGNAME>Airports Authority of India</ORGNAME>";
    $xml .= "\n\t\t <TENDERURL>" . $nodeUrl . "</TENDERURL>";
    $xml .= "\n\t\t <ORGTYPE>5</ORGTYPE>";
    $xml .= "\n\t\t <T_TITLE>" . $node->title . "</T_TITLE>";
    $xml .= "\n\t\t <T_REF_NO>" . $tendername . "</T_REF_NO>";
    $xml .= "\n\t\t <TENDER_VAL>" . $tender_val . "</TENDER_VAL>";
    $xml .= "\n\t\t <LOCATION>" . $tenderAirName . "</LOCATION>";
    $xml .= "\n\t\t <FIRST_A_DATE>" . $createDateformated . "</FIRST_A_DATE>";      
    $xml .= "\n\t\t <LAST_COLL_DATE>" . $saleDateformated . "</LAST_COLL_DATE>";
    $xml .= "\n\t\t <LAST_COLL_TIME>" . $saleTimeformated . "</LAST_COLL_TIME>";
    $xml .= "\n\t\t <LAST_SUBMIT>" . $saleDateformated . "</LAST_SUBMIT>";
    $xml .= "\n\t\t <LAST_SUBMIT_TIME>" . $saleTimeformated . "</LAST_SUBMIT_TIME>";      
    $xml .= "\n\t\t <WORK_DESC>" . $node->title . "</WORK_DESC>";
    $xml .= "\n\t\t <OPEN_DATE>" . $openingDateformated . "</OPEN_DATE>";
    $xml .= "\n\t\t <OPEN_TIME>" . $openingTimeformated . "</OPEN_TIME>";
    $xml .= "\n\t\t <T_TYPT>1</T_TYPT>";
    $xml .= "\n\t\t <WORKNO>3</WORKNO>";
    $xml .= "\n\t\t <PRODUCT_CAT>62</PRODUCT_CAT>";
    $xml .= "\n\t\t <SUB_CAT>No Sub</SUB_CAT>";
    $xml .= "\n\t\t <EMD>Read Document</EMD>";
    $xml .= "\n\t\t <DOC_COST>Read Document</DOC_COST>";
    $xml .= "\n\t\t <PRE_QUAL>For Further details see the tender document</PRE_QUAL>";
    $xml .= "\n\t\t <SECTOR>45</SECTOR>";
    $xml .= "\n\t\t <STATE>10</STATE>";
    $xml .= "\n\t\t <NAME>Asim Sayeed</NAME>";
    $xml .= "\n\t\t <E_MAIL>aaicmshdesk1@aai.aero</E_MAIL>";
    $xml .= "\n\t\t <PHONE>1124642005</PHONE>";
    $xml .= "\n\t\t <FAX>1124642005</FAX>";
    $xml .= "\n\t\t <CITY>" . $tenderAirName . "</CITY>";
    $xml .= "\n\t\t <ADDRESS>Delhi</ADDRESS>";
    $xml .= "\n\t\t <PREBID> </PREBID>";
    $xml .= "\n\t </TENDERS>";
    $xml .= "\n </NOOFTENDER>";

    return $xml;
  }

  /**
   * Function to return tabs for flight search page on airports.
   */
  public function aaiGetFIDSTabs() {
    $in_or_out = arg(3);
    $airport_name = ucwords(arg(2));
    $airport_code = $this->aaiGetAirportTermDetails($airport_name, array('field_airport_code'));
    $airport_code = $airport_code['field_airport_code'];
    //$airport_code = 'TRV';
    $hidden_fld_acode = base64_encode($airport_code);

    $title = t("$airport_name Flights Detail");

    $out .= "<div id='tabs'>
    <ul>
      <li id = 'aai-depart-info'><a href='#departure-tab'>" . t('Departure Flight') . "</a></li>
      <li id = 'aai-arrival-info'><a href='#arrival-tab'>" . t('Arrival Flight') . "</a></li>
      <li id = 'aai-airline-info'><a href='#airline-info-tab'>" . t('Airlines Information') . "</a></li>
    </ul> 
        <div id='departure-tab'>
        <div class='row'><div class='col-md-12'>
      <div class='col-md-3'> <label class = 'form-lable'>" . t('Date') . "</label>
      <input type = 'date' id = 'fids-from-date' value = " . date("d-m-Y") . " />
      </div>";
      $airports_served = $this->aaiGetAirportsServedList($airport_code, 'departure');
      if (count($airports_served)) {        
        $out .= "<div class='col-md-3'><label class = 'form-lable'>" . t('Destination Airport') . "</label>";
        $out .= "<select name='airports' id='fids-airport'>";
        $out .= "<option value=0>--" . t('Please Select') . "--</option>";
        foreach ($airports_served as $a_code => $a_name) {
          $out .= "<option value = $a_code>" . t($a_code) . ' - ' . t($a_name) . "</option>";
        }
        $out .= "</select></div>";
      }
      $out .= "<div class='col-md-3'><label class = 'form-lable'>" . t('Flight No') . ".</label>";
      $out .= "<input type = 'text' id = 'fids-flight-no' placeholder='Flight No.' /></div>";
      $out .= "<div class='col-md-3 '><input id = 'fids-search-btn' type = 'button' value = " . t('Search') . " onclick = 'javascript:get_fids_flight_details(\"departure\");'></div>";
      $out .= "<img id='aai-throbber' src='/sites/all/modules/custom/airports/img/throbber.gif' />";
      $out .= "</div></div><div class = 'fids-result-wrapper'>
        <div id='fids-data-count'></div>
        <div id='fids-data'></div>
      </div>";
    $out .= "</div>
      <div id='arrival-tab'>
      <div class='row'><div class='col-md-12'>
        <div class='col-md-3 '><label class = 'form-lable'>" . t('Date') . "</label>
      <input type = 'date' id = 'fids-from-date' value = " . date("d-m-Y") . " /></div>";

      $airports_served = $this->aaiGetAirportsServedList($airport_code, 'arrival');

      if (count($airports_served)) {
        $out .= " <div class='col-md-3'><label class = 'form-lable'>" . t('Source Airport') . "</label>";
        $out .= "<select name='airports' id='fids-airport'>";
        $out .= "<option value=0>--" . t('Please Select') . "--</option>";
        foreach ($airports_served as $a_code => $a_name) {
          $out .= "<option value = $a_code>" . t($a_code) . ' - ' . t($a_name) . "</option>";
        }
        $out .= "</select></div> ";
      }
      $out .= "<div class='col-md-3'><label class = 'form-lable'>" . t('Flight No') . ".</label>";
      $out .= "<input type = 'text' id = 'fids-flight-no' placeholder='Flight No.'/></div> ";
      $out .= " <div class='col-md-2'><input id = 'fids-search-btn1' type = 'button' value = " . t('Search') . " onclick = 'javascript:get_fids_flight_details(\"arrival\");'></div> ";
      $out .= "<img id='aai-throbber' src='/sites/all/modules/custom/airports/img/throbber.gif' />";
        $out .="</div></div>";
      $out .= "<div class = 'fids-result-wrapper1'>
        <div id='fids-data-count1'></div>
        <div id='fids-data1'></div>
      </div>";
    $out .= "  </div>
      <div id='airline-info-tab'></div>
    </div><input type='hidden' id = 'fids-home-airport' value=$hidden_fld_acode />";
  
    return "<div class='page-title'>$title</div>" . $out;
  }

  /**
   * Function to return themed output for FIDS search result
   */
  public function aaiThemeFIDSOutput($flight_data = array(), $type, $home_airport_code) {
    if (!count($flight_data)) {
      return FALSE;
    }

    // set the fields that will be available for display
    $selected_flds = array();
    switch ($type) {
      case 'arrival':
        $selected_flds = array(
          'ARRIVAL_DEPARTURE_FLAG',
          'FLIGHT_NUMBER',
          'SOURCE_DESTINATION',
          'VIA',
          'SCHED_DATE',
          'SCHED_TIME',          
          'EST_DATE',
          'EST_TIME',           
          'ACT_DATE',
          'ACT_TIME',  
          'TERMINAL',  
          'GATES',
          'BELT_NUMBER',      
          'FLIGHT_STATUS',
          'INTERNATIONAL_DOMESTIC_FLAG',
          'AIRLINE_CODE',

        );

        $tbl_base_structure = "<thead>
          <tr>
            <th  rowspan='2'>" . t('Flight No') . " </th>
            <th  rowspan='2'>" . t('Source') . " </th>
            <th  rowspan='2'>" . t('Via') . " </th>
            <th colspan='3'>" . t('Arrival') . "</th>            
            <th  rowspan='2'>" . t('Terminal') . " </th>
            <th rowspan='2'>" . t('Belt Number') . "</th> 
            <th rowspan='2'>" . t('Status') . "</th>            
          </tr>
          <tr>
            <th>" . t('Scheduled') . "</th>
            <th>" . t('Estimated') . "</th>
            <th>" . t('Actual') . "</th>
          </tr>
        </thead>
        <tfoot>
          <tr>
            <th>" . t('Flight No') . "</th>
            <th>" . t('Source') . "</th>
            <th>" . t('Via') . "</th>
            <th>" . t('Scheduled') . "</th>
            <th>" . t('Estimated') . "</th>
            <th>" . t('Actual') . "</th>         
            <th>" . t('Terminal') . "</th>
            <th>" . t('Belt Number') . "</th>
            <th>" . t('Status') . "</th>
          </tr>
        </tfoot>";
        $out = "<table id='aai-fids-result-tbl-arv' class='aai-fids-tbl table table-bordered display'>";
        break;
      case 'departure':
        $selected_flds = array(
          'ARRIVAL_DEPARTURE_FLAG',
          'FLIGHT_NUMBER',
          'SOURCE_DESTINATION',
          'VIA',
          'SCHED_DATE',
          'SCHED_TIME',          
          'EST_DATE',
          'EST_TIME',
          'ACT_DATE',
          'ACT_TIME',
          'TERMINAL',
          'GATES',
          'BELT_NUMBER',
          'FLIGHT_STATUS',
          'INTERNATIONAL_DOMESTIC_FLAG',
          'AIRLINE_CODE',
        );

        $tbl_base_structure = "<thead>
          <tr>
            <th  rowspan='2'>" . t('Flight No') . " </th>
            <th  rowspan='2'>" . t('Destination') . " </th>
            <th  rowspan='2'>" . t('Via') . " </th>
            <th colspan='3'>" . t('Departure') . "</th>            
            <th  rowspan='2'>" . t('Terminal') . " </th>
            <th rowspan='2'>" . t('Belt Number') . "</th> 
            <th rowspan='2'>" . t('Status') . "</th>            
          </tr>
          <tr> 
            <th>" . t('Scheduled') . "</th>
            <th>" . t('Estimated') . "</th>
            <th>" . t('Actual') . "</th>
          </tr>
        </thead>
        <tfoot>
          <tr>
            <th>" . t('Flight No') . "</th>
            <th>" . t('Destination') . "</th>
            <th>" . t('Via') . "</th>
            <th>" . t('Scheduled') . "</th>
            <th>" . t('Estimated') . "</th>
            <th>" . t('Actual') . "</th>          
            <th>" . t('Terminal') . "</th>
            <th>" . t('Belt Number') . "</th>     
            <th>" . t('Status') . "</th>
          </tr>
        </tfoot>";
        $out = "<table id='aai-fids-result-tbl-dep' class='aai-fids-tbl table table-bordered display'>";
        break;
    }

    // formulate the output structure
    $out .= $tbl_base_structure;
    $out .= "<tbody>";
    foreach ($flight_data as $data) {
      $out .= "<tr>";
      if ($type == 'arrival') {
        foreach ($selected_flds as $fld) {
          $fld_val = trim($data[$fld]);
          switch ($fld) {
            case 'AIRLINE_CODE':
              $airline = $this->aaiGetAirportNameFromCode($fld_val); 
              break;   
            case 'FLIGHT_NUMBER':
              $out .= "<td>" . t($airline.$fld_val) . "</td>";
              break;
            case 'SOURCE_DESTINATION':
              $tmp = $this->aaiGetAirportNameFromCode($fld_val);
              $out .= "<td>" . t($tmp) . "</td>";
              break;
            case 'VIA':
              $tmp = $this->aaiGetAirportNameFromCode($fld_val);
              $out .= "<td>" . t($tmp) . "</td>";
              break;
            case 'SCHED_DATE':
              $date = $fld_val;
              // $out .= "<td>" . t($fld_val) . "</td>";
              break;
            case 'SCHED_TIME':
              $newstr = substr_replace($fld_val, ":", 2, 0);
               if($newstr == ""){ $newstr = "00";}
               $out .= "<td>" . t($newstr) . "</td>";  
              break; 
             case 'EST_DATE':
              $date = $fld_val;
              // $out .= "<td>" . t($fld_val) . "</td>";
              break;
            case 'EST_TIME':
              $newstr = substr_replace($fld_val, ":", 2, 0);
              if($newstr == ""){ $newstr = "00";}
               $out .= "<td>" . t($newstr) . "</td>";  
              break;     
            case 'ACT_DATE':
              $date = $fld_val;
              // $out .= "<td>" . t($fld_val) . "</td>";
              break;
            case 'ACT_TIME':
              $newstr = substr_replace($fld_val, ":", 2, 0);
              if($newstr == "") { $newstr = "00";}
              $out .= "<td>" . t($newstr) . "</td>";  
              break;                            
            case 'TERMINAL':
              $out .= "<td>" . t($fld_val) . "</td>";
              break;
            case 'FLIGHT_STATUS':              
              if ($fld_val == '1') {
                $out .= "<td>ON TIME</td>";
              } elseif ($fld_val == '2') {
                $out .= "<td>ARRIVED</td>";
              } elseif ($fld_val == '3') {
                $out .= "<td>DEPARTED</td>";
              } elseif ($fld_val == '4') {
                $out .= "<td>CHECK IN OPEN</td>";
              } elseif ($fld_val == '5') {
                $out .= "<td>SECURITY CHECK</td>";
              } elseif ($fld_val == '6') {
                $out .= "<td>GATE OPEN</td>";
              } elseif ($fld_val == '7') {
                $out .= "<td>FINAL CALL</td>";
              } elseif ($fld_val == '8') {
                $out .= "<td>DELAYED</td>";
              } elseif ($fld_val == '9') {
                $out .= "<td>CANCELLED</td>";
              } else {
                $out .= "<td></td>";
              }
              break;  
            case 'BELT_NUMBER':
              $out .= "<td>" . t($fld_val) . "</td>";
              break;
          }
        }
      } else {
        // departure flights FIDS search
        foreach ($selected_flds as $fld) {
          $fld_val = trim($data[$fld]);
          switch ($fld) {
            case 'AIRLINE_CODE':
              $airline = $this->aaiGetAirportNameFromCode($fld_val);
              break;
            case 'FLIGHT_NUMBER':
              $out .= "<td>" . t($airline.$fld_val) . "</td>";
              break;
            case 'SOURCE_DESTINATION':
              $tmp = $this->aaiGetAirportNameFromCode($fld_val);
              $out .= "<td>" . t($tmp) . "</td>";
              break;
            case 'VIA':
              $tmp = $this->aaiGetAirportNameFromCode($fld_val);
              $out .= "<td>" . t($tmp) . "</td>";
              break;
            case 'SCHED_DATE':
              $date = $fld_val;
              // $out .= "<td>" . t($fld_val) . "</td>";
              break;
            case 'SCHED_TIME':
              $newstr = substr_replace($fld_val, ":", 2, 0);
              if($fld_val == 0) { $newstr = "00:00"; }
              $out .= "<td>" . t($newstr) . "</td>";  
              break;
            case 'EST_DATE':
              $date = $fld_val;
              // $out .= "<td>" . t($fld_val) . "</td>";
              break;
            case 'EST_TIME':
              $newstr = substr_replace($fld_val, ":", 2, 0);
              if($fld_val == 0) { $newstr = "00:00"; }
              $out .= "<td>" . t($newstr) . "</td>";
              break;
            case 'ACT_DATE':
              $date = $fld_val;
              // $out .= "<td>" . t($fld_val) . "</td>";
              break;
            case 'ACT_TIME':
              $newstr = substr_replace($fld_val, ":", 2, 0);
              if($fld_val == 0) { $newstr = "00:00"; }
              $out .= "<td>" . t($newstr) . "</td>";
              break;
            case 'FLIGHT_STATUS':
              if ($fld_val == '1') {
                $out .= "<td>ON TIME</td>";
              } elseif ($fld_val == '2') {
                $out .= "<td>ARRIVED</td>";
              } elseif ($fld_val == '3') {
                $out .= "<td>DEPARTED</td>";
              } elseif ($fld_val == '4') {
                $out .= "<td>CHECK IN OPEN</td>";
              } elseif ($fld_val == '5') {
                $out .= "<td>SECURITY CHECK</td>";
              } elseif ($fld_val == '6') {
                $out .= "<td>GATE OPEN</td>";
              } elseif ($fld_val == '7') {
                $out .= "<td>FINAL CALL</td>";
              } elseif ($fld_val == '8') {
                $out .= "<td>DELAYED</td>";
              } elseif ($fld_val == '9') {
                $out .= "<td>CANCELLED</td>";
              } elseif ($fld_val == '0') {
                $out .= "<td>N/A</td>";
              } else {
                $out .= "<td> </td>";
              }
              break;
            case 'GATES':
              $out .= "<td>" . t($fld_val) . "</td>";
              break;
             case 'TERMINAL':
              $out .= "<td>" . t($fld_val) . "</td>";
              break;
          }
        }
      }
      $out .= "</tr>";
    }

    $out .= "</tbody></table>";
    if ($type == 'arrival') {
      $out .= "<script>jQuery('#aai-fids-result-tbl-arv').DataTable();</script>";
    } else {
      $out .= "<Script>jQuery('#aai-fids-result-tbl-dep').DataTable();</script>";
    }
    return $out;
  }

  /**
   * Functiom to return list of airports to which flight service is
   * available from the airport
   *
   * @param String
   *   Airport 3 character code
   * @param String
   *   Flight tyep i.e arrival or departure
   *
   * @return Associative Array
   *   array list of airport codes and name to/from which flight is available.
   */
  public function aaiGetAirportsServedList($airport_code, $type = '') {
    $database = DB::dbInstance();
  
    $airport_code = strtoupper($airport_code);
    $flight_to = array();

    // get airports list for the airport, choose all airport codes from last 14 days
    // so we get all posible airport codes to which flights are available
    $tbl = variable_get('aai_fids_active_table', 'aai_fids_data_log');
    $condition_flds = array();
    if ($type == 'departure') {
      $condition_flds = array(
        'LOCAL_AIRPORT' => $airport_code,
        'SCHED_DATE' => array("DATE_SUB(NOW(), INTERVAL 2 WEEK)", '>=', 'where'),
        'ARRIVAL_DEPARTURE_FLAG' => 2,
      );
    } else if ($type == 'arrival') {
      $condition_flds = array(
        'SCHED_DATE' => array("DATE_SUB(NOW(), INTERVAL 2 WEEK)", '>=', 'where'),
        //'LOCAL_AIRPORT' => array("$airport_code", 'LIKE', 'OR'),
        'LOCAL_AIRPORT' => array("$airport_code", 'LIKE'),
        'ARRIVAL_DEPARTURE_FLAG' => 1,
      );
    } else {
      $condition_flds = array(
        'SCHED_DATE' => array("DATE_SUB(NOW(), INTERVAL 2 WEEK)", '>=', 'where'),
        'LOCAL_AIRPORT' => array("$airport_code", 'LIKE'),
      );
    }
    $flds_to_select = array('SOURCE_DESTINATION');
    $airports_rs = $database->dbConditionalSelect($tbl, $condition_flds, $flds_to_select);
 
    if (is_array($airports_rs)) {
      // get array holding all airport names
      $aai_airport_codes = unserialize(variable_get('airport_codes'));
      foreach ($airports_rs as $value) {
        foreach ($value as $fld => $vv) {
          $codes = $vv;
          if (strpos($codes, ',') != FALSE) {
            $codes = explode(',', $codes);
            foreach ($codes as $cods) {
              if (!array_key_exists($cods, $flight_to)) {
                $cods = strtoupper($cods);
                $airport_name = $this->aaiGetAirportNameFromCode($cods);
                $flight_to[$cods] = $airport_name;
              }
            }
          } else {
            if (!array_key_exists($codes, $flight_to)) {
              $cods = strtoupper($codes);
              $airport_name = $this->aaiGetAirportNameFromCode($cods);
              $flight_to[$cods] = $airport_name;
            }
          }
        }
      }
    }
    $flight_to = array_unique($flight_to);

    return $flight_to;
  }


  
  /**
   * Functiom to return list of Schedule airports to which Schedule is
   * available from the airport
   *
   * @param String
   *   Airport 3 character code
   * @param String
   *   Flight type i.e arrival or departure
   *
   * @return Associative Array
   */
  public function aaiGetAirportsScheduleList($airport_code, $type = '') {
    $database = DB::dbInstance();
  $airport_code = strtoupper($airport_code);
  $flight_to = array();
    $tbl = 'aai_fids_schedule';
    $condition_flds = array();
    if ($type == 'departure') {
      $condition_flds = array(
        'local_airport' => $airport_code,
      );
    } 
  if ($type == 'arrival') {
      $condition_flds = array(
        'local_airport' => $airport_code,
      );
    } 
  $flds_to_select = array('SOURCE_DESTINATION', 'local_airport');
    $airports_rs = $database->dbConditionalSelect($tbl, $condition_flds, $flds_to_select);
    if (is_array($airports_rs)) {
      // get array holding all airport names
      $aai_airport_codes = unserialize(variable_get('airport_codes'));
      foreach ($airports_rs as $value) {
        foreach ($value as $fld => $vv) {
          $codes = $vv;
          if (strpos($codes, ',') != FALSE) {
            $codes = explode(',', $codes);
            foreach ($codes as $cods) {
              if (!array_key_exists($cods, $flight_to)) {
                $cods = strtoupper($cods);
                $airport_name = $this->aaiGetAirportNameFromCode($cods);
                $flight_to[$cods] = $airport_name;
              }
            }
          } else {
             if (!array_key_exists($codes, $flight_to)) {
               $cods = strtoupper($codes);
               $airport_name = $this->aaiGetAirportNameFromCode($cods);
               $flight_to[$cods] = $airport_name;
             } 
            }
        }
      }
    }
  return $flight_to;
  }
  
  
  
  /**
   * Functiom to return list of airports to which flight service is
   * available from the airport
   *
   * @param String
   *   Airport 3 character code
   * @param String
   *   Flight tyep i.e arrival or departure
   *
   * @return Associative Array
   *   array list of airport codes and name to/from which flight is available.
   */
  public function aaiGetAirlinesServedList($airport_code, $type = '') {
    $database = DB::dbInstance();

    $airport_code = strtoupper($airport_code);
    $flight_to = array();
    // get airports list for the airport, choose all airport codes from last 14 days
    // so we get all posible airport codes to which flights are available
    $tbl = 'aai_fids_data_log';
    $condition_flds = array(
      'LOCAL_AIRPORT' => $airport_code,
      'SCHED_DATE' => array("DATE_SUB(NOW(), INTERVAL 2 WEEK)", '>=', 'where'),
    );

    $flds_to_select = array('SOURCE_DESTINATION', 'AIRLINE_CODE');
    $airports_rs = $database->dbConditionalSelect($tbl, $condition_flds, $flds_to_select);
    
    if (is_array($airports_rs)) {
      // get array holding all airport names
      $aai_airport_codes = unserialize(variable_get('airport_codes'));
      foreach ($airports_rs as $value) {
        $flight_num[] = $value['AIRLINE_CODE'];
      }
    }

    $flight_num = array_unique($flight_num);

    return $flight_num;
  }


  /**
   * Functiom to return Twitter System setting Form fields
   * available from the airport code
   *
   * @param String
   *   Airport 3 character code
   *
   * @return Form with twitter configuration Fields with Airport code
   *   
   */
  public function aai_twitter_system_setting_form() { //out($_SESSION); die('functionnnnnn');
    $airport = strtolower($_SESSION['aai_api_airportcode']);
    $api_for = $_SESSION['api_for'];
    print_r($_SESSION);
    unset($_SESSION['aai_api_airportcode']);
    unset($_SESSION['api_for']);

    if ($airport != '') {
      // fields for Twitter API Confugrations
      $form['aai_'.$airport.'_twitter_oauth_access_token'] = array(
        '#type' => 'textfield',
        '#title' => t('Access Token'),
        '#description' => t('Please Provide Oauth Access Token.'),
        '#size' => 40,
        '#default_value' => variable_get('aai_'.$airport.'_twitter_oauth_access_token', FALSE),
        '#required' => TRUE,
      );
      $form['aai_'.$airport.'_twitter_oauth_access_token_secret'] = array(
        '#type' => 'textfield',
        '#title' => t('Access Token Secret'),
        '#description' => t('Please Provide Oauth Access Token Secret.'),
        '#size' => 40,
        '#default_value' => variable_get('aai_'.$airport.'_twitter_oauth_access_token_secret', FALSE),
        '#required' => TRUE,
      );
      $form['aai_'.$airport.'_twitter_consumer_key'] = array(
        '#type' => 'textfield',
        '#title' => t('Consumer Key'),
        '#description' => t('Please Provide Consumer Key.'),
        '#size' => 40,
        '#default_value' => variable_get('aai_'.$airport.'_twitter_consumer_key', FALSE),
        '#required' => TRUE,
      );
      $form['aai_'.$airport.'_twitter_consumer_secret'] = array(
        '#type' => 'textfield',
        '#title' => t('Consumer Secret'),
        '#description' => t('Please Provide Consumer Secret.'),
        '#size' => 40,
        '#default_value' => variable_get('aai_'.$airport.'_twitter_consumer_secret', FALSE),
        '#required' => TRUE,
      );

      $form['#submit'] = array('submit_social_api_form');
      return system_settings_form($form);

      //return $form;
    } else { die('elseee');
      drupal_goto("/administer/api/$api_for");
    }
  }

  //public function aai_twitter_system_setting_form_submit($form, &$form_state) {die('submittt');}

  /**
   * Functiom to return Twitter URL System setting Form fields
   * available from the airport code
   *
   * @param 
   *   $baseURI BaseURI of twitter Usertimeline
   *   method : POST
   *
   * @return Complete URL which will access in twitter API
   *   
   */
  public function aai_buildBaseString($baseURI, $method, $params) {
    $r = array();
    ksort($params);
    foreach($params as $key=>$value){ 
       $r[] = "$key=" . rawurlencode($value);
    }
        return $method."&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
  }

  /**
   * Functiom to return Valid Authentication
   * available from the Key
   *
   * @param 
   *   $oauth oauth check the key
   *
   * @return Key is valid or Not
   *   
   */
  public function aai_buildAuthorizationHeader($oauth) {
    $r = 'Authorization: OAuth ';
    $values = array();
    foreach($oauth as $key=>$value)
      $values[] = "$key=\"" . rawurlencode($value) . "\"";
      $r .= implode(', ', $values);
      return $r;
  }

  /**
   * function to return timeinterval().
   */
  public function timeago($date) {
    $timestamp = strtotime($date); 
    $strTime = array("sec", "min", "hr", "day", "month", "year");
    $length = array("60","60","24","30","12","10");
    $currentnewTime = date('Y-m-d H:i:s');
    $currentTime = strtotime($currentnewTime);
    if($currentTime >= $timestamp) {
      $diff     = $currentTime- $timestamp;
      for($i = 0; $diff >= $length[$i] && $i < count($length)-1; $i++) {
        $diff = $diff / $length[$i];
      }
      $diff = round($diff);
      return $diff . " " . $strTime[$i] . "s ";
    }
  }

  /**
   * Function to return quick navigations for Airports
   */
  public function aaiGetAirportsQuickNavigation() {
    global $base_url;
    $functions = AAI::getInstance();
    $lang = $functions->aaiCurrentLang();

    if (arg(0) == 'airports' && arg(2)) {
      $airport_name = strtolower(arg(2)); 
    } else if (arg(0) == 'airports') {
      $airport_name = strtolower(arg(1));
    }

    $out = "<ul class='quickLinks'>
    <li>
      <a href='$base_url/$lang/airports/flights/$airport_name'>
        <span class='flight-icon'>" . t('Flight Search') . "</span>
      </a>
    </li>
     <li>
      <a href='$base_url/$lang/airports/passenger-info/$airport_name/Eat-&-Dine'>
        <span class='eat-icon'>" . t('Eat & Dine') . "</span>
      </a>
    </li>
    <li>
      <a href='$base_url/$lang/airports/transport/$airport_name'>
        <span class='transport-icon'>" . t('Transport') . "</span>
      </a>
    </li>
    <li>
      <a href='$base_url/$lang/airports/passenger-info/$airport_name/Conveniences'>
        <span class='duty-icon'>" . t('Conveniences') . "</span>
      </a>
    </li>
    <li>
      <a href='$base_url/$lang/airports/city-info/$airport_name/Tourist-Place'>
        <span class='tourest-icon'>" . t('Tourist Place') . "</span>
      </a>
    </li>
    <li>
     <a href='$base_url/$lang/airports/city-info/$airport_name/Hotels'>
        <span class='hotel-icon'>" . t('Hotels') . "</span>
      </a>
    </li>
    <li>
      <a href='$base_url/$lang/airports/achievement/$airport_name'>
        <span class='awards-icon'>" . t('Achievement') . "</span>
      </a>
    </li>
    <li>
      <a href='$base_url/$lang/airports/image-gallery/$airport_name'>
        <span class='media-icon'>" . t('Media Center') . "</span>
      </a>
    </li>
    <li>
      <a href='$base_url/$lang/airports/passenger-info/faq/$airport_name'>
        <span class='faq-icon'>" . t('Faq') . "</span>
      </a>
    </li>
    </ul>";

    return $out;
  }

  /**
   * Function to get weather details for airport
   *
   * @param String
   *   Airport name
   *
   * @return json
   *   Json decoded weather data
   */
  public function aaiGetAirportWeather($airport_name) {
    $airport_name = ucwords($airport_name);
    $airport_iata = $this->aaiGetAirportTermDetails($airport_name, array('field_airport_code'));
    $airport_iata = 'JAI'; //strtoupper($airport_iata['field_airport_code']);

    // check if airport weather detail exists in cache
    $cache_weather_data = cache_get('aaiAirportWeather:'.$airport_iata);

    // if data is not cached or expires
    if (!$cache_weather_data || (time() > $cache_weather_data->expire)) {
      // get airport LAT LONG
      $aai_airports = unserialize(variable_get('airport_codes'));
      $current_airport_data = $aai_airports[$airport_iata];
      $airport_lat = $current_airport_data['lat'];
      $airport_lng = $current_airport_data['lng'];

      $weather_api_key = variable_get('weather_api_key');
      $weather_url = variable_get('weather_api_url');

      $url = $weather_url . "/lat=$airport_lat&lon=$airport_lng&APPID=$weather_api_key&units=metric";
      $connect = curl_init();
      // Set URL and other appropriate options.
      curl_setopt($connect, CURLOPT_URL, $url);
      curl_setopt($connect, CURLOPT_RETURNTRANSFER, TRUE);
      // Grab URL and pass it to the browser.
      $result_json = curl_exec($connect);
      $weather_data = json_decode($result_json);
      
      // temporary weather data
      $weather_data = json_decode('{"coord":{"lon":77.22,"lat":28.63},"weather":[{"id":741,"main":"Fog","description":"fog","icon":"50d"}],"base":"stations","main":{"temp":17.62,"pressure":1020,"humidity":93,"temp_min":15,"temp_max":20},"visibility":350,"wind":{"speed":1.5,"deg":270},"clouds":{"all":0},"dt":1480656600,"sys":{"type":1,"id":7809,"message":0.0062,"country":"IN","sunrise":1480642048,"sunset":1480679628},"id":1273840,"name":"Connaught Place","cod":200}');

      // set expiry time for cache
      $expire = time() + (60*60*2);

      // cache airport weather data
      cache_set('aaiAirportWeather:'.$airport_iata, $weather_data, 'cache', $expire);

      // watchdog entry
      $this->aaiWatchdog("New weather data cache set for $airport_name | $airport_iata", WATCHDOG_INFO);
      return $weather_data;
    }

    return $cache_weather_data;
  }

  /**
   * Function to send notification to user about expiring documents.
   */
  public function aaiSendDocumentExpiryNotification() {
    $db = DB::dbInstance();

    // check when was it executed last
    $last_executed_at = variable_get('aai_notifications_sent_on');
    if (!$last_executed_at) {
      $last_executed_at = $time = (time() - (60*60*24));
      variable_set('aai_notifications_sent_on', $time);
    }

    // notification process to execute only once in 24 hours
    $time_elapsed = time() - $last_executed_at;
    $_24_hrs = (60*60*24);
    if ($time_elapsed < $_24_hrs) {
      return 0;
    }

    // Get number of days, before which expiry notification is to be sent
    $date = time();
    $advance_notice_days = variable_get('num_days'); //variable_get('days_for_advance_notice');
    $advance_notice_days = "+" . $advance_notice_days . " day";
 
    $date_timestamp = strtotime($advance_notice_days, $date);
    $advance_date = date('Y-m-d', $date_timestamp);
    $advance_date = $advance_date . " 00:00:00";
 
    // get list of nodes/documents that are going to expire 
    $tbl = 'field_data_field_document_date';
    $flds_to_select = array('entity_id');
    $condition_flds = array(
      'field_document_date_value2' => array("$advance_date", '<='),
    );
    $joins[] = array(
      'join' => 'leftjoin',
      'tbl' => 'node',
      'alias' => 'n',
      'on' => "n.nid = tbl.entity_id",
      'fields' => array('uid'),
      'condition' => array('n.status' => 1),
    );
    $joins[] = array(
      'join' => 'leftjoin',
      'tbl' => 'users',
      'alias' => 'u',
      'on' => "u.uid = n.uid",
      'fields' => array('mail'),
    );
    $rs = $db->dbSelectWithJoin($tbl, $joins, $condition_flds, $flds_to_select);

    // get total number of notification that can be send to user
    $notification_limit = variable_get('notification_num');

    $nodes_expiring_soon = array();
    while($obj = $rs->fetchObject()) {
      $nid = $obj->entity_id;
      $user_id = $obj->uid;
      $user_mail = $obj->mail;

      // check if notification already sent for the node and user pair
      $query_anl = db_select('aai_notification_log', 'anl');
      $query_anl->fields('anl', array('notification_num', 'id', 'sent_on'));
      $query_anl->condition('uid', $user_id);
      $query_anl->condition('nid', $nid);
      $result_anl = $query_anl->execute();
      $rowCount = $result_anl->rowCount();

      //if notification already sent
      if($rowCount > 0){
        while($logs = $result_anl->fetchAssoc()) {  
          $num_of_notice_sent = $logs['notification_num'];
          $notice_id = $logs['id'];
          $notice_updated_on = $logs['sent_on'];

          // check if notice limit exceeded
          if($num_of_notice_sent <= $notification_limit) {
            $nodes_expiring_soon[$user_id][] = array(
              'nid' => $nid,
              'notice_id' => $notice_id,
              'notice_count' => $num_of_notice_sent,
              'usr_mail' => $user_mail,
              'updated_on' => $notice_updated_on,
            );
          }
        }
      } else {
        $nodes_expiring_soon[$user_id][] = array(
          'nid' => $nid,
          'usr_mail' => $user_mail,
        );
      }
    }

    // loop through expiring nodes and send notification mails
    foreach ($nodes_expiring_soon as $usr_id => $value) {
      // get all nodes for the current user
      $user_nodes = array();
      $usr_mail = '';
      $notice_id = 0;
      $notice_count = 0;
      $notice_updated_on = 0;

      // array variable to hold data for notification log entry
      $notice_ary = array();

      foreach ($value as $tmp_ary) {
        $user_nodes[] = $tmp_ary['nid'];
        $usr_mail = $tmp_ary['usr_mail'];
        $notice_ary[$nid][] = $usr_id;
        // check if notifications already sent
        if ($tmp_ary['notice_id']) {
          $notice_id = $tmp_ary['notice_id'];
          $notice_count = $tmp_ary['notice_count'];
          $notice_updated_on = $tmp_ary['updated_on'];
          
          $notice_ary[$nid][] = $notice_id;
          $notice_ary[$nid][] = $notice_count;
          $notice_ary[$nid][] = $notice_updated_on;
        }
      }
      
      // send notification mail to user
      $mail_body = $this->aaiGetExpiringNodeEmailContent($user_nodes);
      $mail_sent = $this->aaiNotificationMail($usr_mail, $mail_body);

      if ($mail_sent) {
        $now = time();

        // do notification log entry for the user
        foreach($notice_ary as $k => $v) {
          $notice_nid = $k;
          $notice_uid = $v[0];

          // check if notice sent earlier
          if ($v[1]) {
            // update existing log entry notice counter
            $tmp = $v[2] + 1;
            $prev_updated_on_val = $v[3] . ',' . $now;
            $row_id = $v[1];
            $flds = array(
              'notification_num' => $tmp,
              'updated_on' => $prev_updated_on_val,
            );
            $cnd_flds = array(
              'id' => $row_id,
            );
            $db->dbUpdateQuery('aai_notification_log', $flds, $cnd_flds);
          } else {
            // Its first notice, do entry into notification log table
            $flds = array (
              'nid' => $notice_nid,
              'notification_num' => 1,
              'uid' => $notice_uid,
              'sent_on' => $now,
              'updated_on' => $now,
            );
            $db->dbInsertQuery('aai_notification_log', $flds);
          }
        }
      }
    }
    // update cron time for notification execution
    $now = time();
    variable_set('aai_notifications_sent_on', $now);
  }

  /**
   * Function to create Email Content.
   */
  function aaiGetExpiringNodeEmailContent($node_array) {
    $subject = "Reminder Email Notification (AAI)";
    $email_content = "Dear User,<BR>Following Content Are expiring Soon<BR><BR><ul>";

    foreach ($node_array as $value) {
      $nodeInf = node_load($value);
      $options = array('absolute' => TRUE);
      $url = url('node/' . $nodeInf->nid, $options); 
      $email_content .= "<li><a href=".$url.">".$nodeInf->title."</a></li>";
      $node_array[] = $nodeInf->nid;
    }

    $email_content .= "</ul><BR>Thanks<BR>AAI TEAM"; 
    return array('subject' => $subject, 'body' => $email_content, 'nodes' => $node_array);
  }


  /**
   * Function to send email notification.
   */
  public function aaiNotificationMail($to, $body = array()) {
    $bdy = nl2br($body['body']);
    $sub = $body['subject'];
    $params = array(
      'body' => $body,
      'subject' => $sub,
    );
    $from = 'support@aai.aero';
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers.="From: ".$from."\r\n";

    $sent = mail($to, $sub, $bdy, $headers);
    
    // $sent = drupal_mail('airseva', 'notification', $to, language_default(), $params, $from, TRUE);

    if ($sent) {
      $this->aaiWatchdog("Email Notification sent to $to.", WATCHDOG_INFO);
      return 1;
    } else {
      $this->aaiWatchdog("Problem sending email notification to $to.", WATCHDOG_ERROR);
      return 0;
    }
  }

  /**
   * Function to get FIDS data & update records
   */
  public function aaiFetchFIDS() {
    $db = DB::dbInstance();

    // check current active table for fids data
    $fids_tbl = variable_get('aai_fids_active_table');
    if (!$fids_tbl) {
      variable_set('aai_fids_active_table', 'aai_fids_data_1');
      $fids_tbl = 'aai_fids_data_1';
    }

    // get query batch size
    $batch_size = AAI_QRY_BATCH_SIZE;

    // update table to change date values to NULL
    $date_fld_ary = array('EST_DATE', 'ACT_DATE', 'SCHED_DATE');
    foreach ($date_fld_ary as $field) {
      $flds = array($field => NULL);
      $cnd_flds = array($field => '0000-00-00');
      $db->dbUpdateQuery($fids_tbl, $flds, $cnd_flds);
    }

    // GENERATE LOG ENTRIES FOR FIDS DATA
    $tbl = $fids_tbl;
    $condition_flds = array(
      'FLIGHT_STATUS' => array(array(2, 3, 9), 'IN'),
    );
    $flds_to_select = array(
      'LOCAL_AIRPORT',
      'ARRIVAL_DEPARTURE_FLAG',
      'INTERNATIONAL_DOMESTIC_FLAG',
      'AIRLINE_CODE',
      'FLIGHT_NUMBER',
      'SOURCE_DESTINATION',
      'VIA',
      'VIA1',
      'SCHED_DATE',
      'SCHED_TIME',
      'EST_DATE',
      'EST_TIME',
      'ACT_DATE',
      'ACT_TIME',
      'FLIGHT_STATUS',
      'TERMINAL',
      'GATES',
      'COUNTERS',
      'BELT_NUMBER',
      'DATEVAL',
    );
    $rs = $db->dbConditionalSelect($tbl, $condition_flds, $flds_to_select);

    // get values to be inserted into log table
    $log_tbl = 'aai_fids_data_log';
    $values = array();
    $total_rec = count($rs);

    $full_batch_cycles = 0;
    $remaining_rec = $total_rec % $batch_size;
    if ($total_rec > $batch_size) {
      $full_batch_cycles = floor($total_rec / $batch_size);
    }

    $full_counter = 0;
    if ($full_batch_cycles) {
      for ($batch_cycle = 1; $batch_cycle <= $full_batch_cycles; $batch_cycle++) {
        for ($i = 0; $i < $batch_size; $i++) {
          $v = $rs[$full_counter];
          $values[] = $v;
          $full_counter++;
          // if we have fetched batch size records then do record entry into log table
          if ($i == ($batch_size - 1)) {
            $db->dbInsertMultiple($log_tbl, $flds_to_select, $values);
            $values = array();
          }
        }
      }  
      // do entry for the remaining records
      for (; $full_counter < $total_rec; ) {
        $v = $rs[$full_counter++];
        $values[] = $v;
      }
      $db->dbInsertMultiple($log_tbl, $flds_to_select, $values);
    } else {
      for (; $full_counter < $total_rec; ) {
        $v = $rs[$full_counter++];
        $values[] = $v;
      }
      $db->dbInsertMultiple($log_tbl, $flds_to_select, $values);
    }

    // FETCH FRESH FIDS DATA FROM API's and store into database
    $api_errors_counter = $this->aaiFetchFidsData();

    if ($api_errors_counter < 4) {
      // update current FIDS table
      if ($fids_tbl == 'aai_fids_data_1') {
        $new_table = 'aai_fids_data_2';
      } else {
        $new_table = 'aai_fids_data_1';
      }
      variable_set('aai_fids_active_table', $new_table);
      sleep(15);

      // Flush old data table
      $db->dbTruncateQuery($fids_tbl);
      $this->aaiWatchdog('FIDS DATA FETCHED SUCCESSFULLY', WATCHDOG_INFO);
    }
  }

  /**
   * Function to get and store fids data into respective table
   */
  private function aaiFetchFidsData() {
    $db = DB::dbInstance();

    // include nusoap library
    $lib_pth = drupal_get_path('module', 'fids') . '/lib/nusoap.php';
    require_once($lib_pth);

    // get query batch size
    $batch_size = AAI_QRY_BATCH_SIZE;

    // fields for which data is fetched
    $fids_fields = array(
      'LOCAL_AIRPORT',
      'ARRIVAL_DEPARTURE_FLAG',
      'INTERNATIONAL_DOMESTIC_FLAG',
      'AIRLINE_CODE',
      'FLIGHT_NUMBER',
      'SOURCE_DESTINATION',
      'VIA',
      'VIA1',
      'SCHED_DATE',
      'SCHED_TIME',
      'EST_DATE',
      'EST_TIME',
      'ACT_DATE',
      'ACT_TIME',
      'FLIGHT_STATUS',
      'TERMINAL',
      'GATES',
      'COUNTERS',
      'BELT_NUMBER',
    );

    // get table for fids data entry
    $fids_tbl = variable_get('aai_fids_active_table');
    if ($fids_tbl == 'aai_fids_data_1') {
      $blank_fids_tbl = 'aai_fids_data_2';
    } else {
      $blank_fids_tbl = 'aai_fids_data_1';
    }
    // truncate secondary table
    $db->dbTruncateQuery($blank_fids_tbl);

    // fields for data entry
    $flds_to_select = $fids_fields;
    $flds_to_select[] = 'DATEVAL';

    // get all url to get FIDS data from
    $fids_api_urls = unserialize(variable_get('fids_xml_feeds'));

    // set parameters for SOAP call
    $proxyhost = isset($_POST['proxyhost']) ? $_POST['proxyhost'] : '';
    $proxyport = isset($_POST['proxyport']) ? $_POST['proxyport'] : '';
    $proxyusername = isset($_POST['proxyusername']) ? $_POST['proxyusername'] : '';
    $proxypassword = isset($_POST['proxypassword']) ? $_POST['proxypassword'] : '';

    // loop through all API's to fetch data
    $api_errors_counter = 0;
    foreach($fids_api_urls as $item) {
      $url = $item['feed_url'];
      $operation = $item['callback'];
      $res_type = $item['res_type'];

      // variable to check if data received from the API
      $data_not_fetched = 1;
      $data_available = 0;
      // variable to ascertain if secondary link used to get data
      $use_secondary_api_link = 0;

      do {
        // if 3 attempts to fetch data fails, try with alternate url if
        // available else send a mail to the admin about the problem
        // & continue with other API in list
        if ($data_not_fetched > 3) {
          if (isset($item['feed_url_sec']) && !$use_secondary_api_link) {
            $use_secondary_api_link = 1;
            $url = $item['feed_url_sec'];
            // reset counter for secondary api, to have 3 attempts
            $data_not_fetched = 1;
          } else {
            $airport = $item['name'];
            $msg = "FIDS API error, failed to fetch data for '$airport' airport.";
            if ($use_secondary_api_link) {
              $msg .= " Both from primary & secondary links.";
            }
            // send mail to aai-admin
            $this->aaiWatchdog($msg, WATCHDOG_ERROR);
            $data_not_fetched = 0;
            // track the api call failures
            $api_errors_counter++;
            break;
          }
        }
        $client = new nusoap_client($url, 'wsdl', $proxyhost, $proxyport, $proxyusername, $proxypassword);
        // set content encoding
        $client->soap_defencoding = 'UTF-8';

        $err = $client->getError();
        if ($err) {
          // iff error occurs, do watchdog entry and skip current iteration
          $this->aaiWatchdog("Constructor error, fetching details from $url " . $err, WATCHDOG_ERROR);
        }

        // Doc/lit parameters get wrapped
        $param = array('Symbol' => 'IBM');
        $result = $client->call($operation, array('parameters' => $param), '', '', false, true);

        // Check for a fault
        if ($client->fault) {
          $this->aaiWatchdog("Constructor error, fetching details from $url ". $result, WATCHDOG_ERROR);
        } else {
          // Check for errors
          $err = $client->getError();
          if ($err) {
            $this->aaiWatchdog("Constructor error, fetching details from $url ". $err, WATCHDOG_ERROR);
          } else {
            // Fetch the result  
            switch ($res_type) {
              case 1:
                $fids_data = $result['return'];
                $fids_data = (array) simplexml_load_string($fids_data);
                $fids_data = $fids_data['Detail_Movements'];
                break;
              case 2:
                $fids_data = $result['FlightFIDSResult']['NewDataSet']['Detail_Movements'];
                break;
            }
            $data_not_fetched = 0;
            $data_available = 1;
          }
        }

        // check if data retrieved from API
        if ($data_not_fetched) {
          $data_not_fetched++;
        }
      } while ($data_not_fetched);

      // process only if data is fetched
      if ($data_available) {
        $total_rec = count($fids_data);

        $full_batch_cycles = 0;
        $remaining_rec = $total_rec % $batch_size;
        if ($total_rec > $batch_size) {
          $full_batch_cycles = floor($total_rec / $batch_size);
        }

        $full_counter = 0;
        $values = array();
        if ($full_batch_cycles) {
          for ($batch_cycle = 1; $batch_cycle <= $full_batch_cycles; $batch_cycle++) {
            for ($i = 0; $i < $batch_size; $i++) {
              $v = $fids_data[$full_counter];
              if (is_array($v)) {
                $v = (object) $v;
              }

              foreach ($fids_fields as $fld) {
                if ($fld == 'EST_DATE' || $fld == 'ACT_DATE') {
                  $tmp_dt = $v->$fld;
                  if ($tmp_dt == '') {
                    $val[$fld] = NULL;
                  } else {
                    $val[$fld] = $tmp_dt;
                  }
                } else if ($fld == 'EST_TIME' || $fld == 'ACT_TIME' || $fld == 'FLIGHT_STATUS') {
                  $tmp_time = $v->$fld;
                  if ($tmp_time == '') {
                    $val[$fld] = 0;
                  } else {
                    $val[$fld] = $tmp_time;
                  }
                } else if ($fld == 'FLIGHT_NUMBER') {
                  $tmp = $v->$fld;
                  if (empty($tmp)) {
                    $val[$fld] = 0;
                  } else {
                    $val[$fld] = $tmp;
                  }
                } else {
                  $val[$fld] = $v->$fld;
                }
              }
              $val['DATEVAL'] = time();
              $values[] = $val;
              $full_counter++;
              // if we have fetched batch size records then do record entry into log table
              if ($i == ($batch_size - 1)) {
                $db->dbInsertMultiple($blank_fids_tbl, $flds_to_select, $values);
                $values = array();
              }
            }
          }
          // do entry for the remaining records
          for (; $full_counter < $total_rec; ) {
            $v = $fids_data[$full_counter++];
            if (is_array($v)) {
              $v = (object) $v;
            }

            foreach ($fids_fields as $fld) {
              if ($fld == 'EST_DATE' || $fld == 'ACT_DATE') {
                $tmp_dt = $v->$fld;
                if ($tmp_dt == '') {
                  $val[$fld] = NULL;
                } else {
                  $val[$fld] = $tmp_dt;
                }
              } else if ($fld == 'EST_TIME' || $fld == 'ACT_TIME' || $fld == 'FLIGHT_STATUS') {
                $tmp_time = $v->$fld;
                if ($tmp_time == '') {
                  $val[$fld] = 0;
                } else {
                  $val[$fld] = $tmp_time;
                }
              } else {
                $val[$fld] = $v->$fld;
              }
            }
            $val['DATEVAL'] = time();
            $values[] = $val;
          }
          $db->dbInsertMultiple($blank_fids_tbl, $flds_to_select, $values);
        } else {
          $values = array();
          for (; $full_counter < $total_rec; ) {
            $v = $fids_data[$full_counter++];
            if (is_array($v)) {
              $v = (object) $v;
            }

            foreach ($fids_fields as $fld) {
              if ($fld == 'EST_DATE' || $fld == 'ACT_DATE') {
                $tmp_dt = $v->$fld;
                if ($tmp_dt == '') {
                  $val[$fld] = NULL;
                } else {
                  $val[$fld] = $tmp_dt;
                }
              } else if ($fld == 'EST_TIME' || $fld == 'ACT_TIME' || $fld == 'FLIGHT_STATUS') {
                $tmp_time = $v->$fld;
                if ($tmp_time == '') {
                  $val[$fld] = 0;
                } else {
                  $val[$fld] = $tmp_time;
                }
              } else {
                $val[$fld] = $v->$fld;
              }
            }
            $val['DATEVAL'] = time();
            $values[] = $val;
          }
          $db->dbInsertMultiple($blank_fids_tbl, $flds_to_select, $values);
        }
      }
    }

    return $api_errors_counter;
  }
  
 
  /**
   * Get Assigned User for a particular airport.
   */
  public function aaiGetUsertoAssign($user_type, $airport_id = 0, $department = 0, $current_user_obj) {
    Global $user;
    $usercurrent = $current_user_obj;
    $lang = $this->aaiCurrentLang();
    
    //case Corporate
    if($user_type == "corporate_creator") {
      $dept_tid = $usercurrent->field_department['en'][0]['tid'];
      // get publisher of that region
      $tbl = 'field_data_field_department';
      $condition_flds = array(
        'field_department_tid' => $dept_tid,
        'bundle' => 'user',
      );
      $flds_to_select = array('entity_id');
      $rs = $db->dbConditionalSelect($tbl, $condition_flds, $flds_to_select);
      $current_uid = 0;
      if (count($rs)) {
        // Get the role of user $rs[0]['entity_id'] after then if user is
        // publisher then assign this node to him 
        // if there is not any such user then assign to regional publisher
        // If Regional publisher is also not available assign to corporate publisher
        foreach ($rs as $value) {
          $userId = $value;
          $userInfo =  user_load($userId);

          if(in_array('CHQ Publisher', $userInfo->roles)) {
            $current_uid = $userId;
          }

        }
      }
      $user_array['creator'] = $user->uid;
      $user_array['publisher'] = $current_uid;
      
    } else if($user_type == "airport_creator") {
      $region_tid = $usercurrent->field_region['en'][0]['tid'];
      // get publisher of that region  
      $tbl = 'field_data_field_region';
      $condition_flds = array(
        'field_region_tid' => $region_tid,
        'bundle' => 'user',
      );
      $flds_to_select = array('entity_id');
      $rs = $db->dbConditionalSelect($tbl, $condition_flds, $flds_to_select);
      $current_uid = 0;
      if (count($rs)) {
        // Get the role of user $rs[0]['entity_id'] after then if user is
        // publisher then assign this node to him 
        // if there is not any such user then assign to regional publisher
        // If Regional publisher is also not available assign to corporate publisher
        foreach ($rs as $value) {
          $userId = $value;
          $userInfo =  user_load($userId);
          if(in_array('Airport Publisher', $userInfo->roles)) {
            $current_uid = $userId;
          }
        }
      }
      if ($current_uid == 0) {
        $tbl2 = 'field_data_field_region';
        $condition_flds2 = array(
          'field_region_tid' => $region_tid,
          'bundle' => 'user',
        );
        $flds_to_select = array('entity_id');
        $rs2 = $db->dbConditionalSelect($tbl2, $condition_flds2, $flds_to_select);
        if (count($rs2)) {
          // Get the role of user $rs[0]['entity_id'] after then if user is
          // publisher then assign this node to him 
          // if there is not any such user then assign to regional publisher
          // If Regional publisher is also not available assign to corporate publisher
          foreach ($rs2 as $value) {
            $userId = $value;
            $userInfo =  user_load($userId);
            if(in_array('Regional Publisher', $userInfo->roles)) {
              $current_uid = $userId;
            }
          }
        }
        if ($current_uid == 0) {
          $tbl3 = 'users';
          $condition_flds3 = array();
          $flds_to_select = array('uid');
          $rs3 = $db->dbConditionalSelect($tbl3, $condition_flds3, $flds_to_select);
          if (count($rs3)) {
            // Get  the role of user $rs[0]['entity_id'] after then if
            // user is publisher then assign this node to him 
            // if there is not any such user then assign to regional publisher
            // If Regional publisher is also not available assign to corporate publisher
            foreach ($rs3 as $value) {
              $userId = $value;
              $userInfo =  user_load($userId);
              if(in_array('Corporate Publisher', $userInfo->roles)) {
                $current_uid = $userId;
              }
            }
          } 
        } 
      }
      $user_array['creator'] = $user->uid;
      $user_array['publisher'] = $current_uid; 

    } else if($user_type == "emp_creator") {
      $user_dept_tid = $usercurrent->field_department['en'][0]['tid'];

      // get publisher of the department  
      $tbl = 'field_data_field_department';
      $condition_flds = array(
        'field_department_tid' => $user_dept_tid,
        'bundle' => 'user',
      );
      $flds_to_select = array('entity_id');
      $rs = $db->dbConditionalSelect($tbl, $condition_flds, $flds_to_select);
      $current_uid = 0;
      if (count($rs)) {
        // Get the role of user $rs[0]['entity_id'] after then if user is 
        //publisher then assign this node to him 
        // if there is not any such user then assign to regional publisher
        // If Regional publisher is also not available assign to corporate publisher
        foreach ($rs as $value) {
           $userId = $value;
           $userInfo =  user_load($userId);
           if(in_array('Corporate Publisher', $userInfo->roles)) {
            $current_uid = $userId;
           }
        }
      }
      $user_array['creator'] = $user->uid;
      $user_array['publisher'] = $current_uid; 
    }

    return $user_array;
  }

  /**
   * function to return users to whom Corporate section node
   * will be assigned
   */
  public function aaiGetCorporateNodeAssignee($node, $user_type) {
    $db = DB::dbInstance();
    $lang = $this->aaiCurrentLang();

    $node_type = $node->type;

    if ($user_type == 'publisher') {
      $roles = array (
        'Publisher',
        'Regional Publisher',
        'CHQ Publisher',
      );
    } else if ($user_type == 'creator') {
      $roles = array (
        'Creator',
        'Regional Creator',
        'CHQ Creator',
      );
    }

    $valid_dept_tid = 0;

    switch ($node_type) {
      case 'aai_magazine':
      case 'achievements':
      case 'articles':
      case 'certification':
      case 'press_news_event':
        $dept_name = 'Public Relations';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        break;
      case 'csr_inactivities':
      case 'csr_policy':
      case 'csr_media_coverage':
        $dept_name = 'Planning';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        break;
      case 'careers':
      case 'current_openings':
        $dept_name = 'Human Resource';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        break;
      case 'cargo':
        $dept_name = 'Cargo';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        break;
      case 'corporate':
        $dept_name = 'Information Technology';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        break;
      case 'investors':
        $dept_name = 'Corporate Planning & Management Services';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        break;
      case 'rti':
        $dept_name = 'Corporate Affairs';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        break;
      case 'vigilance':
      case 'vigilance_integrity_club':
      case 'vigilance_photo_gallery':
        $dept_name = 'Vigilance';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        break;
      case 'resources':
      case 'tender':
      case 'services':
        // here node and user dept must match
        $valid_dept_tid = 0;
        $nid = $node->nid;

        // get node department
        $tbl = 'field_data_field_department';
        $condition_flds = array(
          'bundle' => $node_type,
          'entity_id' => $nid,
        );
        $flds_to_select = array(
          'field_department_tid',
        );
        $rs = $db->dbConditionalSelect($tbl, $condition_flds, $flds_to_select);
        if (count($rs)) {
          $valid_dept_tid = $rs[0]['field_department_tid'];
        }
        break;
    }

    $uid = 0;
    $user_found = 0;
    $index = 0;

    do {
      $role_to_select = $roles[$index];

      // initialize joins array
      $joins = array();

      // get user with respective 'Publisher' role for the node
      $tbl = 'users';
      $conditions = array (
        'status' => 1,
      );
      $flds_to_select = array('uid');
      if ($index == 0 || $index == 1) {
        $joins[] = array(
          'join' => 'leftjoin',
          'tbl' => 'field_data_field_department',
          'alias' => 'fa',
          'on' => "fa.entity_id = tbl.uid",
          'condition' => array('fa.field_department_tid' => $valid_dept_tid, 'fa.bundle' => 'user'),
        );
      }
      $joins[] = array(
        'join' => 'leftjoin',
        'tbl' => 'users_roles',
        'alias' => 'ur',
        'on' => "ur.uid = tbl.uid",
      );
      $joins[] = array(
        'join' => 'leftjoin',
        'tbl' => 'role',
        'alias' => 'role',
        'on' => "role.rid = ur.rid",
        'condition' => array('role.name' => "$role_to_select"),
      );
      $rs = $db->dbSelectWithJoin($tbl, $joins, $conditions, $flds_to_select);

      $user_exists = $rs->rowCount();
      if ($user_exists) {
        $obj = $rs->fetchObject();
        $uid = $obj->uid;
        $user_found = 1;
      }

      $index++;
      // just in case we forgot to add 'CHQ Publisher'
      if ($index >= 3) {
        $user_found = 1;
      }
    } while (!$user_found);

    return $uid;
  }

  /**
   * function to return users to whom Employee section node
   * will be assigned
   */
  public function aaiGetEmpNodeAssignee($node, $user_type) {
    $db = DB::dbInstance();
    $lang = $this->aaiCurrentLang();

    if ($user_type == 'publisher') {
      $roles = array (
        'Emp Publisher',
        'Regional Emp Publisher',
        'CHQ Publisher',
      );
    } else if ($user_type == 'creator') {
      $roles = array (
        'Emp Creator',
        'Regional Emp Creator',
        'CHQ Creator',
      );
    }

    $uid = 0;
    $user_found = 0;
    $index = 0;

    do {
      $role_to_select = $roles[$index];

      // initialize joins array
      $joins = array();

      // get user with respective 'Publisher' role for the node
      $tbl = 'users';
      $conditions = array (
        'status' => 1,
      );
      $flds_to_select = array('uid');
      if ($index == 0 || $index == 1) {
        if (isset($node->field_department[$lang])) {
          $node_dept = $node->field_department[$lang][0]['tid'];
        } else {
          $node_dept = $node->field_department[LANGUAGE_NONE][0]['tid'];
        }
        if (!isset($node_dept)) {
          if (isset($node->field_info_department[$lang])) {
            $node_dept = $node->field_info_department[$lang][0]['tid'];
          } else {
            $node_dept = $node->field_info_department[LANGUAGE_NONE][0]['tid'];
          }
        }
        
        $joins[] = array(
          'join' => 'leftjoin',
          'tbl' => 'field_data_field_department',
          'alias' => 'fa',
          'on' => "fa.entity_id = tbl.uid",
          'condition' => array('fa.field_department_tid' => $node_dept, 'fa.bundle' => 'user'),
        );
      }
      $joins[] = array(
        'join' => 'leftjoin',
        'tbl' => 'users_roles',
        'alias' => 'ur',
        'on' => "ur.uid = tbl.uid",
      );
      $joins[] = array(
        'join' => 'leftjoin',
        'tbl' => 'role',
        'alias' => 'role',
        'on' => "role.rid = ur.rid",
        'condition' => array('role.name' => $role_to_select),
      );
      $rs = $db->dbSelectWithJoin($tbl, $joins, $conditions, $flds_to_select);

      $user_exists = $rs->rowCount();
      if ($user_exists) {
        $obj = $rs->fetchObject();
        $uid = $obj->uid;
        $user_found = 1;
      }

      $index++;
      // just in case we forgot to add 'CHQ Publisher'
      if ($index >= 3) {
        $user_found = 1;
      }
    } while (!$user_found);

    return $uid;
  }

  /**
   * function to return users to whom airport's node will be assigned to
   */
  public function aaiGetAirpotNodeAssignee($airport_node, $user_type) {
    $db = DB::dbInstance();
    $lang = $this->aaiCurrentLang();

    if ($user_type == 'publisher') {
      $roles = array (
        'Airport Publisher',
        'Regional Airport Publisher',
        'CHQ Publisher',
      );
    } else if ($user_type == 'creator') {
      $roles = array (
        'Airport Creator',
        'Regional Airport Creator',
        'CHQ Creator',
      );
    }
    
    $uid = 0;
    $user_found = 0;
    $index = 0;
    do {
      $role_to_select = $roles[$index];

      // initialize joins array
      $joins = array();

      // get user with 'Airport Publisher' role for the supplied airport
      $tbl = 'users';
      $conditions = array (
        'status' => 1,
      );
      $flds_to_select = array('uid');
      if ($index == 0) {
        $airport_tid = $airport_node->field_related_airport[$lang][0][tid];
        $joins[] = array(
          'join' => 'leftjoin',
          'tbl' => 'field_data_field_airport',
          'alias' => 'fa',
          'on' => "fa.entity_id = tbl.uid",
          'condition' => array('fa.field_airport_tid' => $airport_tid, 'fa.bundle' => 'user'),
        );
      } else if ($index == 1) {
        // regional creator/publisher
        $airport_tid = $airport_node->field_related_airport[$lang][0][tid];
        $airport_term = taxonomy_term_load($airport_tid);
        if ($airport_term->field_region[$lang]) {
          $airport_region = $airport_term->field_region[$lang][0]['tid'];
        } else {
          $airport_region = $airport_term->field_region[LANGUAGE_NONE][0]['tid'];
        }
        $joins[] = array(
          'join' => 'leftjoin',
          'tbl' => 'field_data_field_region',
          'alias' => 'fr',
          'on' => "fr.entity_id = tbl.uid",
          'condition' => array('fr.field_region_tid' => $airport_region, 'fr.bundle' => 'user'),
        );
      }
      $joins[] = array(
        'join' => 'leftjoin',
        'tbl' => 'users_roles',
        'alias' => 'ur',
        'on' => "ur.uid = tbl.uid",
      );
      $joins[] = array(
        'join' => 'leftjoin',
        'tbl' => 'role',
        'alias' => 'role',
        'on' => "role.rid = ur.rid",
        'condition' => array('role.name' => $role_to_select),
      );
      $rs = $db->dbSelectWithJoin($tbl, $joins, $conditions, $flds_to_select);

      $user_exists = $rs->rowCount();
      if ($user_exists) {
        $obj = $rs->fetchObject();
        $uid = $obj->uid;
        $user_found = 1;
      }

      $index++;
      // just in case we forgot to add 'CHQ Publisher'
      if ($index >= 3) {
        $user_found = 1;
      }
    } while (!$user_found);

    return $uid;
  }

  /**
   * function to check if valid user is adding/editing corporate content type
   */
  public function aaiCheckCorporateContentPermission($node, $current_user_obj) {
    $db = DB::dbInstance();
    $lang = $this->aaiCurrentLang();

    $node_type = $node->type;
    $authorized = 0;

    // get user department
    if ($current_user_obj->field_department[$lang]) {
      $current_user_dept = $current_user_obj->field_department[$lang][0]['tid'];
    } else {
      $current_user_dept = $current_user_obj->field_department['en'][0]['tid'];
    }    
    if (in_array('CHQ Creator', $current_user_roles)) {
      $authorized = 1;
    }

    switch ($node_type) {
      case 'aai_magazine':
      case 'achievements':
      case 'articles':
      case 'certification':
      case 'press_news_event':
        $dept_name = 'Public Relations';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        if ($current_user_dept == $valid_dept_tid) {
          $authorized = 1;
        }
        break;
      case 'csr_inactivities':
      case 'csr_policy':
      case 'csr_media_coverage':
        $dept_name = 'Planning';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        if ($current_user_dept == $valid_dept_tid) {
          $authorized = 1;
        }
        break;
      case 'careers':
      case 'current_openings':
        $dept_name = 'Human Resource';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        if ($current_user_dept == $valid_dept_tid) {
          $authorized = 1;
        }
        break;
      case 'cargo':
        $dept_name = 'Cargo';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        if ($current_user_dept == $valid_dept_tid) {
          $authorized = 1;
        }
        break;
      case 'corporate':
        $dept_name = 'Information Technology';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        if ($current_user_dept == $valid_dept_tid) {
          $authorized = 1;
        }
        break;
      case 'investors':
        $dept_name = 'Corporate Planning & Management Services';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        if ($current_user_dept == $valid_dept_tid) {
          $authorized = 1;
        }
        break;
      case 'rti':
        $dept_name = 'Corporate Affairs';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        if ($current_user_dept == $valid_dept_tid) {
          $authorized = 1;
        }
        break;
      case 'vigilance':
      case 'vigilance_integrity_club':
      case 'vigilance_photo_gallery':
        $dept_name = 'Vigilance';
        $valid_dept_tid = $this->aaiGetTermID($dept_name, 'Department');
        if ($current_user_dept == $valid_dept_tid) {
          $authorized = 1;
        }
        break;
      case 'resources':
      case 'services':
      if (isset($current_user_dept)) {
          $authorized = 1;
        }
      break;
      case 'tender':  
        // here node and user dept must match
        $node_dept_tid = 0;
        $nid = $node->nid;

        // get node department
        $tbl = 'field_data_field_department';
        $condition_flds = array(
          'bundle' => $node_type,
          'entity_id' => $nid,
        );
        $flds_to_select = array(
          'field_department_tid',
        );
        $rs = $db->dbConditionalSelect($tbl, $condition_flds, $flds_to_select);

        if (count($rs)) {
          $node_dept_tid = $rs[0]['field_department_tid'];
        } else {
          if (isset($node->field_department[$lang])) {
            $node_dept_tid = $node->field_department[$lang][0]['tid'];
          } else {
            $node_dept_tid = $node->field_department[LANGUAGE_NONE][0]['tid'];
          }
          if (!isset($node_dept_tid)) {
            if (isset($node->field_info_department[$lang])) {
              $node_dept_tid = $node->field_info_department[$lang][0]['tid'];
            } else {
              $node_dept_tid = $node->field_info_department[LANGUAGE_NONE][0]['tid'];
            }
          }
        }

        if ($node_dept_tid) {
          if ($current_user_dept == $node_dept_tid) {
            $authorized = 1;
          }
        }
        break;
    }

    return $authorized;
  }
  
  /**
   * Function to check if publisher can review airport node or not
   */
  public function aaiAirportPublisherModeration($nid, $type) {
    global $user;
    $lang = $this->aaiCurrentLang();

    // get current user role
    $current_user_uid = $user->uid;
    $current_user_obj = user_load($current_user_uid);
    $current_user_roles = $current_user_obj->roles;

    if((in_array('aai admin', $current_user_roles)) || (in_array('administrator', $current_user_roles)) || (in_array('CHQ Publisher', $current_user_roles))) {
      return FALSE;
    }

    $node = node_load($nid);
    $node_airport_tid = $node->field_related_airport[$lang][0][tid];

    if (in_array('Airport Publisher', $current_user_roles)) {
      // check if corporate publisher can access the current row
      // get user department
      if ($current_user_obj->field_airport[$lang]) {
        $current_user_airport = $current_user_obj->field_airport[$lang][0]['tid'];
      } else {
        $current_user_airport = $current_user_obj->field_airport['en'][0]['tid'];
      }

      if ($current_user_obj->field_airport[$lang]) {
        $current_user_airport = $current_user_obj->field_airport[$lang][0]['tid'];
      } else {
        $current_user_airport = $current_user_obj->field_airport['en'][0]['tid'];
      }
      
      if ($current_user_airport == $node_airport_tid) {
        return FALSE;
      } else {
        return TRUE;
      } 
    } else if (in_array('Regional Airport Publisher', $current_user_roles)) {
      // for regional creator node's 'airport' should be in user region
      $airport_term = taxonomy_term_load($node_airport_tid);
      if ($airport_term->field_region[$lang]) {
        $airport_region = $airport_term->field_region[$lang][0]['tid'];
      } else {
        $airport_region = $airport_term->field_region[LANGUAGE_NONE][0]['tid'];
      }

      // get current user region
      if ($current_user_obj->field_region[$lang]) {
        $current_user_region = $current_user_obj->field_region[$lang][0]['tid'];
      } else {
        $current_user_region = $current_user_obj->field_region['en'][0]['tid'];
      }

      // check if user and node region matches
      if ($current_user_region == $airport_region) {
        return FALSE;
      } else {
        return TRUE;
      } 
    }
    else {
      return TRUE;
    }
  }


 /**
   * Function to check if publisher can review airport node or not
   */
  public function aaiEmpPublisherModeration($nid, $type) {
    global $user;
    $lang = $this->aaiCurrentLang();

    // get current user role
    $current_user_uid = $user->uid;
    $current_user_obj = user_load($current_user_uid);
    $current_user_roles = $current_user_obj->roles;

    if((in_array('aai admin', $current_user_roles)) || (in_array('administrator', $current_user_roles)) || (in_array('CHQ Publisher', $current_user_roles))) {
      return FALSE;
    }

    $node = node_load($nid);
    $node_airport_tid = $node->field_related_airport[$lang][0][tid];

    if (in_array('Regional Emp Publisher', $current_user_roles) || in_array('Emp Publisher', $current_user_roles)) {
      // check if employee publisher can access the current row
      // get user department
      // node's 'department' should match user dept
      if (isset($node->field_department[$lang])) {
        $node_dept = $node->field_department[$lang][0]['tid'];
      } else {
        $node_dept = $node->field_department[LANGUAGE_NONE][0]['tid'];
      }
      if (!isset($node_dept)) {
        if (isset($node->field_info_department[$lang])) {
          $node_dept = $node->field_info_department[$lang][0]['tid'];
        } else {
          $node_dept = $node->field_info_department[LANGUAGE_NONE][0]['tid'];
        }
      }

      // get user department
      if ($current_user_obj->field_department[$lang]) {
        $current_user_dept = $current_user_obj->field_department[$lang][0]['tid'];
      } else {
        $current_user_dept = $current_user_obj->field_department['en'][0]['tid'];
      }

      // check if user and node dept. matches
      if ($current_user_dept == $node_dept) {
        return FALSE;
      } else {
        return TRUE;
      }  
    } else {
      return TRUE;
    }
  }


  /**
   * Function to check if publisher can review node or not
   */
  public function aaiCorporatePublisherModeration($nid, $type) {
    global $user;

    // get current user role
    $current_user_uid = $user->uid;
    $current_user_obj = user_load($current_user_uid);
    $current_user_roles = $current_user_obj->roles;

    if((in_array('aai admin', $current_user_roles)) || (in_array('administrator', $current_user_roles)) || (in_array('CHQ Publisher', $current_user_roles))) {
      return FALSE;
    }

    if ((in_array('publisher', $current_user_roles)) || (in_array('Regional Publisher', $current_user_roles))) {
      // check if corporate publisher can access the current row
      // get user department
      if ($current_user_obj->field_department[$lang]) {
        $current_user_dept = $current_user_obj->field_department[$lang][0]['tid'];
      } else {
        $current_user_dept = $current_user_obj->field_department['en'][0]['tid'];
      }

      $node = node_load($nid);
      $authorised = $this->aaiCheckCorporateContentPermission($node, $current_user_obj);
      return !$authorised;
    } else {
      return TRUE;
    }
  }

  /**
   * Function to check if user has access to a given content type.
   *
   * @param Number
   *   Department TID.
   *
   * @return Boolean
   */
  public function aaiCheckDepartment($airport_id, $dept, $content_type_category) {
    Global $user;
    $user_array = array();
    $lang = $this->aaiCurrentLang();
    $userInformation = user_load($user->uid);
    $user_dept_id = $userInformation->field_department['en'][0]['tid'];
  
    if(($content_type_category == "aai_magazine") || ($content_type_category == "achievements") || ($content_type_category == "press_news_event") || ($content_type_category == "certification")){
       $dept_Id = 45; //Public Relations
    } else if(($content_type_category == "csr_inactivities") || ($content_type_category == "csr_policy") || ($content_type_category == "csr_media_coverage")){
      $dept_Id = 42; //Planning
    } else if (($content_type_category == "careers") || ($content_type_category == "current_openings")){
      $dept_Id = 32; //Human Resource
    } else if (($content_type_category == "cargo")){
      $dept_Id = 13; //Cargo
    } else if (($content_type_category == "investors")){
      $dept_Id = 264; //Corporate Planning & Management Services
    } else if (($content_type_category == "rti")){
      $dept_Id = 17; //Corporate Social Responsibility 
    } else if (($content_type_category == "services")){
      $dept_Id = 698; //Services 
    } else if (($content_type_category == "tender")){
      $dept_Id = 699; //tender 
    } else if (($content_type_category == "faq")){
      $dept_Id = 700; //faq
    } else if (($content_type_category == "resources")){
      $dept_Id = 701; //Resources 
    } else if (($content_type_category == "vigilance") || ($content_type_category == "vigilance_integrity_club") || ($content_type_category == "vigilance_photo_gallery")){
      $dept_Id = 50; //Vigilance
    }
    
    $is_allowed = 0;
    if(in_array('CHQ Publisher', $userInformation->roles)) {
      $is_allowed = 1;
    }
    if(($user_dept_id != $dept) || ($is_allowed == 0)) {
      return TRUE;
    } else if(in_array('creator', $user_roles)) { 
        $user_type = "corporate_creator";
        $user_array[] = aaiGetUsertoAssign($user_type, $airport_id, $dept);
    } else if (in_array('Regional Creator', $user_roles)) {
        $user_type="corporate_creator";
        $user_array[] = aaiGetUsertoAssign($user_type,$airport_id,$dept);
    } else if (in_array('CHQ Creator', $user_roles)) {
        $user_type="corporate_creator";
        $user_array[] = aaiGetUsertoAssign($user_type,$airport_id,$dept);
    } 
    
    return $user_array;
  }
  
  
  /**
   * Function to return tabs for flight search page on airports.
   */
  public function aaiGetFIDSScheduleTabs() {
    $airport_name = ucwords(arg(3));
    $airport_code = $this->aaiGetAirportTermDetails($airport_name, array('field_airport_code'));
    $airport_code = $airport_code['field_airport_code'];
    $hidden_fld_schedule_acode = base64_encode($airport_code);
  $title = t("$airport_name Flights Schedule");
    $out .= "<div id='tabs'>
    <ul>
      <li id = 'aai-depart-info'><a href='#departure-tab'>" . t('Departure Flight') . "</a></li>
      <li id = 'aai-arrival-info'><a href='#arrival-tab'>" . t('Arrival Flight') . "</a></li>
    </ul> 
  <div id='departure-tab'>
  <div class='row'><div class='col-md-12'>
      <div class='col-md-3 '> <label class = 'form-lable'>" . t('Date') . "</label>
        <input type = 'date' id = 'fids-from-date-schedule' value = " . date("Y-m-d") . " />
      </div>";
    $airports_served = $this->aaiGetAirportsScheduleList($airport_code, 'departure');
  if (count($airports_served)) {        
      $out .= "<div class='col-md-3 '><label class = 'form-lable'>" . t('Destination Airport') . "</label>";
      $out .= "<select name='airports' id='fids-airport-schedule'>";
      $out .= "<option value=0>--" . t('Please Select') . "--</option>";
      foreach ($airports_served as $a_code => $a_name) {
        $out .= "<option value = $a_code>" . t($a_code) . ' - ' . t($a_name) . "</option>";
      }
      $out .= "</select></div>";
    }
    $out .= "<div class='col-md-2 '><input name='schedule'  id = 'fids-search-btn-schedule' type = 'button'  value = " . t('Search') . " onclick = 'javascript:get_fids_flight_schedule_details(\"departure\");'></div>";
    $out .= "<img id='aai-throbber' src='/sites/all/modules/custom/airports/img/throbber.gif' />";
    $out .= "</div></div>
  <div class = 'fids-result-wrapper'>
      <div id='fids-data-count'></div>
      <div id='fids-data'></div>
    </div>";
    $out .= "</div>
  <div id='arrival-tab'>
      <div class='row'><div class='col-md-12'>
        <div class='col-md-3 '><label class = 'form-lable'>" . t('Date') . "</label>
        <input type = 'date' id = 'fids-from-date-schedule1' value = " . date("Y-m-d") . " />
      </div>";
    $airports_served = $this->aaiGetAirportsScheduleList($airport_code, 'arrival');
    if (count($airports_served)) {
      $out .= " <div class='col-md-3'><label class = 'form-lable'>" . t('Source Airport') . "</label>";
      $out .= "<select name='airports' id='fids-airport-schedule1'>";
      $out .= "<option value=0>--" . t('Please Select') . "--</option>";
      foreach ($airports_served as $a_code => $a_name) {
        $out .= "<option value = $a_code>" . t($a_code) . ' - ' . t($a_name) . "</option>";
      }
      $out .= "</select></div> ";
    }
    $out .= " <div class='col-md-2'><input id = 'fids-search-btn-schedule' type = 'button' value = " . t('Search') . " onclick = 'javascript:get_fids_flight_schedule_details(\"arrival\");'></div> ";
    $out .= "<img id='aai-throbber' src='/sites/all/modules/custom/airports/img/throbber.gif' />";
    $out .="</div></div>";
    $out .= "<div class = 'fids-result-wrapper1'><div id='fids-data-count1'></div>
    <div id='fids-data1'></div>
    </div>";
    $out .= "  </div>
    <div id='airline-info-tab'></div>
    </div><input type='hidden' id = 'fids-home-schedule-airport' value=$hidden_fld_schedule_acode />";
    
  return "<div class='page-title'>$title</div>" . $out;
  }
  
  
  public function aaiThemeFIDSScheduleOutput($flight_data = array(), $type, $home_airport_code){
   //out($flight_data);die;
     if (!count($flight_data)) {
      return FALSE;
    }
  $functions = AAI::getInstance();
  $airport_name = $functions->aaiGetAirportNameFromCode($home_airport_code);
  // set the fields that will be available for display
    $selected_flds = array();
    switch ($type) {
      case 'arrival':
    $selected_flds = array(
          'frequency',
          'flight_number',
          'source_destination',
          'eff_dt_from',          
          'eff_dt_till',
          'source_destination_airport',           
          
        );

        $tbl_base_structure = "<thead>
        <tr>
      <th  rowspan='2'>" . t('Airline') . " </th>
            <th  rowspan='2'>" . t('Flight No') . " </th>
      <th  rowspan='2'>" . t('Source') . " </th>
            <th  rowspan='2'>" . t('Destination') . " </th>
            <th  rowspan='2'>" . t('Time') . " </th>
        </tr>
          
        </thead>";
        $out = "<table id='aai-fids-result-tbl-dep' class='aai-fids-tbl table table-bordered display'>";
        break;
      case 'departure':
    
        $selected_flds = array(
          'frequency',
          'flight_number',
          'source_destination',
          'eff_dt_from',          
          'eff_dt_till',
          'source_destination_airport',           
          
        );

        $tbl_base_structure = "<thead>
        <tr>
      <th  rowspan='2'>" . t('Airline') . " </th>
            <th  rowspan='2'>" . t('Flight No') . " </th>
      <th  rowspan='2'>" . t('Source') . " </th>
            <th  rowspan='2'>" . t('Destination') . " </th>
            <th  rowspan='2'>" . t('Time') . " </th>
      
        </tr>
          
        </thead>";
        $out = "<table id='aai-fids-result-tbl-dep' class='aai-fids-tbl table table-bordered display'>";
        break;
  }
   // formulate the output structure
    
    $out .= $tbl_base_structure;
    $out .= "<tbody>";
  $functions = AAI::getInstance();
    $lang = $functions->aaiCurrentLang();
    foreach ($flight_data as $data) {
    $query_comment = db_select('field_data_field_airline_code', 'fac');
    $query_comment->fields('fac', array('entity_id'));
    $query_comment->condition('fac.field_airline_code_value', $data['airline_code'],'=');
    $query_comment->condition('fac.bundle', 'airlines','=');
      $nids = $query_comment->execute()->fetchAll();
    foreach($nids as $nodeid){
      $node_details = node_load($nodeid->entity_id);
      $airline_image_name = $node_details->field_airline_image[$lang][0]['filename'];
      
    }
      $out .= "<tr>";
      if ($type == 'arrival') {
         $timelen = strlen($data['sched_time']);
      if($timelen== 4){
       $splittime = str_split($data['sched_time'], 2);
       $time = $splittime[0].":".$splittime[1];
      }
      if($timelen== 3){
       $splittime = str_split($data['sched_time'], 1);
       $time = $splittime[0].":".$splittime[1].$splittime[2];
      }
        // Arrival flights Schedule Search
     $out .= "<td><img src='/sites/default/files/airline_images/$airline_image_name' /></td>";
         $out .= "<td>" . $data['flight_number'] . "</td>";
     $out .= "<td>" . $airport_name . "</td>";
     $out .= "<td>" . $data['source_destination_airport'] . "</td>";
     $out .= "<td>" . $time . "</td>";
      } else {
      $timelen = strlen($data['sched_time']);
     
      if($timelen== 4){
       $splittime = str_split($data['sched_time'], 2);
       $time = $splittime[0].":".$splittime[1];
      }
      if($timelen== 3){
       $splittime = str_split($data['sched_time'], 1);
       $time = $splittime[0].":".$splittime[1].$splittime[2];
      }
        // departure flights Schedule Search
     $out .= "<td><img src='/sites/default/files/airline_images/$airline_image_name' /></td>";
         $out .= "<td>" . $data['flight_number'] . "</td>";
     $out .= "<td>" . $airport_name . "</td>";
     $out .= "<td>" . $data['source_destination_airport'] . "</td>";
     $out .= "<td>" . $time . "</td>";
        
      }
      $out .= "</tr>";
    }
    
    $out .= "</tbody></table>";
    if ($type == 'arrival') {
      $out .= "<script>
       jQuery('#aai-fids-result-tbl-dep').DataTable();
      </script>";
    } else {
      $out .= "<Script>jQuery('#aai-fids-result-tbl-dep').DataTable();</script>";
    }
    return $out;
    //out($flight_data);
  }

  /**
   * function to handle visitor counter
   */
  public function aaiCheckVisitor() {
    $db = DB::dbInstance();

    $ip = getenv('HTTP_CLIENT_IP')? : getenv('HTTP_X_FORWARDED_FOR')? : getenv('HTTP_X_FORWARDED')? : getenv('HTTP_FORWARDED_FOR')? : getenv('HTTP_FORWARDED')? : getenv('REMOTE_ADDR');

    $ip = trim($ip);

    // check if this IP address exists
    $tbl = 'aai_visitors';
    $condition_flds = array(
      'ip' => $ip,
    );
    $fld_name = array('id');
    $ip_exists = $db->dbConditionalRecordCount($tbl, $condition_flds, $fld_name);

    if (!$ip_exists) {
      $total_visitors = variable_get('aai_total_visitors', 5584127);
      $total_visitors++;
      variable_set('aai_total_visitors', $total_visitors);

      // do entry into the visitors log table
      $now = time();
      $flds = array(
        'ip' => $ip,
        'created' => $now,
        'updated_on' => $now,
      );
      $db->dbInsertQuery($tbl, $flds);
    }
  }

  /**
   * function to clear visitors counter table every 4 hours
   */
  public function aaiClearVisitorsTable() {
    $table_cleared_at = variable_get('aai_visitor_table_trimmed_at', 0);
    $now = time();

    if (!$table_cleared_at) {
      variable_set('aai_visitor_table_trimmed_at', $now);
      $table_cleared_at = $now;
    }

    // get seconds in 4 hours
    $four_hrs_hence_cleared = (60 * 60 * 4) + $table_cleared_at;
    $buffer_seconds = (60 * 5) + $four_hrs_hence_cleared;

    // check if 4 hours passed since last table truncate operation
    if ($now >= $four_hrs_hence_cleared && $now  <= $buffer_seconds) {
      // truncate the table
      $db->dbTruncateQuery('aai_visitors');

      // set variable
      variable_set('aai_visitor_table_trimmed_at', $now);
    }
  }

  /**
   * function to get airports achievements Count
   */
  public function get_airport_achievements($term_id) {
    $db = DB::dbInstance();
   /*
    $sql = db_query("SELECT count(node.nid) AS countnid
            FROM node node
            LEFT JOIN taxonomy_index taxonomy_index ON node.nid = taxonomy_index.nid
            LEFT JOIN taxonomy_term_data taxonomy_term_data_node ON taxonomy_index.tid = taxonomy_term_data_node.tid 
             WHERE (( (taxonomy_term_data_node.tid = '".$term_id."' ) )
              AND(( (node.status = '1') 
              AND (node.type IN ('airport_achievements')) ))) 
              LIMIT 10 OFFSET 0")->fetchAssoc();
   */
      $tbl2 = "node";       
      $flds_to_select = array('nid');

      $conditions = array (
        'status' => 1,
        'type' => 'airport_achievements',
      );
      $joins[] = array(
         'join' => 'leftjoin',
         'tbl' => 'taxonomy_index',
         'alias' => 'taxonomy_index',
         'on' => "taxonomy_index.nid = tbl.nid",           
      );  
      $joins[] = array(
        'join' => 'leftjoin',
        'tbl' => 'taxonomy_term_data',
        'alias' => 'taxonomy_term_data',
        'on' => "taxonomy_term_data.tid = taxonomy_index.tid",
        'condition' => array('taxonomy_term_data.tid' => $term_id),
      );
 
    $rs = $db->dbSelectWithJoin($tbl2, $joins, $conditions, $flds_to_select);
    $nodecount = $rs->rowCount();
    return $nodecount;
  }

} //class ends
