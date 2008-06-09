{* BEGIN EXTRACT FROM header.tpl *}
<html>
<head>
	
<title>{$title}</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
{literal}
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
{/literal}
</head>

<!--
-- The rpdata data set consists of three data blocks:
-- [0] - a single POAM statistics row
-- [1] - a list of vulnerability description rows
-- [2] - a list of affected server/asset rows
-->

<div class="raf">
<br>
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
    <td width="25%"><b>{$raf_lang[1][0]}</b></td>
    <td width="25%">{$WVTNO}</td>
    <td width="25%"><b>{$raf_lang[1][4]}</b></td>
    <td width="25%">{$rpdata[0].dt_created}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[1][2]}</b></td>
    <td>FSA</td><!-- hard coded for FSA release -->
    <td><b>{$raf_lang[1][3]}</b></td>
    <td>{$rpdata[0].s_nick}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[1][7]}</b></td>
    <td>{$rpdata[0].source_name}</td>
    <td><b>{$raf_lang[1][9]}</b></td>
    <td>{$rpdata[0].is_repeat}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[1][11]}</b></td>
    <td>{$rpdata[0].poam_type}</td>
    <td><b>{$raf_lang[1][12]}</b></td>
    <td>{$rpdata[0].poam_status}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[1][13]}</b></td>
    <td>{$rpdata[0].asset_name}</td>
    <td><b>&nbsp;</b></td>
    <td >&nbsp;</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[1][5]}</b></td>
    <td colspan="3">{$rpdata[0].finding_data}</td>
  </tr>
  <tr>
    <td colspan="4">
      <div class="rafHeader">
        <b>System Impact</b>
      </div>
    </td>
  </tr>
  <tr>
    <td colspan="4">{include file="raf_impact_table.tpl"}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[2][0]}</b></td>
    <td colspan="3">{$rpdata[0].system_criticality}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[2][1]}</b></td>
    <td colspan="3">{$rpdata[0].s_c_just}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[2][2]}</b></td>
    <td colspan="3">{$rpdata[0].data_sensitivity}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[2][3]}</b></td>
    <td colspan="3">{$rpdata[0].s_s_just}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[2][4]}</b></td>
    <td colspan="3">{$rpdata[0].impact}</td>
  </tr>
  <tr>
    <td colspan="4">
      <div class="rafHeader">
        <b>Threat(s) and Countermeasure(s)</b>
      </div>
    </td>
  </tr>
  <tr>
    <td colspan="4">{include file="raf_TL_table.tpl"}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[3][0]}</b></td><td colspan="3">{$rpdata[0].cm}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[3][1]}</b></td><td colspan="3">{$rpdata[0].cm_eff}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[3][2]}</b></td><td colspan="3">{$rpdata[0].cm_just}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[3][4]}</b></td><td colspan="3">{$rpdata[0].t_source}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[3][3]}</b></td><td colspan="3">{$rpdata[0].t_level}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[3][5]}</b></td><td colspan="3">{$rpdata[0].t_just}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[3][6]}</b></td><td colspan="3">{$rpdata[0].threat_likelihood}</td>
  </tr>
  <tr>
    <td colspan="4">
      <div class="rafHeader">
        <b>Risk Level</b>
      </div>
    </td>
  </tr>
  <tr>
    <td colspan="4">{include file="raf_risklevel_table.tpl"}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[4][1]}</b></td>
    <td colspan="3">{$raf_lang[4][2]}</td></tr><tr>
  </tr>
  <tr>
    <td><b>{$raf_lang[4][3]}</b></td>
    <td colspan="3">{$raf_lang[4][4]}</td></tr><tr>
  </tr>
  <tr>
    <td><b>{$raf_lang[4][5]}</b></td>
    <td colspan="3">{$raf_lang[4][6]}</td>
  </tr>
  <tr>
    <td width="{$table_width}"><b>{$raf_lang[4][0]}</b></td><td colspan="3">{$overall_risk}</td>
  </tr>
  <tr>
    <td colspan="4">
      <div class="rafHeader">
        <b>Mitigation Strategy</b>
      </div>
    </td>
  </tr>
  <tr>
    <td><b>{$raf_lang[5][0]}</b></td>
    <td colspan="3">{$rpdata[0].act_sug}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[5][1]}</b></td>
    <td colspan="3">{$rpdata[0].act_plan}</td>
  </tr>
  <tr>
    <td><b>{$raf_lang[5][2]}</b></td>
    <td colspan="3">{$rpdata[0].poam_action_date_est}</td>
  </tr>
{if $rpdata[0].poam_type_code == 'AR'}
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
      {$rpdata[0].finding_data}
    </td>
  </tr>
  <tr>
    <td colspan="4">
      <b>Business Case Justification for accepted low risk:</b>
    </td>
  </tr>
  <tr>
    <td colspan="4">
      {$rpdata[0].act_plan}
    </td>
  </tr>
  <tr>
    <td colspan="4">
      <b>Mitigating Controls:</b>
    </td>
  </tr>
  <tr>
    <td colspan="4">
      {$rpdata[0].cm}
    </td>
  </tr>
{/if}  
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
{if $pdf==false}
  <tr>
    <td align="right" colspan="4">
      <input type="button" name="Button" value="Print" onclick="javascript:window.print();">
      <input type="button" name="Button" value="Export to PDF" onclick="javascript:window.location='craf.php?poam_id={$poam_id}';">
    </td>
  </tr>
{/if}
  <tr>
    <td colspan="4">{$warn_footer}</td>
  </tr>
</table>
</div>
