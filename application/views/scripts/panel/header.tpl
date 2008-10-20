<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td><img id="logo" src="/images/customer_logo.jpg" /></td>
		<td align="right">
                <form class="button_link" action="/user/logout">
                <input type="submit" value="Logout" /></form>
                <p><b><?php echo $this->identity;  ?></b> is currently logged in.</p>
		</td>
	</tr>
</table>
<div id="menu">
<?php 
    echo '<img src="/images/menu_line.gif" border="0">';
    if(Config_Fisma::isAllow('dashboard','read')) {
        echo 
        "<ul>
             <li>
             <h2><a href='/panel/dashboard'>Dashboard</a></h2>
             </li>
         </ul>";
        echo '<img src="/images/menu_line.gif" border="0">';
    }
    if(Config_Fisma::isAllow('asset','read')) {
        echo'<ul><li><h2><a href="/panel/asset/sub/searchbox/s/search">Assets</a></h2></li></ul>';
        echo'<img src="/images/menu_line.gif" border="0">';
    }
    if(Config_Fisma::isAllow('finding','read')) {
        echo'<ul><li><h2><a>Findings</a></h2>';
        echo'<ul>';
        if(Config_Fisma::isAllow('remediation', 'read')) {
            echo'<li><a href="/panel/remediation/sub/summary">Summary</a>';
            echo'<li><a href="/panel/remediation/sub/searchbox">Search</a>';
        }
        if(Config_Fisma::isAllow('finding','create')) {
            echo'<li><a href="/panel/finding/sub/create">Create New Finding</a>';
            echo'<li><a href="/finding/injection">Upload Spreadsheet</a>';
            echo'<li><a href="/finding/import">Upload Scan Results</a>';
        }
        echo '</ul>
             </li></ul>';
        echo '<img src="/images/menu_line.gif" border="0">';
    }
    if(Config_Fisma::isAllow('report','read')) { 
        echo'<ul><li><h2><a>Reports</a></h2>';
        echo'<ul>';
        if(Config_Fisma::isAllow('report', 'generate_poam_report' )) {
            echo'<li><a href="/panel/report/sub/poam">POA&amp;M Report</a>';
        }            
        if(Config_Fisma::isAllow('report','generate_fisma_report')) {
            echo'<li><a href="/panel/report/sub/fisma">FISMA POA&amp;M Report</a>';
        }
        if(Config_Fisma::isAllow('report','generate_general_report')) {
            echo'<li><a href="/panel/report/sub/general">General Report</a>';
        }
        if(Config_Fisma::isAllow('report','generate_system_rafs')) {
            echo'<li><a href="/panel/report/sub/rafs">Generate System RAFs</a>';
        }
        if(Config_Fisma::isAllow('report','generate_overdue_report')) {
            echo'<li><a href="/panel/report/sub/overdue">Overdue Report</a>';
        }            
        echo'</ul>
             </li></ul>';
        echo '<img src="/images/menu_line.gif" border="0">';
    }
    if(Config_Fisma::isAllow('admin','read')) {
        echo'<ul><li><h2><a>Administration</a></h2>';
        echo'<ul>';
        if(Config_Fisma::isAllow('app_configuration','update')) {
            echo'<li><a href="/panel/config">Configuration</a></li>';
        }
        if(Config_Fisma::isAllow('admin_sources','read')) {
            echo'<li><a href="/panel/source/sub/list">Finding Sources</a>';
        }
        if(Config_Fisma::isAllow('admin_networks','read')){
            echo'<li><a href="/panel/network/sub/list">Networks</a>';
        }
        if(Config_Fisma::isAllow('admin_products','read')) {
            echo'<li><a href="/panel/product/sub/list">Products</a>';
        }
        if(Config_Fisma::isAllow('admin_roles','read')){
            echo'<li><a href="/panel/role/sub/list">Roles</a>';
        }
        if(Config_Fisma::isAllow('admin_organizations','read')) {
            echo'<li><a href="/panel/Organization/sub/list">Organization</a>';
        }
        if(Config_Fisma::isAllow('admin_systems','read')) {
            echo'<li><a href="/panel/system/sub/list">Systems</a>';
        }
        if(Config_Fisma::isAllow('admin_users','read')) {
            echo'<li><a href="/panel/account/sub/list">Users</a>';
        }
        echo'</ul>
            </li></ul>';
        echo '<img src="/images/menu_line.gif" border="0">';
    }
    echo'<ul><li><h2><a>User Preferences</a></h2>
        <ul>
            <li><a href="/panel/user/sub/profile">Profile</a>
            <li><a href="/panel/user/sub/password">Change Password</a>
            <li><a href="/panel/user/sub/notifications">Notifications</a>
        </ul></ul>';
    echo '<img src="/images/menu_line.gif" border="0">';
?>
</div>
<div id="msgbar"></div>
