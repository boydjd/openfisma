<?php
/**
 * Export to PHP Array plugin for PHPMyAdmin
 * @version 0.2b
 */

//
// Database "openfisma"
//

// openfisma.workflow
$workflow = array(
  array('id'=>'1','createdts'=>$now,'modifiedts'=>$now,'name'=>'New','description'=>'Decide whether to fix a vulnerability','isdefault'=>'1','module'=>'vulnerability','creatorid'=>$rootId),
  array('id'=>'2','createdts'=>$now,'modifiedts'=>$now,'name'=>'Won\'t Fix','description'=>'Decide not to fix the vulnerability','isdefault'=>'0','module'=>'vulnerability','creatorid'=>$rootId),
  array('id'=>'3','createdts'=>$now,'modifiedts'=>$now,'name'=>'Remediation','description'=>'Workflow to remediate vulnerabilities.','isdefault'=>'0','module'=>'vulnerability','creatorid'=>$rootId),
  array('id'=>'4','createdts'=>$now,'modifiedts'=>$now,'name'=>'New','description'=>'Default bucket for new findings','isdefault'=>'1','module'=>'finding','creatorid'=>$rootId),
  array('id'=>'5','createdts'=>$now,'modifiedts'=>$now,'name'=>'Risk Acceptance','description'=>'Workflow to accept the risks','isdefault'=>'0','module'=>'finding','creatorid'=>$rootId),
  array('id'=>'6','createdts'=>$now,'modifiedts'=>$now,'name'=>'False Positive','description'=>'Discard findings as False Positives','isdefault'=>'0','module'=>'finding','creatorid'=>$rootId),
  array('id'=>'7','createdts'=>$now,'modifiedts'=>$now,'name'=>'Remediation','description'=>'Workflow to remediate the findings','isdefault'=>'0','module'=>'finding','creatorid'=>$rootId),
  array('id'=>'8','createdts'=>$now,'modifiedts'=>$now,'name'=>'False Positive','description'=>'Bucket used for identifying false positives created by various vulnerability scanners.','isdefault'=>'0','module'=>'vulnerability','creatorid'=>$rootId)
);
