<?php
require_once( CONTROLLERS . DS . 'components' . DS . 'rafutil.php');

    $type_name = array('NONE'=>'None',
                       'CAP'=>'Corrective Action Plan',
                       'AR' =>'Accepted Risk',
                       'FP' =>'False Positive');
    $status_name = array('NEW' => 'New',
                         'OPEN' =>'Open',
                         'EN' => 'Evidence Needed',
                         'EO' => 'Evidence Overdue',
                         'EP(SSO)' =>'Evidence Provided to SSO',
                         'EP(S&P)' => 'Evidence Provided to S&P',
                         'ES' => 'Evidence Submitted',
                         'CLOSED' => 'Closed' );

$cellidx_lookup['HIGH']['LOW']          = 0;
$cellidx_lookup['HIGH']['MODERATE']     = 1;
$cellidx_lookup['HIGH']['HIGH']         = 2;
$cellidx_lookup['MODERATE']['LOW']      = 3;
$cellidx_lookup['MODERATE']['MODERATE'] = 4;
$cellidx_lookup['MODERATE']['HIGH']     = 5;
$cellidx_lookup['LOW']['LOW']           = 6;
$cellidx_lookup['LOW']['MODERATE']      = 7;
$cellidx_lookup['LOW']['HIGH']          = 8;

?>
<html>
<head>
    
<title>Risk Analysis Form (RAF)</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style>
p.pageHeader {
  font-size: 20pt;
  font-family: sans-serif; 
  text-align: center;
  margin: auto auto;
}

div.raf {
  text-align: center;
  margin: auto;
  width: 900px;
}

div.rafHeader {
  padding: 2px;
  border: 2px solid black;
  width: 100%;
  text-align: left;
  font-family: sans-serif;
  font-size: 10pt;
  margin-top: 20px;
  margin-bottom: 20px;
}

table.rafContent {
  width: 100%;
}

table.rafContent td {
  vertical-align: top;
  width: 25%;
  font-family: sans-serif;
  font-size: 10pt;
}

table.rafImpact {
  margin: auto auto;
  border: none;
  border-collapse: collapse;
  width: 80%;
  color: black;
}

table.rafImpact td {
  padding: 2px;
  border: 2px solid black;
}

</style>
</head>
<body>

<p style="text-align:right"><button onclick="javascript:window.print();">Print</button> </p>


<div class="raf">
<table class="rafContent">
  <tr>
    <td colspan="4">
      <p class="pageHeader">Risk Analysis Form (RAF)</p>
    </td>
  </tr>
  <tr>
    <td colspan="4">
      <div class="rafHeader">
        <b>Vulnerability/Weakness</b>
      </div>
    </td>
  </tr>
  <tr>
    <td width="25%"><b>Weakness Tracking #:</b></td>
    <td width="25%"><?php echo $this->poam['id']; ?></td>
    <td width="25%"><b>Date Opened:</b></td>
    <td width="25%"><?php echo $this->poam['create_ts'];?></td>
  </tr>
  <tr>
    <td><b>Principle Office</b></td>
    <td>FSA</td><!-- hard coded for FSA release -->
    <td><b>System Acronym:</b></td>
    <td><?php echo $this->system_list[$this->poam['system_id']];?></td>
  </tr>
  <tr>
    <td><b>Finding Source</b></td>
    <td><?php echo $this->source_list[$this->poam['source_id']];?></td>
    <td><b>Repeat finding?:</b></td>
    <td><?php echo $this->poam['is_repeat']?'yes':'no';?></td>
  </tr>
  <tr>
    <td><b>POA&amp;M Type:</b></td>
    <td><?php echo $type_name[$this->poam['type']];?></td>
    <td><b>POA&amp;M Status:</b></td>
    <td><?php echo $status_name[$this->poam['status']];?></td>
  </tr>
  <tr>
    <td><b>Asset(s) Affected:</b></td>
    <td><?php echo $this->poam['asset_name'];?></td>
    <td><b>&nbsp;</b></td>
    <td >&nbsp;</td>
  </tr>
  <tr>
    <td><b>Finding:</b></td>
    <td colspan="3"><?php echo $this->poam['finding_data'];?></td>
  </tr>
  </tr>
  <tr>
    <td colspan="4">
      <div class="rafHeader">
        <b>System Impact</b>
      </div>
    </td>
  </tr>
    <?php
        $sys = new System();
        $rows = $sys->find($this->poam['system_id']);
        $act_owner = $rows->current()->toArray();
        $sensitivity = calSensitivity(array($act_owner['confidentiality'],
                             $act_owner['availability'],
                             $act_owner['integrity']) );

        $availability = &$act_owner['availability'];

        $impact = calcImpact($sensitivity, $availability);


        echo $this->partial('remediation/raf_impact.tpl',
                    array('act_owner'=>$act_owner,
                          'poam'=>&$this->poam,
                          'table_lookup'=>&$cellidx_lookup,
                          'impact'=>$impact,
                          'sensitivity'=>$sensitivity)
                     );
    ?>
  <tr>
    <td colspan="4">
      <div class="rafHeader">
        <b>Threat(s) and Countermeasure(s)</b>
      </div>
    </td>
  </tr>
    <?php
        $threat_likelihood = calcThreat($this->poam['threat_level'], 
                                         $this->poam['cmeasure_effectiveness']);
        echo $this->partial('remediation/raf_tl.tpl',
                    array('act_owner'=>$act_owner,
                          'poam'=>&$this->poam,
                          'table_lookup'=>&$cellidx_lookup,
                          'threat_likelihood'=>&$threat_likelihood)
                     );
    ?>

  <tr>
    <td colspan="4">
      <div class="rafHeader">
        <b>Risk Level</b>
      </div>
    </td>
  </tr>

    <?php
        echo $this->partial('remediation/raf_risk.tpl',
                    array('act_owner'=>$act_owner,
                          'impact'=>&$impact,
                          'table_lookup'=>&$cellidx_lookup,
                          'threat_likelihood'=>&$threat_likelihood)
                     );
    ?>
    </td>
  </tr>
  <tr>
    <td colspan="4">
      <div class="rafHeader">
        <b>Mitigation Strategy</b>
      </div>
    </td>
  </tr>
  <tr>
    <td><b>Recommendation(s):</b></td>
    <td colspan="3"><?php echo $this->poam['action_suggested'];?></td>
  </tr>
  <tr>
    <td><b>Course of Action:</b></td>
    <td colspan="3"><?php echo $this->poam['action_planned'];?></td>
  </tr>
  <tr>
    <td><b>Est. Completion Date:</b></td>
    <td colspan="3"><?php echo $this->poam['action_est_date'];?></td>
  </tr>
  <?php if( $this->poam['type'] == 'AR' ) { ?>
  <tr>
    <td colspan="4">
      <div class="rafHeader">
        <b>AR - (Recommend accepting this low risk)</b>
      </div>
    </td>
  </tr>
  <tr>
    <td colspan="4">
      <b>Vulnerability:</b>
    </td>
  </tr>
  <tr>
    <td colspan="4">
      <?php echo $this->poam['finding_data']; ?>
    </td>
  </tr>
  <tr>
    <td colspan="4">
      <b>Business Case Justification for accepted low risk:</b>
    </td>
  </tr>
  <tr>
    <td colspan="4">
      <?php echo $this->poam['action_planned']; ?>
    </td>
  </tr>
  <tr>
    <td colspan="4">
      <b>Mitigating Controls:</b>
    </td>
  </tr>
  <tr>
    <td colspan="4">
      <?php echo $this->poam['cmeasure_effectiveness']; ?>
    </td>
  </tr>
<?php } ?>
  <tr>
    <td colspan="4">
      <div class="rafHeader">
        <b>Endorsement of Risk Level Analysis</b>
      </div>
    </td>
  </tr>
  <tr>
    <td colspan="1">
      Concur __&nbsp;&nbsp;Non-Concur __
    </td>
    <td colspan="2">
      _____________________________________________<br>Business Owner/Representative
    </td>
    <td>
      ___/___/______<br>Date
    </td>
  </tr>
  <tr>
    <td colspan="4">
     WARNING: This report is for internal, official use only.  This report contains sensitive computer security related information. Public disclosure of this information would risk circumvention of the law. Recipients of this report must not, under any circumstances, show or release its contents for purposes other than official action. This report must be safeguarded to prevent improper disclosure. Staff reviewing this document must hold a minimum of Public Trust Level 5C clearance.
    </td>
  </tr>
</table>
</div>

</body>
</html>
