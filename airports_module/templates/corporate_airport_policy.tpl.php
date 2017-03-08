	<?php
/**
 * @file
 *   hhh
 */
$terms = taxonomy_get_term_by_name('Policy On Airports');
$tid = key($terms);
$functions = AAI::getInstance();
$nids = $functions->aaiGetBasicPageNids($tid);
$lang = $functions->aaiCurrentLang(); 
$body_files="";
if($lang == "hi")
{
	$nids = array(1586);
}
 if(count($nids) > 0){
 foreach($nids as $nid) {
 $node_info = node_load($nid);
 $content['title'] = $node_info->title; 
if(isset($node_info->field_upload_document['und'])){
  $files_array = $node_info->field_upload_document['und'];
  $body_files = "<ul class='airport-policy-list'>"; 
  foreach($files_array as $file){  
	$filename = "/sites/default/files/basic_page_files/".$file['filename'];
	$description = $file['description'];		
	$body_files .= "<li><a href=".$filename." target='_blank'>".$description."</a></li>";		
  }  
  $body_files .= "</ul>";  
 }
 $content['body'] = $body_files;
}
}
// echo "<div><h1>".$content['title']."</h1></div>";
echo "<div>".$content['body']."</div>";
?>