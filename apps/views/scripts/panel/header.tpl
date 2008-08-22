<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td><img id="logo" src="/images/customer_logo.jpg" /></td>
		<td align="right"><ul class="loginfo">
            <?php if ( 'ldap' != readSysConfig('auth_type') ) { ?>
                <li><form class="button_link" action="/panel/user/sub/pwdchange">
                <input type="submit" value="Change Password" /></form>&nbsp;
            <?php } ?>
                <form class="button_link" action="/user/logout">
                <input type="submit" value="Logout" /></form><br>
				<li><b><?php echo $this->identity;  ?></b> is currently logged in 
			</ul></td>
	</tr>
</table>
<div id="menu">
<?php 
    echo '<img src="/images/menu_line.gif" border="0">';
    if(isAllow('dashboard','read')) {
        echo 
        "<ul>
             <li>
             <h2><a href='/panel/dashboard'>Dashboard</a></h2>
             </li>
         </ul>";
        echo '<img src="/images/menu_line.gif" border="0">';
    }
    if(isAllow('asset','read')) {
        echo 
        "<ul>
             <li>
             <h2><a href='/panel/asset/sub/searchbox/s/search'>Assets</a></h2>
             </li>
         </ul>";
        echo '<img src="/images/menu_line.gif" border="0">';
    }
    if(isAllow('finding','read')) {
        echo 
        '<ul>
            <li> 
            <h2><a>Finding</a></h2>
            <ul>';
        if(isAllow('finding','create')) {
            echo "\n",'<li><a href="/panel/finding/sub/create">New Finding</a>
                <li><a href="/finding/injection">Spreadsheet Upload</a>
                <li><a href="/finding/import">Upload Scan Results</a>';
        }
        echo '</ul>
             </li></ul>';
        echo '<img src="/images/menu_line.gif" border="0">';
    }
    if(isAllow('remediation','read')) {
        echo '<ul><li>
              <h2><a href="/panel/remediation/sub/index/">Remediation</a></h2>
              <ul>
              <li><a href="/panel/remediation/sub/summary">Remediation Summary</a>
              <li><a href="/panel/remediation/sub/searchbox">Remediation Search</a>
              </ul>
              </li></ul>';
        echo '<img src="/images/menu_line.gif" border="0">';
    }
    if(isAllow('report','read')) { 
        echo "\n",'<ul><li><h2><a>Reports</a></h2>
              <ul>';
        if(isAllow('report', 'generate_poam_report' )) {
            echo "\n",'<li><a href="/panel/report/sub/poam">POA&amp;M Report</a>';
        }            
        if(isAllow('report','generate_fisma_report')) {
            echo "\n",'<li><a href="/panel/report/sub/fisma">FISMA POA&amp;M Report</a>';
        }
        if(isAllow('report','generate_general_report')) {
            echo "\n",'<li><a href="/panel/report/sub/general">General Report</a>';
        }
        if(isAllow('report','generate_system_rafs')) {
            echo "\n",'<li><a href="/panel/report/sub/rafs">Generate System RAFs</a>';
        }
        if(isAllow('report','generate_overdue_report')) {
            echo "\n",'<li><a href="/panel/report/sub/overdue">Overdue Report</a>';
        }            
        echo'</ul>
             </li></ul>';
        echo '<img src="/images/menu_line.gif" border="0">';
    }
    if(isAllow('admin','read')) {
        echo'<ul><li><h2><a>Administration</a></h2>';
        echo'<ul>';
        if(isAllow('app_configuration','update')) {
            echo'<li><a href="/panel/config">Configuration</a></li>';
        }
        if(isAllow('admin_sources','read')) {
            echo'<li><a href="/panel/source/sub/list">Finding Sources</a>';
        }
        if(isAllow('admin_networks','read')){
            echo'<li><a href="/panel/network/sub/list">Networks</a>';
        }
        if(isAllow('admin_products','read')) {
            echo'<li><a href="/panel/product/sub/list">Products</a>';
        }
        if(isAllow('admin_roles','read')){
            echo'<li><a href="/panel/role/sub/list">Roles</a>';
        }
        if(isAllow('admin_system_groups','read')) {
            echo'<li><a href="/panel/sysGroup/sub/list">System Group</a>';
        }
        if(isAllow('admin_systems','read')) {
            echo'<li><a href="/panel/system/sub/list">Systems</a>';
        }
        if(isAllow('admin_users','read')) {
            echo'<li><a href="/panel/account/sub/list">Users</a>';
        }
        echo'</ul>
            </li></ul>';
        echo '<img src="/images/menu_line.gif" border="0">';
    }
    /*
    if(isAllow('vulnerability','read')) {
        echo'<ul><li><h2><a href="/mainPanel.php?panel=association" >Vulnerability</a></h2>';
        echo'<ul><li><a href="#">Asset Dashboard</a></li>';
        if(isAllow('vulnerability','create')) {
            echo'<li><a href="#">Create an Asset</a></li>';
        }
        echo'</ul>
        </li></ul>';
        echo '<img src="/images/menu_line.gif" border="0">';
    }*/
?>
&nbsp;
</div>
<div id="msgbar"></div>
