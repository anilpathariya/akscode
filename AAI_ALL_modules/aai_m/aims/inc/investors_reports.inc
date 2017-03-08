<?php
/**
 * @file
 *   file to list type of reports available under investors.
 */

/**
 * Function to return themed list of insvestors reports available.
 */
function investors_reports() {
  drupal_set_title(t('Traffic News'));
  
  
  
  
  
  
  
  
  
  
  /* Temprarory Code will be changed after requirement gathering*/
  ?>
  
 
  
  <?php
  $out = "<div>";
  $out .= "<div><form action='/investors/reports/traffic-news' method='get' id='views-exposed-form-resources-page' accept-charset='UTF-8' class='jquery-once-1-processedd'><div><div class='views-exposed-form'>
  <div class='views-exposed-widgets clearfix'>
           
           
           
          <div id='edit-combine-wrapper' class='views-exposed-widget views-widget-filter-combine'>
                  <label for='edit-combine'>
            Month          </label>
                        <div class='views-widget'>
          <div class='form-item form-type-textfield form-item-combine'>
  <select id='edit-field-department-tid' name='field_department_tid' class='form-select'>


<option value='All' selected='selected'>- Any -</option>

<option  value='1'>Janaury</option>
    <option value='2'>February</option>
    <option value='3'>March</option>
    <option value='4'>April</option>
    <option value='5'>May</option>
    <option value='6'>June</option>
    <option value='7' selected>July</option>
    <option value='8'>August</option>
    <option value='9'>September</option>
    <option value='10'>October</option>
    <option value='11'>November</option>
    <option value='12'>December</option>


</select>
</div>
        </div>
              </div>
			  
			  
			  
			  <div id='edit-combine-wrapper' class='views-exposed-widget views-widget-filter-combine'>
                  <label for='edit-combine'>
            Year          </label>
                        <div class='views-widget'>
          <div class='form-item form-type-textfield form-item-combine'>
   <select id='edit-field-department-tid' name='field_department_tid' class='form-select'>


<option value='All' selected='selected'>- Any -</option>

<option value='2005'>2005</option> 
 <option value='2006'>2006</option> 
 <option value='2007'>2007</option> 
 <option value='2008'>2008</option> 
 <option value='2009'>2009</option> 
 <option value='2010'>2010</option> 
  <option value='2011'>2011</option> 
   <option value='2012'>2012</option> 
    <option value='2013'>2013</option> 
	 <option value='2014'>2014</option> 
	  <option value='2015'>2015</option> 
	   <option value='2016' selected>2016</option> 
	   
		  


</select>
</div>
        </div>
              </div>
			  
			  <div id='edit-combine-wrapper' class='views-exposed-widget views-widget-filter-combine'>
                  <label for='edit-combine'>
            Annexure          </label>
                        <div class='views-widget'>
          <div class='form-item form-type-textfield form-item-combine'>
    <select id='edit-field-department-tid' name='field_department_tid' class='form-select'>


<option value='All' selected='selected'>- Any -</option>

<option value='1'>Annexure 1</option> 
<option value='2'>Annexure 2</option> 
<option value='3'>Annexure 3</option> 
<option value='4'>Annexure 4</option> 
<option value='5'>Annexure 5</option> 


</select>
</div>
        </div>
              </div>
			  
			  
                    <div class='views-exposed-widget views-submit-button'>
      <input type='submit' id='edit-submit-resources' name='' value='Search' class='form-submit'>    </div>
         
      </div>
</div>
</div></form></div>";
  $out .= "</div>";
 
  
  
  
  
  
  


  $out .= "<ul class = 'aai-ul traffic-news' id='annexure'>";
    $out .= "<li>" . l(t("Annexure 1 (Monthly Report)"), 'investors/reports/traffic-news') . "</li>";
  $out .= "</ul>";

  return $out;
}
?>
