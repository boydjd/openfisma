<?PHP
// no-cache — forces caches to submit the request to the origin server for validation before releasing a cached copy, every time. This is useful to assure that authentication is respected.
// must-revalidate — tells caches that they must obey any freshness information you give them about a representation. By specifying this header, you’re telling the cache that you want it to strictly follow your rules.
header("Cache-Control: no-cache, must-revalidate");

/*
** The addslashes() in dblink.php messes with the my_v_type[]
** brackets, so capture this right away. For now. Apr. 4 2006
*/
$my_v_type = isset($_POST['my_v_type']) ? $_POST['my_v_type'] : null;

// required for all pages, after user login is verified function displayloginfor checks all user security functions, gets the users first/last name and customer log as well as loads ovms.ini.php
require_once("config.php");
require_once("dblink.php");
require_once("finding.class.php");
require_once("findingDBManager.php");
require_once("pubfunc.php");

// session_start() creates a session or resumes the current one based on the current session id that's being passed via a request, such as GET, POST, or a cookie.
// If you want to use a named session, you must call session_name() before calling session_start().
session_start();

// creates a new user object from the user class
$user = new User($db);

// validates that the user is logged in properly, if not redirects to the login page.
verify_login($user, $smarty);

// get user right for this screen
// $user->checkRightByFunction($screen_name, "function_name");
$view_right = $user->checkRightByFunction("vulnerability", "view");
$edit_right = $user->checkRightByFunction("vulnerability", "edit");
$add_right  = $user->checkRightByFunction("vulnerability", "add");
$del_right  = $user->checkRightByFunction("vulnerability", "delete");

// let's template know how to display the page
$smarty->assign('view_right', $view_right);
$smarty->assign('edit_right', $edit_right);
$smarty->assign('add_right', $add_right);
$smarty->assign('del_right', $del_right);


$total_pages = 0 ;

/**************Main Area*****************/
if($view_right || $del_right || $edit_right)
{
    $v_data = Get_Vul_Type();

    if (isset($_POST['v_page']) && ($_POST['v_search'] != 'Search'))
        $page_no = $_POST['v_page'] ;
    else
        $page_no = 1 ;
        
    if(isset($_POST['submit'])){
        if ($_POST['submit'] == 'Next Page')
            $page_no ++ ;
        else if ( $_POST['submit'] == 'Prev Page')
            $page_no -- ;
    }

    if (isset($_POST['go_back']))
        $page_no = $_SESSION['page_no'] ;
    $_SESSION['page_no'] = $page_no;


    $smarty->assign('v_page', $page_no);


    $from_record = ($page_no - 1) * 20 ;

    $v_keyword = '' ;

    if (isset($_POST['my_v_type']))
    {
        $type_para = ' ' ;

// currently retrieved up top
//      $my_v_type = $_POST['my_v_type'];

        $vt_count = 0 ;

        for( $iii=0; $iii<count($v_data); $iii++)
        {
            $v_data[$iii]['v_checked'] = '' ;
        }

        foreach ($my_v_type as $vt)
        {
            $type_para = " $type_para  '" . $vt ."' , "     ;

            for( $ii=0; $ii<count($v_data); $ii++)
            {
                if ( $v_data[$ii]['v_type'] == "$vt" )
                    $v_data[$ii]['v_checked'] = 'checked' ;
            }
        }

        if ($type_para != ' ')
            $search_para = " vuln_type in ( " . substr($type_para, 0, strlen($type_para)-2) . " )";
    }
    else
    {
        $search_para = "";
    }

    if (isset($_POST['go_back']))
        $v_data = $_SESSION['v_data'] ;
    $_SESSION['v_data'] = $v_data;

    $smarty->assign('v_data', $v_data);

    if (isset($_POST['v_startdate']) && ($_POST['v_enddate'] != '') && ($search_para !='') )
    {
        //echo "v_startdate:  $_POST[v_startdate]  ";
        $new_v_startdate = convert_date_format($_POST['v_startdate']);
        //echo "   v_enddate: $_POST[v_enddate]  ";
        $new_v_enddate = convert_date_format($_POST['v_enddate']);

        $search_para = $search_para . " and vuln_date_published > '" . $new_v_startdate . "' and vuln_date_published < '" . $new_v_enddate . "' " ;
        $v_startdate = $_POST['v_startdate'] ;
        $v_enddate = $_POST['v_enddate'] ;
    }
    else if (isset($_POST['v_startdate']) && ($_POST['v_enddate'] != '') && ($search_para =='') )
    {
        //echo "v_startdate:  $_POST[v_startdate]  ";
        $new_v_startdate = convert_date_format($_POST['v_startdate']);
        //echo "   v_enddate: $_POST[v_enddate]  ";
        $new_v_enddate = convert_date_format($_POST['v_enddate']);

        $search_para = $search_para . "  vuln_date_published > '" . $new_v_startdate . "' and vuln_date_published < '" . $new_v_enddate . "' " ;
        $v_startdate = $_POST['v_startdate'] ;
        $v_enddate = $_POST['v_enddate'] ;
    }

    if (!empty($_POST['v_keyword']) ){
        if(!empty($search_para) ){
            $search_para .= " and "; 
        }
        $search_para .= " MATCH (vuln_desc_primary, vuln_desc_secondary) AGAINST ('" .$_POST['v_keyword']. "') " ;
        $v_keyword = $_POST['v_keyword'] ;
    }

    if (isset($_POST['v_order']) && ($search_para == ''))
    {
        $search_para = $search_para . " 1=1 " . $_POST['v_order']  ;
    }
    else if (isset($_POST['v_order']) && ($search_para != ''))
    {
        $search_para = $search_para . $_POST['v_order']  ;
    }



    $v_count = get_quanlity() ;



    if (isset($_POST['go_back']))
    {
        $v_keyword = $_SESSION['v_keyword'] ;
        $v_startdate = $_SESSION['v_startdate'] ;
        $v_enddate = $_SESSION['v_enddate'] ;
        $search_para = $_SESSION['search_para'] ;
    }

    $_SESSION['tot_define'] = $v_count;
    $_SESSION['v_keyword'] = $v_keyword;
    $_SESSION['v_startdate'] = isset($v_startdate)?$v_startdate:NULL;
    $_SESSION['v_enddate'] = isset($v_enddate)?$v_enddate:NULL;
    $_SESSION['search_para'] = $search_para;

    $smarty->assign('v_keyword', $v_keyword);
    $smarty->assign('v_startdate', $_SESSION['v_startdate']);
    $smarty->assign('v_enddate', $_SESSION['v_enddate']);
    $smarty->assign('tot_define', $v_count);

    //Get total vulnerabilities


/*
    if ($_POST[go_back])
    {
        $search_para = stripslashes ($_POST[pass_search_para]) ;
        $page_no = stripslashes ($_POST[pass_page_no] );
        echo "search_para : $search_para   :: page_no : $page_no"  ;
    }
*/
    //Get vulnerabilities list

    $v_table = Get_Vul_List($search_para , $page_no);

/*
    $smarty->assign('pass_search_para', $search_para);
    $smarty->assign('pass_page_no', $page_no);
*/
    if ( $page_no == 1 )
    {
        $smarty->assign('prev_page_disabled', 'disabled');
        //$_SESSION['prev_page_disabled'] = 'disabled';
    }

    if ( $page_no == $total_pages )
    {
        $smarty->assign('next_page_disabled', 'disabled');
        //$_SESSION['next_page_disabled'] = 'disabled';
    }

    $smarty->assign('total_pages', $total_pages);
    //$_SESSION['total_pages'] = $total_pages;


    $smarty->assign('v_table', $v_table);
    //$_SESSION['v_table'] = $v_table;

    //print_r($_SESSION) ;

}

    $smarty->assign("pageName","Vulnerabilities Summary");
    $smarty->display('vulnerabilities.tpl');

//convert any date format to 2006-12-31
/*function convert_date_format( $date )
{
    $day = "00";
    $month = "00";
    $year = "0000";

    if ( $date != "" )
    {
        // mm-dd-yy
        //ereg("^([-a-zA-Z0-9_\.\!@#\$&\*\+\=\|])*$" , $var)
        if ( ereg( "^([0-9]{1,2})-([0-9]{1,2})-([0-9]{2})$", $date, $components ))
        {
            $day = $components[2];
            $month = $components[1];
            $year = $components[3];
        }
        // mm-dd-yyyy
        else if ( ereg( "^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$", $date, $components ))
        {
            $day = $components[2];
            $month = $components[1];
            $year = $components[3];
        }
        // yyyy-mm-dd
        else if ( ereg( "^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$", $date, $components ))
        {
            $day = $components[3];
            $month = $components[2];
            $year = $components[1];
        }
        // mm/dd/yyyy
        else if ( ereg( "^([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})$", $date, $components ))
        {
            $day = $components[2];
            $month = $components[1];
            $year = $components[3];
        }
        // /mm/dd/yy
        else if ( ereg( "^([0-9]{1,2})/([0-9]{1,2})/([0-9]{2})$", $date, $components ))
        {
            $day = $components[2];
            $month = $components[1];
            $year = $components[3];
        }

        $day = ( strlen( $day ) == 1 ) ? "0".$day : $day;
        $month = ( strlen( $month ) == 1 ) ? "0".$month : $month;
        $year = ( strlen( $year ) < 4 ) ? (( $year < 50 ) ? ( 2000 + $year ) : (1900 + $year )) : $year;

        return $year."-".$month."-".$day;
    }

    return "0000-00-00";
}
*/

//return vulnerability data list
function Get_Vul_List($para, $p_n)
{
    global $total_pages ;
    $from_page = ($p_n - 1) * 20 ;

    if ($para=='')
    {
        $total_sql = "select vuln_seq, vuln_type, vuln_desc_primary, vuln_date_published, vuln_severity from " . TN_VULNERABILITIES ;
        $total_result  = mysql_query($total_sql) or die("Query failed: " . mysql_error());
        $total_row = mysql_num_rows($total_result) ;
        $total_pages = ceil( $total_row / 20 );

        $sql = "select vuln_seq, vuln_type, vuln_desc_primary, vuln_date_published, vuln_severity from " . TN_VULNERABILITIES." limit $from_page , 20";
        $result  = mysql_query($sql) or die("Query failed: " . mysql_error());
        $data = null;

        if($result)
        {
            while($row = mysql_fetch_array($result))
            {
                $data[] = array('v_seq'=>$row['vuln_seq'],
                                'v_type'=>$row['vuln_type'],
                                'v_desc'=>$row['vuln_desc_primary'],
                                'v_date'=>$row['vuln_date_published'],
                                'v_severity'=>$row['vuln_severity'] );
            }
        }
    }
    else//  if ($para=='')
    {
        $total_sql = "select vuln_seq, vuln_type, vuln_desc_primary, vuln_date_published, vuln_severity from " . TN_VULNERABILITIES." where $para ";
        $total_result  = mysql_query($total_sql) or die("Query failed: " . mysql_error());
        $total_row = mysql_num_rows($total_result) ;
        $total_pages = ceil( $total_row / 20 );

        $sql = "select vuln_seq, vuln_type, vuln_desc_primary, vuln_date_published, vuln_severity from " . TN_VULNERABILITIES . " where $para  limit $from_page , 20";
        $result  = mysql_query($sql) or die("Query failed: " . mysql_error());
        $data = null;

        if($result)
        {
            while($row = mysql_fetch_array($result))
            {
                $data[] = array('v_seq'=>$row['vuln_seq'],
                                'v_type'=>$row['vuln_type'],
                                'v_desc'=>$row['vuln_desc_primary'],
                                'v_date'=>$row['vuln_date_published'],
                                'v_severity'=>$row['vuln_severity'] );
            }
        }
    }

    return $data;
}

//get amount of vulnerabilities
function get_quanlity()
{
    $t_sql = "select count(vuln_type) from " . TN_VULNERABILITIES ;
    $t_results  = mysql_query($t_sql) ;

    $t_count = mysql_fetch_row($t_results);
    $v_qty = $t_count[0];

    return $v_qty ;
}

//return vulnerability data : type, amount
function Get_Vul_Type()
{
    $vul_sql = " select vuln_type, count(vuln_type) as vuln_total  from " . TN_VULNERABILITIES . " group by vuln_type " ;

    $vul_results  = mysql_query($vul_sql) ;

    if($vul_results)
    {
        while($row = mysql_fetch_array($vul_results))
        {
            $data[] = array('v_type'=>$row['vuln_type'] ,
                            'v_checked'=>'checked' ,
                            'v_total'=>$row['vuln_total'] );
        }
    }
    return $data ;
}



?>
