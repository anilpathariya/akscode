jQuery(document).ready(function(event) {
  
  var d = new Date();
  var url = window.location.href;
  var slan = url.split('/');
  var language = slan[3];
  var currentdate = jQuery.datepicker.formatDate('mm/dd/yy', new Date());
  //Tender Last Sale Date
  jQuery('#edit-field-tender-last-sale-date-'+language+'-0-value-datepicker-popup-0').change(function(){ 
    var selected = jQuery(this).value();
    if(selected < currentdate)
    {
          jQuery("#edit-field-tender-last-sale-date-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
          jQuery('#edit-field-tender-last-sale-date-'+language+'-0-value-datepicker-popup-0').focus(function(){
          jQuery('.validation').hide();
         });
    }
    else{
       jQuery('.validation').hide();
     }
  });

  //Opening of Tender
  jQuery('#edit-field-opening-of-tender-'+language+'-0-value-datepicker-popup-0').change(function(){ 
    var selected = jQuery(this).val();
    if(selected < currentdate)
    {
          jQuery("#edit-field-opening-of-tender-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
          jQuery('#edit-field-opening-of-tender-'+language+'-0-value-datepicker-popup-0').focus(function(){
          jQuery('.validation').hide();
         });
    }
    else{
       jQuery('.validation').hide();
     }
  });

  //Sale of Tender
   jQuery('#edit-field-sale-of-tender-'+language+'-0-value-datepicker-popup-0').change(function(){ 
    var selected = jQuery(this).val();
    if(selected < currentdate)
    {
          jQuery("#edit-field-sale-of-tender-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
          jQuery('#edit-field-sale-of-tender-'+language+'-0-value-datepicker-popup-0').focus(function(){
          jQuery('.validation').hide();
         });
    }
    else{
       jQuery('.validation').hide();
     }
  });

    jQuery('#edit-field-sale-of-tender-'+language+'-0-value2-datepicker-popup-0').change(function(){ 
    var selected = jQuery(this).val();
    if(selected < currentdate)
    {
          jQuery("#edit-field-sale-of-tender-"+language+"-0-value2").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
          jQuery('#edit-field-sale-of-tender-'+language+'-0-value2-datepicker-popup-0').focus(function(){
          jQuery('.validation').hide();
         });
    }
    else{
       jQuery('.validation').hide();
     }
  // Sale of Tender to-From date comparision
     if(jQuery('#edit-field-sale-of-tender-'+language+'-0-value2-datepicker-popup-0').val() <  jQuery('#edit-field-sale-of-tender-'+language+'-0-value-datepicker-popup-0').val())
     {
        jQuery("#edit-field-sale-of-tender-"+language+"-0-value2").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>To Date Should Be Greater Then From Date</div>");
          jQuery('#edit-field-sale-of-tender-'+language+'-0-value2-datepicker-popup-0').focus(function(){
          jQuery('.validation').hide();
       });
     }

  });

// Corrigendum validation 
    jQuery('#edit-field-corrigendum-details-'+language+'-0-field-corrigendum-date-'+language+'-0-value-datepicker-popup-0').change(function(){ 
    var selected = jQuery(this).val();
    
    if(selected < currentdate)
    {
          jQuery("#edit-field-corrigendum-details-"+language+"-0-field-corrigendum-date-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
          jQuery('#edit-field-corrigendum-details-'+language+'-0-field-corrigendum-date-'+language+'-0-value-datepicker-popup-0').focus(function(){
          jQuery('.validation').hide();
         });
    }
    else{
       jQuery('.validation').hide();
     }
  });


  // PQQ validation 
    jQuery('#edit-field-tender-cpq-collection-'+language+'-0-field-tender-cpq-date-'+language+'-0-value-datepicker-popup-0').change(function(){ 
    var selected = jQuery(this).val();
    
    if(selected < currentdate)
    {
          jQuery("#edit-field-tender-cpq-collection-"+language+"-0-field-tender-cpq-date-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
          jQuery('#edit-field-tender-cpq-collection-'+language+'-0-field-tender-cpq-date-'+language+'-0-value-datepicker-popup-0').focus(function(){
          jQuery('.validation').hide();
         });
    }
    else{
       jQuery('.validation').hide();
     }
  });  

     // Technical Evaluation validation 
    jQuery('#edit-field-technical-evaluation-colle-'+language+'-0-field-technical-evaluation-date-'+language+'-0-value-datepicker-popup-0').change(function(){ 
    var selected = jQuery(this).val();
    
    if(selected < currentdate)
    {
          jQuery("#edit-field-technical-evaluation-colle-"+language+"-0-field-technical-evaluation-date-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
          jQuery('#edit-field-technical-evaluation-colle-'+language+'-0-field-technical-evaluation-date-'+language+'-0-value-datepicker-popup-0').focus(function(){
          jQuery('.validation').hide();
         });
    }
    else{
       jQuery('.validation').hide();
     }
  });

     // Finance Evaluation validation 
    jQuery('#edit-field-finance-evaluation-collect-'+language+'-0-field-finance-evaluation-date-'+language+'-0-value-datepicker-popup-0').change(function(){ 
    var selected = jQuery(this).val();
    
    if(selected < currentdate)
    {
          jQuery("#edit-field-finance-evaluation-collect-"+language+"-0-field-finance-evaluation-date-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
          jQuery("#edit-field-finance-evaluation-collect-'+language+'-0-field-finance-evaluation-date-'+language+'-0-value-datepicker-popup-0").focus(function(){
          jQuery('.validation').hide();
         });
    }
    else{
       jQuery('.validation').hide();
     }
  });

 // Awarded validation 
    jQuery('#edit-field-tender-awarded-'+language+'-0-field-date-of-award-'+language+'-0-value-datepicker-popup-0').change(function(){ 
    var selected = jQuery(this).val();
    
    if(selected < currentdate)
    {
          jQuery("#edit-field-tender-awarded-"+language+"-0-field-date-of-award-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
          jQuery("#edit-field-tender-awarded-"+language+"-0-field-date-of-award-"+language+"-0-value-datepicker-popup-0").focus(function(){
          jQuery('.validation').hide();
         });
    }
    else{
       jQuery('.validation').hide();
     }
  });

    // Schedule Completation validation 
    jQuery('#edit-field-tender-awarded-'+language+'-0-field-schedule-completion-of-sup-'+language+'-0-value-datepicker-popup-0').change(function(){ 
    var selected = jQuery(this).val();
    
    if(selected < currentdate)
    {
          jQuery("#edit-field-tender-awarded-"+language+"-0-field-schedule-completion-of-sup-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
          jQuery('#edit-field-tender-awarded-'+language+'-0-field-schedule-completion-of-sup-'+language+'-0-value-datepicker-popup-0').focus(function(){
          jQuery('.validation').hide();
         });
    }
    else{
       jQuery('.validation').hide();
     }
  });

     // Reverse Auction validation 
    jQuery('#edit-field-tender-reverse-auction-col-'+language+'-0-field-date-of-reverse-auction-'+language+'-0-value-datepicker-popup-0').change(function(){ 
    var selected = jQuery(this).val();
    
    if(selected < currentdate)
    {
          jQuery("#edit-field-tender-reverse-auction-col-"+language+"-0-field-date-of-reverse-auction-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
          jQuery('#edit-field-tender-reverse-auction-col-'+language+'-0-field-date-of-reverse-auction-'+language+'-0-value-datepicker-popup-0').focus(function(){
          jQuery('.validation').hide();
         });
    }
    else{
       jQuery('.validation').hide();
     }
  });
  
  // Cancel Tender validation 
    jQuery('#edit-field-tender-cancelled-collectio-'+language+'-0-field-tender-cancelled-date-'+language+'-0-value-datepicker-popup-0').change(function(){ 
    var selected = jQuery(this).val();
    
    if(selected < currentdate)
    {
          jQuery("#edit-field-tender-cancelled-collectio-"+language+"-0-field-tender-cancelled-date-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
          jQuery('#edit-field-tender-cancelled-collectio-'+language+'-0-field-tender-cancelled-date-'+language+'-0-value-datepicker-popup-0').focus(function(){
          jQuery('.validation').hide();
         });
    }
    else{
       jQuery('.validation').hide();
     }
  });



  jQuery('#tender-node-form #edit-submit').click(function(){
     return tender_validate();
  });
  

  }); 

function tender_validate(){
  var url = window.location.href;
  var slan = url.split('/');
  var language = slan[3];
  var d = new Date();
  var currentdate = jQuery.datepicker.formatDate('mm/dd/yy', new Date());
  //Tender Last Sale Date
  var last_sale_date = jQuery('#edit-field-tender-last-sale-date-'+language+'-0-value-datepicker-popup-0').val();
  var opening_of_tender = jQuery('#edit-field-opening-of-tender-'+language+'-0-value-datepicker-popup-0').val();
  var sale_of_tender_sdate = jQuery('#edit-field-sale-of-tender-'+language+'-0-value-datepicker-popup-0').val();
  var sale_of_tender_edate =  jQuery('#edit-field-sale-of-tender-'+language+'-0-value2-datepicker-popup-0').val();
  var corrigendum_date = jQuery('#edit-field-corrigendum-details-'+language+'-0-field-corrigendum-date-'+language+'-0-value-datepicker-popup-0').val();
  var pqq_date = jQuery('#edit-field-tender-cpq-collection-'+language+'-0-field-tender-cpq-date-'+language+'-0-value-datepicker-popup-0').val();
  var tech_eval_date = jQuery('#edit-field-technical-evaluation-colle-'+language+'-0-field-technical-evaluation-date-'+language+'-0-value-datepicker-popup-0').val();
  var financial_eval_date = jQuery('#edit-field-finance-evaluation-collect-'+language+'-0-field-finance-evaluation-date-'+language+'-0-value-datepicker-popup-0').val();
  var awarded_date = jQuery('#edit-field-tender-awarded-'+language+'-0-field-date-of-award-'+language+'-0-value-datepicker-popup-0').val();

  var schedule_awarded_date = jQuery('#edit-field-tender-awarded-'+language+'-0-field-schedule-completion-of-sup-'+language+'-0-value-datepicker-popup-0').val();
  var reverse_auction_date =jQuery('#edit-field-tender-reverse-auction-col-'+language+'-0-field-date-of-reverse-auction-'+language+'-0-value-datepicker-popup-0').val();
  var cancel_date = jQuery('#edit-field-tender-cancelled-collectio-'+language+'-0-field-tender-cancelled-date-'+language+'-0-value-datepicker-popup-0').val();

    if(last_sale_date < currentdate){
      jQuery('.validation').hide();
      jQuery("#edit-field-tender-last-sale-date-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
          
         
       return false;
    }

    if(opening_of_tender < currentdate){
      jQuery('.validation').hide();
      jQuery("#edit-field-opening-of-tender-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
      return false;
    }

    if(sale_of_tender_sdate < currentdate){
      jQuery('.validation').hide();
      jQuery("#edit-field-sale-of-tender-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
      return false;


    }

    if(sale_of_tender_edate < currentdate){
      jQuery('.validation').hide();
      jQuery("#edit-field-sale-of-tender-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
      return false;
    }

    if(sale_of_tender_edate < sale_of_tender_sdate){
       jQuery('.validation').hide();
      jQuery("#edit-field-sale-of-tender-"+language+"-0-value2").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>To Date Should Be Greater Then From Date</div>");
      return false;
    }

    if(corrigendum_date < currentdate){
      jQuery('.validation').hide();
      jQuery("#edit-field-corrigendum-details-"+language+"-0-field-corrigendum-date-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
      return false;
    }

    if(pqq_date < currentdate){
      jQuery('.validation').hide();
      jQuery("#edit-field-tender-cpq-collection-"+language+"-0-field-tender-cpq-date-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
      return false;
    }

    if(tech_eval_date < currentdate){
      jQuery('.validation').hide();
      jQuery("#edit-field-technical-evaluation-colle-"+language+"-0-field-technical-evaluation-date-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
      return false;
    }

    if(financial_eval_date < currentdate){
      jQuery('.validation').hide();
      jQuery("#edit-field-finance-evaluation-collect-"+language+"-0-field-finance-evaluation-date-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
      return false;
    }
    
    if(awarded_date < currentdate){
      jQuery('.validation').hide();
      jQuery("#edit-field-tender-awarded-"+language+"-0-field-date-of-award-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
      return false;
    }

    if(schedule_awarded_date < currentdate){
      jQuery('.validation').hide();
      jQuery("#edit-field-tender-awarded-"+language+"-0-field-schedule-completion-of-sup-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
      
      return false;
    }
    
     if(reverse_auction_date < currentdate){
      jQuery('.validation').hide();
      jQuery("#edit-field-tender-reverse-auction-col-"+language+"-0-field-date-of-reverse-auction-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
      return false;
    }

    if(cancel_date < currentdate){
          jQuery('.validation').hide();
          jQuery("#edit-field-tender-cancelled-collectio-"+language+"-0-field-tender-cancelled-date-"+language+"-0-value").parent().after("<div class='validation' style='color:red;margin-bottom: 20px;'>Please Select Future Date</div>");
          return false;
        }
    

  
}