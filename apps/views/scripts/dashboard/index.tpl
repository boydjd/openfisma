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
            <li>There are <b><a href="<?php echo $this->url ?>NEW"><?PHP echo $this->alert['NEW'];?></a></b> finding(s) awaiting a mitigation strategy and <b><a href="<?php echo $this->url;?>OPEN"><?PHP echo $this->alert['OPEN'];?></a></b> awaiting approval.</li>
            <!-- Awaiting Evidence -->
            <li>There are <b><a href="<?php echo $this->url ?>EN"><?PHP echo $this->alert['EN'];?></a></b> finding(s) awaiting evidence.
            <!-- Overdue Awaiting Evidence -->
            <li>There are <b><a href="<?php echo $this->url ?>EO"><?PHP echo $this->alert['EO'];?></a></b> overdue finding(s) awaiting evidence.
            </ul>
        </td>
    </tr>
</table>

<table width="95%" align="center"  border="0" cellpadding="10" class="tipframe">
    <tr>
        <td  align="left"><b>Last Login</b>
            <ul>
            <li>Last Logged in at <b><?php echo $this->lastLogin->toString("D, M j H:i");?></b></li>
            <li>From Ip address <b><?php echo $this->lastLoginIp;?></b></li>
            <li>There were <b><?php echo $this->failureCount;?></b> bad login attempts since your last login.</li>
            </ul>
        </td>
    </tr>
</table>

<?php
if (isset($this->notifications)) {
?>
    <table width="95%" align="center"  border="0" cellpadding="10" class="tipframe">
        <tr>
            <td  align="left">
                <b>Notifications</b>
                <p>
                    You have new notifications that you have not received in e-mail yet.
                    Click <a href="<?php echo $this->dismissUrl; ?>">here</a> to dismiss these notifications.
                </p>
                <ol>
                <?php
                foreach($this->notifications as $notification) {
                ?>
                    <li><b><?php echo $notification['event_text']; ?></b>&nbsp;at&nbsp;<b><?php echo $notification['timestamp']; ?></b></li>
                <?php
                }
                ?>
                </ol>
            </td>
        </tr>
    </table>
<?php
}
?>

<?php if ( !empty( $this->alert['TOTAL'] ) ) { ?>
<table width="95%" align="center" border="0" cellpadding="0" cellspacing="0" class="tipframe">
    <tr><td colspan="3"  align="left"><b>&nbsp;&nbsp;&nbsp;Management Overview </b></td></tr>
    <tr>
      <td width="33%"  align="center">
        <?php echo $this->partial('/dashboard/chart.tpl', array(
                    'source_url'=> '/dashboard/totalstatus/format/xml/type/pie',
                    'width'=>380,
                    'height'=>220 ) );
        ?>
      </td>
      <td width="34%"  align="center">
        <?php echo $this->partial('/dashboard/chart.tpl', array(
                    'source_url'=> urlencode(
                        '/dashboard/totalstatus/format/xml/type/3d column'),
                    'width'=>200,
                    'height'=>220 ) );
        ?>
      </td>
      <td width="33%"  align="center">
        <?php echo $this->partial('/dashboard/chart.tpl', array(
                    'source_url'=> '/dashboard/totaltype/format/xml',
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


