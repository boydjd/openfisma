<div class="barleft">
<div class="barright">
<p><b>Dashboard</b>
</div>
</div>
<table width="95%" align="center"  border="0" cellpadding="10" class="tipframe">
	<tr>
		<td  align="left"><b>Alerts </b>
			<ul>
			<!-- Awaiting Mitigation Strategy -->
			<li>There are <b><a href="<?php echo $this->url ?>OPEN"><?PHP echo $this->alert['OPEN'];?></a></b> finding(s) awaiting a mitigation strategy and approval.</li>
			<!-- Awaiting Evidence -->
			<li>There are <b><a href="<?php echo $this->url ?>EN"><?PHP echo $this->alert['EN'];?></a></b> finding(s) awaiting evidence.
            <!-- Overdue Awaiting Evidence -->
			<li>There are <b><a href="<?php echo $this->url ?>EO"><?PHP echo $this->alert['EO'];?></a></b> overdue finding(s) awaiting evidence.
            </ul>
		</td>
	</tr>
</table>

<?php if ( !empty( $this->alert['TOTAL'] ) ) { ?>
<table width="95%" align="center" border="0" cellpadding="0" cellspacing="0" class="tipframe">
	<tr><td colspan="3"  align="left"><b>&nbsp;&nbsp;&nbsp;Management Overview </b></td></tr>
    <tr>
      <td width="33%"  align="center">
        <?php echo $this->partial('dashboard/chart.tpl', array(
                    'source_url'=> '/zfentry.php/dashboard/totalstatus/format/xml/type/pie',
                    'width'=>380,
                    'height'=>220 ) );
        ?>
      </td>
      <td width="34%"  align="center">
        <?php echo $this->partial('dashboard/chart.tpl', array(
                    'source_url'=> urlencode(
                        '/zfentry.php/dashboard/totalstatus/format/xml/type/3d column'),
                    'width'=>200,
                    'height'=>220 ) );
        ?>
      </td>
      <td width="33%"  align="center">
        <?php echo $this->partial('dashboard/chart.tpl', array(
                    'source_url'=> '/zfentry.php/dashboard/totaltype/format/xml',
                    'width'=>380,
                    'height'=>220 ));
        ?>
      </td>
    </tr>
    <tr>
      <td width="33%"  align="center">Current Distribution of<br>POA&amp;M Status</td>
      <td width="34%"  align="center">Current POA&amp;M Item<br>Totals by Status</td>
      <td width="33%"  align="center">Current Distribution of<br>POA&amp;M Type</td>
    </tr>
</table>

<?php } ?>


