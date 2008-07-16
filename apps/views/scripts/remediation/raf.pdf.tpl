<?php
include ( VENDORS . DS . 'pdf'. DS . 'class.ezpdf.php');
require_once( CONTROLLERS . DS . 'components' . DS . 'rafutil.php');

    $type_name = array('NONE'=>'None',
                       'CAP'=>'Corrective Action Plan',
                       'AR' =>'Accepted Risk',
                       'FP' =>'False Positive');
    $status_name = array('NEW' => 'New',
                         'OPEN' =>'Open',
                         'EN' => 'Evidence Needed',
                         'EP' => 'Evidence Provided',
                         'ES' => 'Evidence Submitted',
                         'CLOSED' => 'Closed' );
$p = &$this->poam ; //alias

    $headerOptions = array('showHeadings'=>0,
                           'width'=>500);
    $labelOptions = array('showHeadings'=>0,
                          'shaded'=>0,
                          'showLines'=>0,
                          'width'=>500);
    $tableOptions = array('showHeadings'=>0,
                          'shaded'=>0,
                          'showLines'=>2,
                          'width'=>400
                          );
define('FONTS', VENDORS . DS . 'pdf' . DS . 'fonts');

$pdf = new Cezpdf();
$pdf->selectFont(FONTS . DS . "Helvetica.afm");

$pdf->ezSetMargins(50,110,50,50);

$pdf->ezText('Risk Analysis Form (RAF)', 20, array('justification'=>'center'));
$pdf->ezText('', 12, array('justification'=>'center')); //vertical blank
$pdf->ezText('', 12, array('justification'=>'center'));
$data = array(array('<b>Vulnerability/Weakness</b>'));
$pdf->ezTable($data, null, null, $headerOptions);
$pdf->ezText('', 12, array('justification'=>'center'));

$data = array(
array("<b>Weakness Tracking #:</b>",$p['id'],
      "<b>Date Opened:</b>",        $p['create_ts']),
array("<b>Principle Office</b>",    'FSA',
      "<b>System Acronym:</b>",     $this->system_list[$p['system_id']]),
array("<b>Finding Source</b>",      'FSA',
      "<b>Repeat finding?:</b>",    'no'),
array("<b>POA&M Type:</b>",         $type_name[$p['type']],
      "<b>POA&M Status:</b>",       $status_name[$p['status']]),
array("<b>Asset(s) Affected:</b>",  $p['asset_name'],"",""));
$pdf->ezTable($data, null, null, $labelOptions);

$data = array( array("<b>Finding:</b>",            $p['finding_data']));
$pdf->ezTable($data, null, null, $labelOptions);
$pdf->ezText('', 12, array('justification'=>'center'));
$data = array(array('<b>System Impact</b>'));
$pdf->ezTable( $data, null, null, $headerOptions);
$pdf->ezText('', 12, array('justification'=>'center'));
// the spaces are a quick hack to align the labels correctly
$data = array(
  array("<b>                                                 IMPACT LEVEL TABLE</b>"),
  array("<b>                                                                            MISSION CRITICALITY</b>")
);

$pdf->ezTable($data, null, null, $tableOptions);
$data = array(
  array("<b>DATA SENSITIVITY</b>","<b>SUPPORTIVE</b>","<b>IMPORTANT</b>","<b>CRITICAL</b>"),
  array("<b>HIGH</b>",     "low", "moderate", "high"),
  array("<b>MODERATE</b>", "low", "moderate", "moderate"),
  array("<b>LOW</b>",      "low", "low",      "low"),
);    

$pdf->ezTable($data, null, null, $tableOptions);

$pdf->ezText('', 12, array('justification'=>'center'));

$sys = new System();
$rows = $sys->find($this->poam['system_id']);
$act_owner = $rows->current()->toArray();
$sensitivity = calSensitivity(array($act_owner['confidentiality'],
                     $act_owner['availability'],
                     $act_owner['integrity']) );

$availability = &$act_owner['availability'];

$impact = calcImpact($sensitivity, $availability);
$threat_likelihood = calcThreat($this->poam['threat_level'], 
                                 $this->poam['cmeasure_effectiveness']);

$data = array(
  array('<b>Mission Criticality:</b>',      $act_owner['criticality'],'',''),
  array('<b>Criticality Justification:</b>',$act_owner['criticality_justification'],'',''),
  array('<b>Data Sensitivity:</b>',         $sensitivity,'',''),
  array('<b>Sensitivity Justification:</b>',$act_owner['sensitivity_justification'],'',''),
  array('<b>Overall Impact Level:</b>',     $impact,'','')
);
$pdf->ezTable($data, null, null, $labelOptions);
$pdf->ezText('', 12, array('justification'=>'center'));

$data = array(array('<b>Threat(s) and Countermeasure(s)</b>'));
$pdf->ezTable( $data, null, null, $headerOptions);
$pdf->ezText('', 12, array('justification'=>'center'));

$data = array(
  array("<b>                                               THREAT LIKELIHOOD TABLE</b>"),
  array("<b>                                                                              COUNTERMEASURE</b>")
);
$pdf->ezTable($data, null, null, $tableOptions);
$data = array(
  array("<b>THREAT SOURCE</b>","<b>LOW</b>","<b>MODERATE</b>","<b>HIGH</b>"),
  array("<b>HIGH</b>",     "high",     "moderate", "low"),
  array("<b>MODERATE</b>", "moderate", "moderate", "low"),
  array("<b>LOW</b>",      "low",      "low",      "low"),
);    
$pdf->ezTable($data, null, null, $tableOptions);
$pdf->ezText('', 12, array('justification'=>'center'));

$data = array(
  array('<b>Specific Countermeasures:</b>',     $p['cmeasure'],'',''),
  array('<b>Countermeasure Effectiveness:</b>', $p['cmeasure_effectiveness'],'',''),
  array('<b>Effectiveness Justification:</b>',  $p['cmeasure_justification'],'',''),
  array('<b>Threat Source(s):</b>',             $p['threat_source'],'',''),
  array('<b>Threat Impact:</b>',                $p['threat_level'],'',''),
  array('<b>Impact Level Justification:</b>',   $p['threat_justification'],'',''),
  array('<b>Overall Threat Likelihood:</b>',    $threat_likelihood,'','')           
);
$pdf->ezTable($data, null, null, $labelOptions);
$pdf->ezText('', 12, array('justification'=>'center'));


$data = array(array('<b>Risk Level</b>'));
$pdf->ezTable($data, null, null, $headerOptions);
$pdf->ezText('', 12, array('justification'=>'center'));

$data = array(
  array("<b>                                                  RISK LEVEL TABLE</b>"),
  array("<b>                                                                              IMPACT</b>")
);
$pdf->ezTable($data, null, null, $tableOptions);
                                                     
$data = array(
  array("<b>LIKELIHOOD</b>","<b>LOW</b>","<b>MODERATE</b>","<b>HIGH</b>"),
  array("<b>HIGH</b>",     "low",  "moderate", "high"),
  array("<b>MODERATE</b>", "low",  "moderate", "moderate"),
  array("<b>LOW</b>",      "low",  "low",      "low"),
);    
$pdf->ezTable($data, null, null, $tableOptions);
$pdf->ezText('', 12, array('justification'=>'center'));

$overall_impact = calcImpact($threat_likelihood, $impact);

$data = array(
  array('<b>High:</b>', 'Strong need for corrective action'),
  array('<b>Moderate:</b>', 'Need for corrective action within a reasonable time period.'),
  array('<b>Low:</b>', 'Authorizing official may correct or accept the risk'),
  array('<b>Overall Risk Level:</b>', $overall_impact)       
);

$pdf->ezTable($data, null, null, $labelOptions);
$pdf->ezText('', 12, array('justification'=>'center'));
// Mitigration Strategy section
$data = array(array('<b>Mitigation Strategy</b>'));
$pdf->ezTable($data, null, null, $headerOptions);
$pdf->ezText('', 12, array('justification'=>'center'));

$data = array(
  array('<b>Recommendation(s):</b>', $p['action_suggested']),
  array('<b>Course of Action:</b>', $p['action_planned']),
  array('<b>Est. Completion Date:</b>', $p['action_est_date']),    
);

$pdf->ezTable($data, null, null, $labelOptions);

$pdf->ezText('', 12, array('justification'=>'center'));
// Accepted Risk section (conditional on poam type)
if ($p['type'] == 'AR') {
  $data = array(array('<b>AR - (Recommend accepting this low risk)</b>'));
  $pdf->ezTable($data, null, null, $headerOptions);
  $pdf->ezText('', 12, array('justification'=>'center'));
  
  $pdf->ezText('<b>Vulnerability:</b>', 12, null);
  $pdf->ezText($p['finding_data'], 12, null);
  $pdf->ezText('<b>Business Case Justification for accepted low risk:</b>', 12, null);
  $pdf->ezText($p['action_planned'], 12, null);
  $pdf->ezText('<b>Mitigating Controls:</b>', 12, null);
  $pdf->ezText($p['cmeasure'], 12, null);                              
  $pdf->ezText('', 12, array('justification'=>'center'));
}

// Endorsement of Risk Level Analysis section
$data = array(array('<b>Endorsement of Risk Level Analysis</b>'));
$pdf->ezTable($data, null, null, $headerOptions);
$pdf->ezText('', 12, array('justification'=>'center'));

$data = array(
  array('Concur __ ', 'Non-Concur __ ','_____________________________________________','___/___/______'),
  array('',           '',              'Business Owner/Representative',                'Date')
);

$pdf->ezTable($data,
              null,
              null,
              $labelOptions
             );

$pdf->ezText('', 12, array('justification'=>'center'));
$footer = 'WARNING: This report is for internal, official use only.  This report contains sensitive computer security related information. Public disclosure of this information would risk circumvention of the law. Recipients of this report must not, under any circumstances, show or release its contents for purposes other than official action. This report must be safeguarded to prevent improper disclosure. Staff reviewing this document must hold a minimum of Public Trust Level 5C clearance.';
$pdf->ezText($footer, 9, array('justification'=>'left'));
echo $pdf->ezOutput();
