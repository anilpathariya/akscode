<?php
/**
 * This file contains all database queries, that possibly could be used.
 * @author
 *  Atul Kumar Sharma -sharma.atul40@gmail.com
 */

class DB {

  private static $instance;

  private function __construct() {}

  public function __clone()
  {
    trigger_error('Clone is not allowed.', E_USER_ERROR);
  }

  public static function dbInstance() {
    if(!isset(self::$instance)) {
      $c = __CLASS__;
      self::$instance = new $c;
    }
    return self::$instance;
  }

  // function to insert multiple records
  public function dbInsertMultiple($tbl, $flds, $values = array()) {
    // build query string
    $qry = db_insert($tbl);
    $qry->fields($flds);
    
    foreach($values as $val) {
      $qry->values($val);
    }

    try {
      $qry->execute();
    } catch (Exception $e) {
      $err_msg = 'Caught exception @' . __METHOD__ . ': ';
      $err_msg .= $e->getMessage() . (int)$e->getCode();
      $this->dbWatchdog($err_msg);
    }
  }

  // function to perform DB Insert operations
  public function dbInsertQuery($tbl, $flds = array(), $duplicate = 1) {
    if(!isset($tbl) || count($flds) < 1) {
      return FALSE;
    }
    // if duplicate entry not allowed
    if(!$duplicate) {
      $is_duplicate = $this->dbCheckDuplicateRecord($tbl, $flds);
      if($is_duplicate) {
        return FALSE;
      }
    }
    //return print_r( $flds);
    $qry = db_insert($tbl);
    $qry->fields($flds);
    
    try {
      $qry->execute();
    } catch (Exception $e) {
      $err_msg = 'Caught exception @' . __METHOD__ . ': ';
      $err_msg .= $e->getMessage() . (int)$e->getCode();
      $this->dbWatchdog($err_msg);
    }
    return TRUE;
  }
  
  /**
   * function to perform Db update operation
   *
   * @param
   *  String $tbl table name
   *  Array $flds associative array of fields with values
   *  Array $cnd_flds associative array of fields 'Key = filed name, value = value to insert'
   *  to generate record selection condition
   *
   * @return
   *  Boolean 1 if pass, 0 if fail
   */
  public function dbUpdateQuery($tbl, $flds = array(), $cnd_flds = array()) {
    if(!isset($tbl) || count($flds) < 1 || count($cnd_flds) < 1) {
      return FALSE;
    }
  
    $qry = db_update("$tbl");
    $qry->fields($flds);
    foreach($cnd_flds as $k => $v) {
      $qry->condition("$k", $v);
    }

    try {
      $rs = $qry->execute();
      if($rs) {
        return TRUE;
      } else {
        return FALSE;
      }
    } catch (Exception $e) {
      $err_msg = 'Caught exception @' . __METHOD__ . ': ';
      $err_msg .= $e->getMessage() . (int)$e->getCode();
      $this->dbWatchdog($err_msg);
    }
  }
   
  /**
   * function to check if duplicate record exists.<br />
   * @param
   *  $tbl table name
   *  $flds associative array of fields 'key = field name, value = field value'
   *
   * @return
   *  Boolean 1 if duplicate, 0 if original
   */
  public function dbCheckDuplicateRecord($tbl, $flds = array()) {
    if(!isset($tbl) || count($flds) < 1) {
      return FALSE;
    }
    $field_names = array_keys($flds);
    $qry = db_select("$tbl", 'tbl');
    $qry->fields("tbl", $field_names);
    foreach($flds as $k => $v) {
      $qry->condition("tbl.$k", $v);
    }
    $rs = $qry->execute();
    $count = $rs->rowCount();
    if($count) {
      return TRUE;
    } else {
      return FALSE;
    }
  }
  
  /**
   * function to get value of a column/field for a given record
   * @param
   *  String $tbl table name
   *  Array $condition_fld fields used to build the condition to select the row
   *  String $id_fld name of the field whose value is to be selected<br />
   *
   * @return
   *  Array of matched values
   */
  public function dbGetFieldValue($tbl, $condition_flds = array(), $id_fld = '') {
    if($tbl == '') {
      return FALSE;
    }
    if($id_fld == ''){
      $id_fld = 'id';
    }
    $qry = db_select("$tbl", 'tbl');
    $qry->fields('tbl', array("$id_fld"));
    foreach($condition_flds as $k => $v) {
      $qry->condition("tbl.$k", $v);
    }
    
    $rs = $qry->execute();
    $count = $rs->rowCount();
    $ans = array();
    if($count > 1){
      while($obj = $rs->fetchObject()) {
        $ans[] = $obj->$id_fld;
      }
      return $ans;
    } else {
      if($count) {
        $obj = $rs->fetchObject();
        $res = $obj->$id_fld;
        return array($res);
      } else {
        return FALSE;
      }
    }
  }

  /**
   * Implements function to get MAX value record
   */
  public function dbGetMAXRecord($tbl, $max_fld, $condition_flds = array()) {
    if($tbl == '' || $max_fld == '') {
      return FALSE;
    }

    $qry = db_select($tbl, 'tbl');
    foreach($condition_flds as $k => $v) {
      $qry->condition("tbl.$k", $v);
    }
    $qry->addExpression("MAX($max_fld)", 'max_val');
    $rs = $qry->execute();
    $count = $rs->rowCount();
    if($count) {
      $obj = $rs->fetchObject();
      return $obj->max_val;
    } else {
      return 0;
    }
  }

  /**
  *  Implements a function to select all records meeting a condition
  *
  * @param
  *  String $tbl table name
  *  Array $condition_flds associative array of conditional fields
  *  Array $flds_to_select palin array of fields that are to be selected
  *  String $operator operator to use in query condition, defaults to =
  *  Array $limit if limit to be used, gives start and end range, 1st element is start point.
  *
  * @return
  *  Array associative array with fields and there respective values
  */
  public function dbConditionalSelect($tbl, $condition_flds = array(), $flds_to_select = array(), $operator = NULL, $limit = array()) {
    if($tbl == '' || !count($condition_flds) || !count($flds_to_select)) {
      return FALSE;
    }
    $qry = db_select("$tbl", 'tbl');
    $qry->fields('tbl', $flds_to_select);
    foreach($condition_flds as $k => $v) {
      if(is_null($operator)) {
        if(is_array($v)) {
          $fld_val = $v[0];
          $fld_exp = $v[1];
          if (isset($v[2])) {
            $param = $v[2];
            switch ($param) {
              case 'where':
                $qry->where("tbl.$k $fld_exp $fld_val");
                break;
            }
          } else {
            $qry->condition("tbl.$k", $fld_val, $fld_exp);
          }
        } else {
          $qry->condition("tbl.$k", $v);
        }
      } else {
        if(is_array($v)) {
          $fld_val = $v[0];
          $fld_exp = $v[1];
          if (isset($v[2])) {
            $param = $v[2];
            switch ($param) {
              case 'where':
                $qry->where("tbl.$k $fld_exp $fld_val");
                break;
              case 'or':
                $or_condition_exists = 1;
                if ($fld_exp == 'like') {
                  $db_or->condition("tbl.$k", "%$fld_val%", $fld_exp);
                } else {
                  $db_or->condition("tbl.$k", "%$fld_val%", $fld_exp);
                }
                break;
            }
          } else {
            if ($fld_exp == 'like') {
              $qry->condition("tbl.$k", "%$fld_val%", $fld_exp);
            } else {
              $qry->condition("tbl.$k", "$fld_val", $fld_exp);
            }
          }
        } else {
          $qry->condition("tbl.$k", $v, $operator);
        }
      }
    }
    if(count($limit)) {
      $strt = $limit[0];
      $end = $limit[1];
      $qry->range($strt, $end);
    }
    
    $rs = $qry->execute();
    $count = $rs->rowCount();
    $ans = array();
    if($count){
      $i = 0;
      while($obj = $rs->fetchObject()) {
        $record = array();
        foreach($flds_to_select as $fld) {
          $record[$fld] = $obj->$fld;
        }
        $ans[$i++] = $record;
      }
    }
    return $ans;
  }

  /**
   *  Implements function to return all vocabulary tree
   *  
   * @param
   *  String $vocab_name vocabulary name whose tree is to be selected (not machine name)
   *
   * @return
   *  Associative Array $all_terms if found, 0 if no result found
   */
  public function dbGetVocabTreeByName($vocab_name = '') {
    if($vocab_name == '') {
      return FALSE;
    }
    $vocab_name = trim($vocab_name);
    // first get vocabulary ID
    $vocab_vid = '';
    $vocab = taxonomy_get_vocabularies();
    foreach ($vocab as $v) {
      if ($v->name == $vocab_name) {
        $vocab_vid = $v->vid;
        break;
      }
    }

    if($vocab_vid != '') {
      $terms = taxonomy_get_tree($vocab_vid);
      $all_terms = array();
      foreach ($terms as $v) {
        $all_terms[$v->tid] = $v->name;
      }
      return $all_terms;
    }
    return 0;
  }
  
  /**
  *  Implements delete query
  *
  * @param
  *  String $tbl table name
  *  
  */
  public function dbDeleteQuery($tbl, $condition_flds = array()) {
    if($tbl == '' || !count($condition_flds)) {
      return FALSE;
    }
    $qry = db_delete($tbl);
    foreach($condition_flds as $k => $v) {
      $qry->condition("'$k'", "'$v'");
    }
    $qry->execute();
    return TRUE;
  }

  /**
   * Implements truncate query
   */
  public function dbTruncateQuery($tbl) {
    if ($tbl == '') {
      return FALSE;
    }

    db_truncate($tbl)->execute();
  }
  
  /**
   *  function to fetch all records
   */
  public function dbSelectAll($tbl) {
    $qry = db_select("$tbl",'tbl');
    $rs = $qry->execute();
    return $rs;
  }

  /**
  *  Function to return count of records meeting a condition
  *
  * @param
  *  String $tbl table name
  *  Array $condition_flds associative array of conditional fields
  *  Array $fld_name plain array of fields that are to be selected
  *
  * @return
  *  Integer $count count of the records
  */
  public function dbConditionalRecordCount($tbl, $condition_flds = array(), $fld_name = array()) {
    if($tbl == '' || !count($condition_flds)) {
      return FALSE;
    }
    $qry = db_select("$tbl",'tbl');
    if($fld_name != '') {
      $qry->fields('tbl', $fld_name);
    }
    foreach($condition_flds as $k => $v) {
      $qry->condition("tbl.$k", $v);
    }
    $rs = $qry->execute();
    return $rs->rowCount();
  }

  /**
   * function to Camouflage a numer
   */
  public function dbCamouflageNumber($mobile_num) {
    $mobile_num_ary = str_split($mobile_num);
    $mobile_num = '';
    foreach($mobile_num_ary as $k => $v) {
      if($k < 3 || $k > 8) {
        $mobile_num .= $v;
      } else {
        $mobile_num .= '*';
      }
    }
    return $mobile_num;
  }
  
  /**
   * Function to generate random number
   *
   * @param Integer $iNumChars
   *   number of characters in random number.<br />
   *
   * @param Boolean $arbitrary
   *   whether to include alphanumeric characters
   */ 
  public function dbGenerateCode($iNumChars, $arbitrary=false) {
    // reset code
    $sCode = '';
         
    // loop through and generate the code letter by letter
    for ($i = 0; $i < $iNumChars; $i++) {
 
      if( $arbitrary == false ) {
        // select random character and add to code string
        $sCode .= chr(rand(65, 90));
      } else {
        /********************************************/
        /* alternatively replace the line above     */
        /* with the following code to enable        */
        /* support for arbitrary characters         */
        /********************************************/
        // characters to use
        $aChars = array('A', 'B', 'C', '3', 'g', 'G', 'D', 'E', 'F', 'h', 'i', 'J', '#', 'k', '4', '8', '2', 'x', 'd', 'z','p', 'Q', 'r', 'S', 't', 'v', 'V', '6', 'w', '7', 'K', 'M', 'N', 'R', 'T', 'U', 'W', 'X', 'Y', 'Z', 'a', 'b', 'f', 'H', '4', '5', '1', '9', 'c', 'e', 'j', 'q');
            
        // get number of characters
        $iTotal = count($aChars) - 1;

        // get random index
        $iIndex = rand(0, $iTotal);

        // add selected character to code string
        $sCode .= $aChars[$iIndex];

        /********************************************/
        /* End of optional code                     */
        /********************************************/
      } // end if

    } // end for
 
    return $sCode;
    // end GenerateCode
  }

  // function to encrypt or decrypt a string
  public function dbEncryptDecrypt($action, $plaintext) {
    $output = false;
    $key = variable_get('encrypt_decrypt_key', '');
  
    # create a random IV to use with CBC encoding
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

    if( $action == 'encrypt' ) {
      $output = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $plaintext, MCRYPT_MODE_CBC, $iv);
      $output = $iv . $output;
      $output = base64_encode($output);
    }
    else if( $action == 'decrypt' ){
      $output_desc = base64_decode($plaintext);
      $iv_dec = substr($output_desc, 0, $iv_size);
      $output_desc = substr($output_desc, $iv_size);
      $output = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $output_desc, MCRYPT_MODE_CBC, $iv_dec);
    //$output = rtrim($output, '');
    }
    return $output;
  }

  /**
  *  Implements function to get term ID by name
  *
  * @param
  *  String $term_name name of the term to search for
  *  String $vocab_name (optional) vocabulary machine name to look into
  *
  * @return
  *  Array of matched term ID's
  */
  public function dbGetTermIdFromName($term_name = NULL, $vocab_name = NULL) {
    if(is_null($term_name)) {
      return 0;
    }
    return array_keys(taxonomy_get_term_by_name($term_name, $vocab_name));
  }
  
  /*
   *  Implements function to format a date
   *
   * @param
   *  Date $date date in any format or simple timestamp
   *
   * @return
   *  Date as per specified format
   *
   * @see
   *  $conf['db_date_format'] in settings.php
   */
  public function dbFormatDate($date) {
    if(strpos($date, '-') !== FALSE || strpos($date, ' ') !== FALSE) {
      $date = strtotime($date);
    }
    $format = variable_get('db_date_format');
    return format_date($date, 'custom', $format);
  }

  /**
  *  Implements function to cut short a string
  *
  * @param
  *  String $str string to wrap
  *  Number $character_limit number of characters to cut from
  *
  * @return
  *  String $str wrapped string or FALSE in case of error
  */
  public function dbCropString($str = '', $character_limit = 0) {
    if($str == '' || !$character_limit) {
      return FALSE;
    }
    if(strlen($str) > $character_limit) {
      $str = wordwrap($str, $character_limit, '~!');
      $str = explode('~!', $str);
      $str = $str[0];
    }
    return $str;
  }

  /**
   * Implements function to set term in proper hierarchy.
   * i.e parent child relation
   *
   * @param
   *  Array $terms array of term ID's to set in order.
   *
   * @return 
   *  Array $term_ary array in parent child form.
   */
  public function dbSetTermHierarchy($terms) {
    if(!count($terms)) {
      return FALSE;
    }
    $term_ary = array();
    foreach($terms as $v) {
      $temp = taxonomy_get_parents($v);
      if(!count($temp)) {
        if(!array_key_exists($v, $term_ary)) {
          $parent_term = taxonomy_term_load($v);
          $name = $parent_term->name;
          $term_ary[$v] = array($v => $name);
        }
      } else {
        $key = key($temp);
        $parent_tid = $temp[$key]->tid;
        $child_term = taxonomy_term_load($v);
        $term_ary[$parent_tid][$v] = $child_term->name;
      }
    }
    return $term_ary;
  }

  /**
   * Implements function to get comments thread
   *
   * @param
   *  Object $node_obj node object whose comments are returned
   *  Constant $mode represents the comment listing mode
   *    COMMENT_MODE_THREADED or COMMENT_MODE_FLAT
   *  Integer $count noumber of comments to return
   *
   * @return
   *  String $comments_blk structured comments block
   */
  public function dbGetCommentsThread($node_obj, $mode = COMMENT_MODE_THREADED, $count = 10) {
    global $base_url;

    $comments_ary = comment_get_thread($node_obj, $mode, $count);
    $comments = comment_load_multiple($comments_ary);
    $comments_blk = "<div class='db-comments'>";
    foreach($comments as $v) {
      $cid = $v->cid;
      $lang = $v->language;
      $c_uid = $v->uid;
      $c_bdy = $v->comment_body[$lang][0]['value'];
      $c_user = $this->dbGetAuthorDetail($c_uid);
      $c_user_img_pth = $c_user['user_img'];
      $c_user_name = $c_user['first_name']; // . ' ' . $c_user['last_name'];
      $comments_blk .= "<div class='db-comment wrapper-$cid'>";
      $comments_blk .= "<div class='db-cusr-img'><img src='$c_user_img_pth' /></div>";
      $comments_blk .= "<div class='db-comment-detail'>";
      $comments_blk .= "<div class='db-comment-usr-name'>$c_user_name</div>";
      $comments_blk .= "<div class='db-comment-bdy'>$c_bdy</div>";
      $comments_blk .= "</div></div>";
    }
    return $comments_blk .= '</div>';
  }
 
  /** Implemenhts function to execute almost any db_select query, except the one with joins
   *
   * @param
   *  String $tbl table name
   *  String $expression expression for the query - "COUNT('entity_id')"
   *  Array $con_flds associative array of condition fields
   *  Array $fld_to_select array of fields to select
   *  String $groupby groupby clause
   *  String $orderby groupBy clause
   *  String $orderas how to order result in ASC or DESC
   *  Array $limit adds limit to query 1 element is first argument ...
   *
   * @return
   *  Array $result associative array
   */
  public function dbSelectWithExpression($tbl, $expression = '', $con_flds = array(), $fld_to_select = array(), $groupby = '', $orderby = '', $orderas = 'ASC', $limit = array()) {
    if($tbl == '' || !count($con_flds) || !count($fld_to_select)) {
      return 0;
    }    
    $result = array();

    $qry = db_select($tbl, 'tbl');
    $qry->fields('tbl', $fld_to_select);
    
    // set variable for 'OR' conditions in query
    $db_or = db_or();
    $or_condition_exists = 0;

    foreach($con_flds as $k => $v) {
      // replace token '~!' used when multiple condirion are applied on one field 
      $k = str_replace('~!', '', $k);
      if(is_array($v)) {
        $fld_val = $v[0];
        $fld_exp = strtolower($v[1]);

        if (isset($v[2])) {
          $param = strtolower($v[2]);
          switch ($param) {
            case 'where':
              $qry->where("tbl.$k $fld_exp $fld_val");
              break;
            case 'or':
              $or_condition_exists = 1;
              if ($fld_exp == 'like') {
                $db_or->condition("tbl.$k", "%$fld_val%", $fld_exp);
              } else {
                $db_or->condition("tbl.$k", "%$fld_val%", $fld_exp);
              }
              break;
          }
        } else {
          if ($fld_exp == 'like') {
            $qry->condition("tbl.$k", "%$fld_val%", $fld_exp);
          } else {
            $qry->condition("tbl.$k", "$fld_val", $fld_exp);
          }
        }
      } else {
        $qry->condition("tbl.$k", "$v");
      }
    }

    // add or condition if exists
    if ($or_condition_exists) {
      $qry->condition($db_or);
    }

    if($expression != '') {
      $qry->addExpression("$expression", 'expression');
    }

    if($groupby != '') {
      $qry->groupBy("$groupby");
    }

    if($orderby != '') {
      $qry->orderBy("$orderby", $orderas);
    }

    if(count($limit)) {
      $strt = $limit[0];
      $end = $limit[1];
      $qry->range($strt, $end);
    }
    //echo $qry->__toString();
    //out($qry->getArguments());
    $rs = $qry->execute();
 
    $result = array();
    if($rs->rowCount()) {
      $i = 0;
      while($obj = $rs->fetchObject()) {
        $tmp = array();
        foreach($fld_to_select as $v) {
          $tmp[$v] = $obj->$v;
        }
        if($expression) {
          $tmp['expression'] = $obj->expression;
        }
        $result[$i++] = $tmp;
      }
    }
    return $result;
  }

  /**
   * Implements function to execute select query with join
   *
   * @param
   *  $tbl table name
   *  Associative Array $join
   *  $join[] = array(
   *    'join' => 'leftjoin',
   *    'tbl' => 'Table Name',
   *    'alias' => 'Tbl Alias',
   *    'on' => "alias.field = alias.field",
   *    'fields' => array('field1', 'field2'),
   *    'condition' => array('n.type' => 'badge') or array('n.nid' => array(100, '>='))
   *  );
   *  Associative Array $condition condition array
   *  Array $flds_to_select (fields from primary table)
   *  String $join_name, name of join to use 'leftjoin' or 'rightjoin'.
   *
   * @return
   *  Object $rs mysql resultset
   */
  public function dbSelectWithJoin($tbl, $joins = array(), $conditions = array(), $flds_to_select = array()) {
    if($tbl == '' || count($joins) == 0) {
      return 0;
    }

    $join_fields_ary = array();
    $join_condition_ary = array();
    $qry = db_select($tbl, 'tbl');
    // loop joins array
    foreach($joins as $join_ary) {
      $join_tbl = $join_ary['tbl'];
      $join_tbl_alias = $join_ary['alias'];
      $join_on = $join_ary['on'];
      if(array_key_exists('fields',$join_ary)) {
        $join_fields_ary[] = array($join_tbl_alias, $join_ary['fields']);
      }
      if(array_key_exists('condition',$join_ary)) {
        $join_condition_ary[] = $join_ary['condition'];
      }
      // check if join name exists
      if(array_key_exists('join', $join_ary)) {
        $join_name = $join_ary['join'];
      } else {
        $join_name = 'join';
      }

      switch ($join_name) {
        case 'join' :
          $qry->join("$join_tbl", "$join_tbl_alias", "$join_on");
          break;
        case 'leftjoin' : 
          $qry->leftJoin("$join_tbl", "$join_tbl_alias", "$join_on");
          break;
        case 'rightjoin' :
          $qry->rightJoin("$join_tbl", "$join_tbl_alias", "$join_on");
          break;
        default :
          $qry->innerjoin("$join_tbl", "$join_tbl_alias", "$join_on");
          break;
      }
    }

    // loop join fields
    foreach($join_fields_ary as $val) {
      $tbl_alias = $val[0];
      $flds = $val[1];
      $qry->fields($tbl_alias, $flds);
    }

    // add fields from primary table
    if(count($flds_to_select)) {
      $qry->fields('tbl', $flds_to_select);
    }

    // include join conditions
    if(count($join_condition_ary)) {
      foreach($join_condition_ary as $val) {
        foreach($val as $k => $v) {
          if(is_array($v)) {
            $fld_val = $v[0];
            $fld_exp = $v[1];
            $qry->condition($k, $fld_val, $fld_exp);
          } else {
            $qry->condition($k, $v);
          }
        }
      }
    }

    // add primary conditions
    if(count($conditions)) {
      foreach($conditions as $k => $v) {
        if(is_array($v)) {
          $fld_val = $v[0];
          $fld_exp = $v[1];
          $qry->condition("tbl.$k", $fld_val, $fld_exp);
        } else {
          $qry->condition("tbl.$k", $v);
        }
      }
    }

    try { //echo $qry->__toString();
      $rs = $qry->execute();
      return $rs;
    } catch (Exception $e) {
      $err_msg = 'Caught exception @dbSelectWithJoin: ';
      $err_msg .= $e->getMessage() . (int)$e->getCode();
      $this->dbWatchdog($err_msg);
    }
  }

  /**
   * Implements function to format investment amount
   *
   * @param
   *  Number $amt amount
   *  
   * @return
   *  Number $amount Formated amount
   */
  public function dbFormatAmount($amt) {
    $amount = round($amt / 1000, 2);
    return $amount . 'K';
  }

  /**
   * Implements function to Get Comments Count on Nodes.
   * @param
   * $node_type - String - Machine name of the content type.
   * $timelimit - Integer - Timestamp for no. of days.
   * @return
   * $count - Integer - Comment Count.
   */
  public function dbGetCommmentsCount($node_type, $timelimit = 0) {
    $qry = db_select('comment', 'c');
    $qry->Join('node', 'n', 'n.nid = c.nid');
    $qry->fields('c',array('cid'));
    $qry->condition('n.type', $node_type);
    if($timelimit) {
      $qry->condition("c.created", $timelimit, '>=');
    }
    $count = $qry->execute()->rowCount();
    return $count;
  }

  /**
   * Implements function to get comment attachment count OR records on a node.
   *
   * @param
   *  $nid - Integer - Node id.
   *  $comment_node_bundle - String -  Comment node budle i.e. comment_node_project, comment_node_answers_answer etc.
   *  $type - String - Default is count to return attachment count.
   *
   * @return
   *  $rs - Integer OR Array - Default count of attachments on comments of a node 
   *  else all attachment records on node comments.
   */
  public function dbGetCommentAttachmentsList($nid, $comment_node_bundle, $type = 'count') {
    //get all comment ids where node id is $nid.
    $sub_qry = db_select('comment', 'c');
    $sub_qry->fields('c', array('cid'));
    $sub_qry->where("c.nid  = $nid");
    //Get all fids where entity_id IN $sub_qry.
    $subqry = db_select('field_data_field_file_upload', 'u');
    $subqry->fields('u', array('field_file_upload_fid'));
    $subqry->where("u.entity_id IN($sub_qry) 
      AND u.entity_type = 'comment' 
      AND u.bundle = '$comment_node_bundle'"
    );
    //Get all fields where fid IN $subqry.
    $qry = db_select('file_managed', 'm');
    $qry->fields('m', array('fid', 'filename', 'uri', 'filemime'));
    $qry->condition('fid', $subqry, 'IN');
    if($type != 'count') {
      $rs = $qry->execute()->fetchAll();
    } else {
      $rs = $qry->execute()->rowCount();
    }
    return $rs;
  }

  /**
   * Implments function to drop table
   */
  public function dbDropTable($tbl = '') {
    if ($tbl == '') {
      return FALSE;
    }

    db_drop_table($tbl);
    return TRUE;
  }
 
  /**
   * Implements watchdog entry function
   */
  public function dbWatchdog($msg) {
    watchdog('common', $msg, array(), WATCHDOG_ERROR, NULL);
  }
  
} // class ends