Drupal.behaviors.complaint = {attach: function(context, settings) {  jQuery('#complaint-site-form').ajaxComplete(function(event, xhr, settings) {
if(jQuery( "#edit-assigned-to option:selected" ).val()==1 || jQuery( "#edit-assigned-to option:selected" ).val()==2){
	 jQuery("#grievance_related_replace").show();
	 jQuery("#category_replace").show();
} else {
	jQuery("#grievance_related_replace").hide();
	jQuery("#category_replace").hide();
}
});
}
}

jQuery( document ).ready(function() {
  if(jQuery( "#edit-assigned-to option:selected" ).val()==1 || jQuery( "#edit-assigned-to option:selected" ).val()==2){
	 jQuery("#grievance_related_replace").show();
	 jQuery("#category_replace").show();
} else {
	jQuery("#grievance_related_replace").hide();
	jQuery("#category_replace").hide();
}
});


