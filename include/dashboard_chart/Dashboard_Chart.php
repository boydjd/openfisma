<?PHP

class Dashboard_Chart
{


  function InsertChart($xml_file, $w , $h) 
	{
		$html ="<OBJECT classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' codebase='https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0' WIDTH='$w' HEIGHT='$h' id='charts' ALIGN=''> ";
		$html.="<PARAM NAME='movie' VALUE='flash/charts.swf?license=I1XFXZQRXRQL.NS5T4Q79KLYCK07EK&xml_source=$xml_file'>";
		$html.="<PARAM NAME='quality' VALUE='high'> <PARAM NAME='bgcolor' VALUE='#666666'> ";
		$html.="<PARAM NAME='wmode' VALUE='transparent'> ";
		$html.="<EMBED src='flash/charts.swf?license=I1XFXZQRXRQL.NS5T4Q79KLYCK07EK&xml_source=$xml_file' ";
		$html.="quality='high' bgcolor='#FFFFFF' WIDTH='$w' HEIGHT='$h' NAME='charts' ALIGN='' wmode='transparent' ";
		$html.="TYPE='application/x-shockwave-flash' PLUGINSPAGE='https://www.macromedia.com/go/getflashplayer'></EMBED></OBJECT>";
		return $html;
	}

	function new_poam($system_ids = NULL) 
	{

		// set up the initial query
		$sql_none = "";

		// use query dependent on presence of system id
		if ($system_ids) { $sql_none = "select count(*) as v_none from " . TN_POAMS . " where (poam_type='NONE' and poam_action_owner IN ($system_ids))"; }
		else { $sql_none = "select count(*) as v_none from " . TN_POAMS . " where poam_type='NONE' " ; }

//		print $sql_none;

		// execute the query
		$result_none  = mysql_query($sql_none) or die("Query failed: " . mysql_error());
		$row_none = mysql_fetch_array($result_none, MYSQL_ASSOC);
		$v_none = $row_none['v_none'] ; 

		// set up the alert message
		if ($v_none > 0)
			$msg = "<li>You have <font color='red'> $v_none </font> new POA&M items to update</li>" ;
		else
			$msg = "" ;
		
		// return the alert message
		return $msg ;
	}

	function new_cap($system_ids = NULL)
	{

		// set up the initial query
		$sql_none = "";

		// use the query dependent on the presence of system_id
		if ($system_ids) { $sql_none = "select count(*) as v_none from " . TN_POAMS . " where (poam_type='CAP' and poam_status='OPEN' and poam_action_status='NONE' and poam_action_owner IN ($system_ids))"; }
		else {

			//$sql_none = "select count(*) as v_none from " . TN_POAMS where poam_type='CAP' and poam_status='OPEN' and poam_action_is_approved=0 " ;
			$sql_none = "select count(*) as v_none from " . TN_POAMS . " where poam_type='CAP' and poam_status='OPEN' and poam_action_status = 'NONE' " ;

		}

		// execute the query
		$result_none  = mysql_query($sql_none) or die("Query failed: " . mysql_error());
		$row_none = mysql_fetch_array($result_none, MYSQL_ASSOC);
		$v_none = $row_none['v_none'] ; 

		// set up the alert message
		if ($v_none > 0)
			$msg = "<li>You have <font color='red'> $v_none </font> new CAP items to approve </li>" ;
		else
			$msg = "" ;
		
		// return the alert message
		return $msg ;
	}


	function review_pkg($Role_ID)
    {
       //$sql_none = "select count(*) as v_none from " . TN_POAMS as p , POAM_EVIDENCE as e where p.poam_type!='NONE' and p.poam_status='EP' and e.ev_accepted=0 and p.poam_id=e.poam_id " ;
              //if user has role sso, it would be e.ev_sso_evaluation = 'NONE'
       //if they have role of FSA, then it would be (e.ev_sso_evaluation = 'APPROVED' AND e.ev_fsa_evaluation = 'NONE')
       //if they have role of IV&V it would be (e.ev_sso_evaluation = 'APPROVED' AND e.ev_fsa_evaluation = 'APPROVED')
		if ( $Role_ID == 3 ) //sso
			$sql_none = "select count(*) as v_none from " . TN_POAMS . " as p , POAM_EVIDENCE as e where p.poam_type!='NONE' and p.poam_status='EP' and e.ev_sso_evaluation = 'NONE' and p.poam_id=e.poam_id " ;
		else if ( $Role_ID == 4 ) //FSA
			$sql_none = "select count(*) as v_none from " . TN_POAMS . " as p , POAM_EVIDENCE as e where p.poam_type!='NONE' and p.poam_status='EP' and e.ev_sso_evaluation = 'APPROVED' AND e.ev_fsa_evaluation = 'NONE' and p.poam_id=e.poam_id " ;               
		else if ( $Role_ID == 7 ) //IVV
			$sql_none = "select count(*) as v_none from " . TN_POAMS . " as p , POAM_EVIDENCE as e where p.poam_type!='NONE' and p.poam_status='EP' and e.ev_sso_evaluation = 'APPROVED' AND e.ev_fsa_evaluation = 'APPROVED' and p.poam_id=e.poam_id " ;               
		else // other roles
			return " " ;
		
		$result_none  = mysql_query($sql_none) or die("Query failed: " . mysql_error());
		$row_none = mysql_fetch_array($result_none, MYSQL_ASSOC);
		$v_none = $row_none['v_none'] ;
		
		if ($v_none > 0)
		   $msg = "<li>You have <font color='red'> $v_none </font>  evidence package to review  </li>" ;
		else
		   $msg = "" ;
			  return $msg ;
    }


	function cap_expected() 
	{
		// $sql_none = "select count(*) as v_none from " . TN_POAMS where poam_type='CAP' and poam_status='OPEN' or poam_status='EN' and poam_action_date_est <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) and poam_action_date_est >= CURDATE()" ;
		
		$current_date = date("Y-m-d");  
		$next_week  = date("Y-") . date("m-") . ( date("d")+7 )  ;
		
		$sql_none = "select count(*) as v_none from " . TN_POAMS . " where poam_type='CAP' and ( poam_status='OPEN' or poam_status='EN' )	and poam_action_date_est <= '$next_week'  and poam_action_date_est >= '$current_date' " ;		//$sql_none = "select count(*) as v_none from POAMS where poam_type='CAP' and poam_status='OPEN' or poam_status='EN' and poam_action_date_est < DATE_ADD(CURDATE(), INTERVAL 7 DAY) " ;
		
		$result_none  = mysql_query($sql_none) or die("Query failed: " . mysql_error());
		$row_none = mysql_fetch_array($result_none, MYSQL_ASSOC);
		$v_none = $row_none['v_none'] ; 

		if ($v_none > 0)
			$msg = "<li>You have <font color='red'> $v_none </font>   CAP items with expected completion dates within the next 7 days  </li>" ;
		else
			$msg = "" ;
		
		return $msg ;
	}

	function cap_overdue() 
	{
		$sql_none = "select count(*) as v_none from " . TN_POAMS . " where poam_type='CAP' and ( poam_status='OPEN' or poam_status='EN' ) and poam_action_date_est < CURDATE() " ;
		$result_none  = mysql_query($sql_none) or die("Query failed: " . mysql_error());
		$row_none = mysql_fetch_array($result_none, MYSQL_ASSOC);
		$v_none = $row_none['v_none'] ; 

		if ($v_none > 0)
			$msg = "<li>You have <font color='red'> $v_none </font> CAP items overdue   </li>" ;
		else
			$msg = "" ;
		
		return $msg ;
	}

}

?>
