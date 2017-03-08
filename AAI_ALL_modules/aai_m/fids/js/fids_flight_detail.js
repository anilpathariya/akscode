/**
 * @file
 *   JS file for showing FIDS flight detail.
 */
jQuery(document).ready(function() {
});

// function called after all page elements loaded
jQuery(window).load(function() {
	jQuery('#aai-throbber').remove();
  // get current date flight data on page load
  var home_airport_code = jQuery('#fids-home-airport').val();
  var fids_date = jQuery('#fids-from-date').val();
  if (fids_date == '') {
  	var d = new Date();
    var strDate = d.getFullYear() + "-" + (d.getMonth()+1) + "-" + d.getDate();
  	fids_date = strDate;
  }
  var base_pth = Drupal.settings.basePath;
  jQuery.ajax({
    type: 'POST',
    url: base_pth + 'fids-search-flight-detail',
    data: 'home_airport_code='+home_airport_code+'&type=departure'+'&fids-date='+fids_date,
   // error: function(e) {alert('Please try again, if problem persists contact the administrator.');},
    success: function (response) {
      jQuery('#aai-throbber').remove();
      if(response.indexOf('~') >= 0) {
      	var tmp = response.split('~');
      	var rec_count = tmp[0];
      	var data_tbl = tmp[1];
      	jQuery('#fids-data-count').html(rec_count);
        jQuery('#fids-data').html(data_tbl);
      } else {
        jQuery('#fids-data').html(response);
      }
    }
  });
});

// specify drupal behaviours
(function($) {
  Drupal.behaviors.fids = {
    attach: function (context, settings) {
      // add jquery for tabs
      jQuery("#tabs").tabs();

      // remove throbber, of airline info if clicked on other tabs
      jQuery('#aai-depart-info').on('click', function() {
        jQuery('#aai-throbber').remove();
      });
      jQuery('#aai-arrival-info').on('click', function() {
        jQuery('#aai-throbber').remove();
      });

      // GET AIRLINES INFO
      jQuery('#aai-airline-info').on('click', function() {
        // check if airline if=nfo already shown or not
        var airln_info = jQuery('#airline-info-tab').html();
        if (airln_info == '') {
          var home_airport_code = jQuery('#fids-home-airport').val();
          jQuery('#airline-info-tab').before("<div id='aai-throbber'>Fetching details ...<img src='/sites/all/modules/custom/airports/img/throbber.gif' /></div>");
          var base_pth = Drupal.settings.basePath;
          jQuery.ajax({
            type: 'POST',
            url: base_pth + 'fids-airlines-detail',
            data: 'home_airport_code='+home_airport_code,
            error: function(e) {alert('Please try again, if problem persists contact the administrator.');},
            success: function (response) {
              jQuery('#aai-throbber').remove();
              jQuery('#airline-info-tab').html(response);
            }
          });
        } else {
          // do nothing if detail already exists.
        }
      });
    }
  };
})(jQuery);

// function called on click of search button
function get_fids_flight_details(type) {
  var home_airport_code = jQuery('#fids-home-airport').val();
  // add throbber
  if (type == 'departure') {
  var fids_date = jQuery('#departure-tab #fids-from-date').val();
  var airport = jQuery('#departure-tab #fids-airport').val();
  if (airport < 1) {
    airport = '';
  }
  var flight_no = jQuery('#departure-tab #fids-flight-no').val();
    jQuery('#departure-tab #fids-search-btn').after("<img id='aai-throbber' src='/sites/all/modules/custom/airports/img/throbber.gif' />");
  } else {
  var fids_date = jQuery('#arrival-tab #fids-from-date').val();
  var airport = jQuery('#arrival-tab #fids-airport').val();
  if (airport < 1) {
    airport = '';
  }
  var flight_no = jQuery('#arrival-tab #fids-flight-no').val();
    jQuery('#arrival-tab #fids-search-btn1').after("<img id='aai-throbber' src='/sites/all/modules/custom/airports/img/throbber.gif' />");
  }
  var base_pth = Drupal.settings.basePath;
  jQuery.ajax({
    type: 'POST',
    url: base_pth + 'fids-search-flight-detail',
    data: 'home_airport_code='+home_airport_code+'&type='+type+'&fids-date='+fids_date+'&airport-code='+airport+'&flight-no='+flight_no,
    error: function(e) {alert('Please try again, if problem persists contact the administrator.');},
    success: function (response) {
      jQuery('#aai-throbber').remove();
      // clear previous data
      if (type == 'departure') {
        jQuery('#fids-data-count').html('');
        jQuery('#fids-data').html('');
      } else {
        jQuery('#fids-data-count1').html('');
        jQuery('#fids-data1').html('');
      }

      if(response.indexOf('~') >= 0) {
        var tmp = response.split('~');
        var rec_count = tmp[0];
        var data_tbl = tmp[1];
        if (type == 'departure') {
          jQuery('#fids-data-count').html(rec_count);
          jQuery('#fids-data').html(data_tbl);
             jQuery('#aai-fids-result-tbl-dep').DataTable();
        } else {
          jQuery('#fids-data-count1').html(rec_count);
          jQuery('#fids-data1').html(data_tbl);
          jQuery('#aai-fids-result-tbl-arv').DataTable();
        }
      } else {
        if (type == 'departure') {
          jQuery('#fids-data').html(response);
          jQuery('#aai-fids-result-tbl-dep').DataTable();
        } else {
          jQuery('#fids-data1').html(response);
          jQuery('#aai-fids-result-tbl-arv').DataTable();
        }
      }
    }
  });
 
}

function get_fids_flight_schedule_details(type){
  var button_type =  jQuery("#fids-search-btn-schedule").attr("name");
  var base_pth = Drupal.settings.basePath;
  if(button_type == 'schedule' ){
	if(type == 'departure'){  
      var home_airport_code = jQuery('#fids-home-schedule-airport').val();
	  var fids_date = jQuery('#fids-from-date-schedule').val();
      var airport = jQuery('#fids-airport-schedule').val();
	    if (airport < 1) {
         airport = '';
        }
	  jQuery('#fids-search-btn-schedule').after("<img id='aai-throbber' src='/sites/all/modules/custom/airports/img/throbber.gif' />");
    }
	if(type == 'arrival'){
	  var home_airport_code = jQuery('#fids-home-schedule-airport').val();
	  var fids_date = jQuery('#fids-from-date-schedule1').val();
      var airport = jQuery('#fids-airport-schedule1').val();
	    if (airport < 1) {
          airport = '';
        } 
	 jQuery('#fids-search-btn1').after("<img id='aai-throbber' src='/sites/all/modules/custom/airports/img/throbber.gif' />");
   }
   jQuery.ajax({
     type: 'POST',
      url: base_pth + 'fids-search-flight-schedule-detail',
     data: 'home_airport_code='+home_airport_code+'&type='+type+'&fids-date='+fids_date+'&airport-code='+airport,
     success: function (response) {
     jQuery('#aai-throbber').remove();
     if (type == 'departure') {
       jQuery('#fids-data-count').html('');
       jQuery('#fids-data').html('');
     } else {
        jQuery('#fids-data-count1').html('');
        jQuery('#fids-data1').html('');
       }
	 if(response.indexOf('~') >= 0) {
	   var tmp = response.split('~');
       var rec_count = tmp[0];
       var data_tbl = tmp[1];
       if (type == 'departure') {
         jQuery('#fids-data-count').html(rec_count);
         jQuery('#fids-data').html(data_tbl);
         jQuery('#aai-fids-result-tbl-dep').DataTable();
       } else {
          jQuery('#fids-data-count1').html(rec_count);
          jQuery('#fids-data1').html(data_tbl);
          jQuery('#aai-fids-result-tbl-arv').DataTable();
          }
     } else {
     if (type == 'departure') {
       jQuery('#fids-data').html(response);
       jQuery('#aai-fids-result-tbl-dep').DataTable();
     } else {
        jQuery('#fids-data1').html(response);
        jQuery('#aai-fids-result-tbl-arv').DataTable();
       }
      }
    }
  });	  
  }
}