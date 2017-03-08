<?php
/**
 * @file
 *  Corporate Organizational Structure Page
 */
$terms = taxonomy_get_term_by_name('Corporate Organization Structure');
$tid = key($terms);

$functions = AAI::getInstance();
$nids = $functions->aaiGetBasicPageNids($tid);
$lang = $functions->aaiCurrentLang();

if($lang == "hi")
{
	$nids = array(1585);
}
foreach($nids as $nid) {
  $node_info = node_load($nid);
  $content['title'] = $node_info->title;
  $content['body'] = $node_info->body['dbplus_undo(relation)'][0]['value'];
  $content['uploadedfiles'] =  file_create_url($node_info->field_upload_document['und'][0]['uri']);
 }

//echo "<div><h1>".$content['title']."</h1></div>";
echo "<div>".$content['body']."</div>";
echo"<div id='uploadeddata'><img id ='thumbofogstructure' src=".$content['uploadedfiles']." width='866px' height='600px' alt=".$content['uploadedfiles'].">(Click On Image To Zoom)</div>"
?>
