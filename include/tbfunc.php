<?PHP

/****************************************************************************/
/************************Function Define Begin*******************************/
/****************************************************************************/
// display the menu
function OptionMenu($tb_id)
{
	global $table_class, $table_class_index,$_db_name,$table_arr,$table_id_field_arr,$table_name_arr;
	global $pageurl;

	$msg = "";
	$cols = 1;
	$msg .= "<table border=\"0\" width=\"95%\" align=\"center\">\n";
	foreach($table_class as $classid=>$classname)
	{
		$msg .= "<tr>\n";
		$msg .= "<td bgcolor=\"#AE97E9\"><font color=\"#ffffff\">&nbsp;$classname</font></td>\n";
		$msg .= "</tr>\n";

		for($i = 0; $i < count($table_arr); $i++)
		{
			if($classid == $table_class_index[$i][1])
			{
				$tbid = $i + 1;
				$msg .= "<tr>\n";
				if($tbid == $tb_id)
					$msg .= "<td align=\"right\" bgcolor=\"#D7CEF3\"><font color=\"#888888\">";
				else
					$msg .= "<td align=\"right\">";

				$msg .= "<nobr>[<a href=\"$pageurl?tid=$tbid\" title=\"List $table_name_arr[$i] entries\">$table_name_arr[$i]</a>]</nobr></td>\n";

				$msg .= "</tr>\n";
			}
		}

		$msg .= "<tr>\n";
		$msg .= "<td>&nbsp;</td>\n";
		$msg .= "</tr>\n";
	}
	$msg .= "</table>\n";

	echo $msg;
}


// script for delete operation
function Script()
{
	global $tablename;
?>
<script language=javascript src="javascripts/form.js"></script>
<script language="javascript" src="javascripts/func.js"></script>
<script language="javascript">
function delok(entryname)
{
	var str = "Are you sure that you want to delete this " + entryname + "?";

	if(confirm(str) == true)
	{
		return true;
	}
	return false;
}

function qok(pg)
{
	var q = document.query.qv.value;

	if(q.length == 0)
	{
		alert("Search criteria cannot be blank. Please enter a vaild search criteria.");
		document.query.qv.focus();
		return false;
	}

	if(pg > 0)
	{
		document.query.qno.value = pg;
	}

	return true;
}


function pagego()
{
	var pg = document.scroll.pgno.value;

	if(IsStrNull(pg) || !IsNumber(pg))
	{
		alert("Please input a valid page number.");
		document.scroll.pgno.select();
		return false;
	}

	return true;
}
</script>

<?
}


function StatForm($tb_id, $qindex)
{
	global $table_field_arr,$table_field_name_arr,$table_field_stat_arr;
	global $pageurl;

	$index = $tb_id - 1;
	$field_arr = $table_field_arr[$index];
	$field_cn_arr = $table_field_name_arr[$index];
	$field_stat_arr = $table_field_stat_arr[$index];
?>

<table border="0" align="center" cellspacing="1" cellpadding="1">
<form name="stat" method="post" action="<?=$pageurl?>">
<input type="hidden" name="r_do" value="stat">
<input type="hidden" name="tid" value="<?=$tb_id?>">
<tr>
	<td><b>Statistic:&nbsp;</b></td>
	<td><select name="fid">
<?
	$num = 0;
	for($i = 0; $i < count($field_arr); $i++)
	{
		$k = $i + 1;
		if($field_stat_arr[$i] == 1)
		{
			$num++;
			if($qindex == $k)
				echo "<option value=\"$k\" selected>$field_cn_arr[$i]</option>\n";
			else
				echo "<option value=\"$k\">$field_cn_arr[$i]</option>\n";
		}
	}
	if($num == 0)
		echo "<option value=\"0\">No stat data</option>\n";
?>
	</select></td>
	<td>
	<?if($num > 0) { ?>
	<input type="submit" value="Go" title="Submit your request for stat">
	<? } else { ?>
	<input type="button" value="Go" title="Submit your request for stat">
	<?}?>
	</td>
</tr>
</form>
</table>


<?
}



function QueryForm($tb_id, $qindex, $qvalue, $pgno, $flag)
{
	global $_db_name,$table_arr,$table_id_field_arr,$table_name_arr,$table_field_arr,$table_field_type_arr,$table_field_name_arr,$table_field_key_arr,$table_field_len_arr;
	global $pageurl;

	$index = $tb_id - 1;
	$field_arr = $table_field_arr[$index];
	$field_cn_arr = $table_field_name_arr[$index];
	$field_type_arr = $table_field_type_arr[$index];


?>

<table border="0" align="center" cellspacing="1" cellpadding="1">
<form name="query" method="post" action="<?=$pageurl?>" onsubmit="return qok(0);">
<input type="hidden" name="r_do" value="query">
<input type="hidden" name="tid" value="<?=$tb_id?>">
<input type="hidden" name="qno" value="<?=$pgno?>">
<tr>
	<td><b>Query:&nbsp;</b></td>
	<td><select name="fid">
<!--		<option value="0" selected>NO.</option>-->
<?
	for($i = 0; $i < count($field_arr); $i++)
	{
		if($field_type_arr[$i] == "password" || $field_type_arr[$i] == "text")
			continue;

		$k = $i + 1;
		if($qindex == $k)
			echo "<option value=\"$k\" selected>$field_cn_arr[$i]</option>\n";
		else
			echo "<option value=\"$k\">$field_cn_arr[$i]</option>\n";
	}
?>
	</select></td>
	<td><input type="text" name="qv" value="<? if(isset($qvalue)) echo $qvalue; ?>" title="Input your query value" size="10" maxlength="20"></td>
	<!--<td><input type="submit" name="submit" value="Go" title="submit your request"></td>-->
	<td><input type="submit" value="Search" title="submit your request" onclick="qok(1);"></td>
<?

	$pre = $pgno - 1;
	$next = $pgno + 1;

	if($pre > 0)
		echo "<td>&nbsp;<a href=\"#\" onclick=\"qok($pre); document.query.submit();\" title=\"Get the prev search page\">&lt;&lt;</a>&nbsp;</td>\n";
	if($flag)
		echo "<td>&nbsp;<a href=\"#\" onclick=\"qok($next); document.query.submit();\" title=\"Get the next search page\">&gt;&gt;</a>&nbsp;</td>\n";
?>
</tr>
</form>
</table>


<?
}


// ҳ����ת��ʾ
function PageScroll($tb_id,$pgno,$of=0,$asc=0)
{
	global $_db_name,$table_arr,$table_id_field_arr,$table_name_arr,$table_field_arr,$table_field_type_arr,$table_field_name_arr,$table_field_key_arr,$table_field_len_arr;
	global $db, $pageurl, $pagesize, $totalrecords;

	$pages = 0;
	$index = $tb_id - 1;
	$tbname = $table_arr[$index];

	if($tbname == "SYSTEM_GROUPS")
		$sql = "SELECT COUNT(*) AS num FROM $_db_name.$tbname WHERE sysgroup_is_identity=0";
	else
		$sql = "SELECT COUNT(*) AS num FROM $_db_name.$tbname";

	$result = $db->sql_query($sql);
	if($line = $db->sql_fetchrow($result))
	{
		$totalrecords = $line["num"];
		$temppages = floor($totalrecords / $pagesize);
		if($pages == $temppages * $pagesize)
			$pages = $temppages;
		else
			$pages = floor($totalrecords / $pagesize) + 1;
	}

	if($pgno <= 1)
		$pos = 1;
	else
		$pos = $pgno - 1;

	if($pgno >= $pages)
		$ipos = $pages;
	else
		$ipos = $pgno + 1;

	$toolbar = "<table border=\"0\" align=\"center\" cellspacing=\"1\" cellpadding=\"1\">\n";
	$toolbar .= "<tr height=\"22\">\n";
	$toolbar .= "<td><b>Page:&nbsp;</b></td>\n";
	$toolbar .= "<td>|</td>\n";
	if($pgno == 1)
	{
		$toolbar .= "<td>First</td>\n";
		$toolbar .= "<td>|</td>\n";
		$toolbar .= "<td>Prev</td>\n";
	}
	else
	{
		$toolbar .= "<td><a href=\"$pageurl?tid=$tb_id&pgno=1&of=$of&asc=$asc\" title=\"go to the first page\">First</a></td>\n";
		$toolbar .= "<td>|</td>\n";
		$toolbar .= "<td><a href=\"$pageurl?tid=$tb_id&pgno=$pos&of=$of&asc=$asc\"  title=\"the prev page\">Prev</a></td>\n";
	}
	$toolbar .= "<td>|</td>\n";
	if($pgno >= $pages)
	{
		$toolbar .= "<td>Next</td>\n";
		$toolbar .= "<td>|</td>\n";
		$toolbar .= "<td>Last</td>\n";
	}
	else
	{
		$toolbar .= "<td><a href=\"$pageurl?tid=$tb_id&pgno=$ipos&of=$of&asc=$asc\" title=\"the next page\">Next</a></td>\n";
		$toolbar .= "<td>|</td>\n";
		$toolbar .= "<td><a href=\"$pageurl?tid=$tb_id&pgno=$pages&of=$of&asc=$asc\" title=\"go to the last page\">Last</a></td>\n";
	}
	$toolbar .= "<td>|</td>\n";
	$toolbar .= "<form name=\"scroll\" action=\"$pageurl\" method=\"post\" onsubmit=\"return pagego();\">\n";
	$toolbar .= "<td>";
	$toolbar .= "<input type=\"hidden\" name=\"tid\" value=\"$tb_id\">\n";
	$toolbar .= "<input type=\"hidden\" name=\"of\" value=\"$of\">\n";
	$toolbar .= "<input type=\"hidden\" name=\"asc\" value=\"$asc\">\n";

	$toolbar .= "<input type=\"text\" name=\"pgno\" value=\"$pgno\" size=\"2\" maxlength=\"5\">\n";
	$toolbar .= "</td>\n";
	$toolbar .= "<td>\n";
	//$toolbar .= "<input type=\"submit\" name=\"submit\" value=\"Go\">\n";
	$toolbar .= "<input type=\"submit\" value=\"Go\">\n";
	$toolbar .= "</td></form>\n";
	$toolbar .= "<td>|</td>\n";
	$toolbar .= "</tr>\n";
	$toolbar .= "</table>\n";

	return $toolbar;
}


// stat by the special field
function DoStat($tb_id, $n_id)
{
	global $_db_name,$table_arr,$table_id_field_arr,$table_name_arr,$table_field_arr,$table_field_type_arr,$table_field_name_arr,$table_field_key_arr,$table_field_len_arr,$table_field_stat_arr,$table_relation_arr;
	global $db, $pageurl, $page_title;

	$index = $tb_id - 1;
	$fnid = $n_id - 1;

	$tbname = $table_arr[$index];
	$tbcnname = $table_name_arr[$index];
	$field_arr = $table_field_arr[$index];
	$field_cn_arr = $table_field_name_arr[$index];
	$field_type_arr = $table_field_type_arr[$index];
	$field_len_arr = $table_field_len_arr[$index];
	$relation_arr = $table_relation_arr[$index];
	$field_stat_arr = $table_field_stat_arr[$index];


	$msg = "";
	$num = 0;
	if(count($field_arr) > 0 && $field_stat_arr[$fnid] == 1)
	{
		$relation_val = array();
		$relation = $relation_arr[$fnid];
		//print_r($relation);

		if($relation[0] == 1 && count($relation) == 4)
		{
			$sql = "SELECT $relation[2] AS skey,$relation[3] AS svalue FROM $relation[1]";
			//echo $sql;
			$result = $db->sql_query($sql);
			$num = 0;
			$key_arr = array();
			$value_arr = array();
			while($line_arr = $db->sql_fetchrow($result))
			{
				$line = array_values($line_arr);
				$num++;
				list($key, $value) = $line;
				array_push($key_arr, $key);
				array_push($value_arr, $value);
			}
			$db->sql_freeresult($result);
			$key_value_arr = array($num, $key_arr, $value_arr);
			array_push($relation_val, $key_value_arr);
		}
		else if($relation[0] == 2 && count($relation) == 2)
		{
			$num = count($relation[1]);
			$key_arr = array_keys($relation[1]);
			$value_arr = array_values($relation[1]);
			$key_value_arr = array($num, $key_arr, $value_arr);
			array_push($relation_val, $key_value_arr);
		}
		else
			array_push($relation_val, 0);
		//print_r($relation_val);

		$type = $field_type_arr[$fnid];
		if($type == "date")
			$fn = "DATE_FORMAT(". $field_arr[$fnid] . ",'%Y-%m-%d')";
		else if($type == "time")
			$fn = "DATE_FORMAT(". $field_arr[$fnid] . ",'%H:%i:%s')";
		else if($type == "datetime")
			$fn = "DATE_FORMAT(". $field_arr[$fnid] . ",'%Y-%m-%d %H:%i:%s')";
		else
			$fn = $field_arr[$fnid];

		if($tbname == "SYSTEM_GROUPS")
			$sql = "SELECT $fn, COUNT(*) FROM $_db_name.$tbname WHERE sysgroup_is_identity=0 GROUP BY $field_arr[$fnid]";
		else
			$sql = "SELECT $fn, COUNT(*) FROM $_db_name.$tbname GROUP BY $field_arr[$fnid]";
		//echo $sql;
		$result = $db->sql_query($sql);

		$num = 0;
		while($line_arr = $db->sql_fetchrow($result))
		{
			$line = array_values($line_arr);
			$num++;
			$v = $line[0];
			if($relation_arr[$fnid][0] == 1)
			{
				if($relation_val[0][0] > 0 && count($relation_val[0]) == 3)
				{
					$key_arr = $relation_val[0][1];
					$value_arr = $relation_val[0][2];

					for($pos = 0; $pos < count($key_arr); $pos++)
					{
						if($key_arr[$pos] == $v)
						{
							$v = $value_arr[$pos];
							break;
						}
					}
				}
			}

			if($field_type_arr[$fnid] == "text" || $field_len_arr[$fnid] > 100)
				$v = substring($v, 100, false);

			$msg .= "<tr>\n";
			$msg .= "	<td align=\"center\" bgcolor=\"#eeeeee\">$num</td>\n";
			$msg .= "	<td>&nbsp;$v</td>\n";
			$msg .= "	<td align=\"right\">$line[1]</td>\n";
			$msg .= "</tr>\n";
		}
		$db->sql_freeresult($result);
	}

	$body = underline("$page_title Stat result");
	$body .= "<table border=\"1\" cellspacing=\"1\" cellpadding=\"1\">\n";
	$body .= "<tr bgcolor=\"#cccccc\">\n";
	$body .= "<td align=\"center\"><nobr>NO</nobr></td>\n";
	$body .= "<td align=\"center\"><nobr>$field_cn_arr[$fnid]</nobr></td>\n";
	$body .= "<td align=\"center\"><nobr>Count</nobr></td>\n";
	$body .= "</tr>\n";
	if($num > 0)
		$body .= $msg;
	$body .= "</table>\n";

	if($num == 0)
		$body .= "<p>Entries is empty, no stat!</p>";

	return $body;
}



// search by the special field
function DoQuery($tb_id, $n_id, $q_v, $pgno, $edit_right, $view_right, $del_right)
{
	global $_db_name,$table_arr,$table_id_field_arr,$table_name_arr,$table_field_arr,$table_field_type_arr,$table_field_name_arr,$table_field_key_arr,$table_field_len_arr,$table_relation_arr,$table_field_display_arr;
	global $db, $pageurl, $pagesize, $querynext, $page_title;

	$index = $tb_id - 1;
	$tbname = $table_arr[$index];
	$tb_fid = $table_id_field_arr[$index];
	$tbcnname = $table_name_arr[$index];
	$tbdisplay_arr = $table_field_display_arr[$index];
	$field_arr = $table_field_arr[$index];
	$field_cn_arr = $table_field_name_arr[$index];
	$field_type_arr = $table_field_type_arr[$index];
	$field_len_arr = $table_field_len_arr[$index];
	$relation_arr = $table_relation_arr[$index];

	$msg = "";
	$num = 0;
	$nid = $n_id - 1;
	$relationsearch = false;

	if(count($field_arr) > 0)
	{
		$real_con = "";

		$field = $tb_fid; //"id";
		$relation_val = array();
		for($i = 0; $i < count($field_arr); $i++)
		{
			$relation = $relation_arr[$i];
			$type = $field_type_arr[$i];

			if($relation[0] == 1 && count($relation) == 4)
			{
				$sql = "SELECT $relation[2],$relation[3] FROM $relation[1]";
				//echo $sql;
				$result = $db->sql_query($sql);

				$num = 0;
				$key_arr = array();
				$value_arr = array();
				while($line_arr = $db->sql_fetchrow($result))
				{
					$line = array_values($line_arr);
					$num++;
					list($key,$value) = $line;
					// get real condition
					if($nid == $i) {
						$relationsearch = true;
						if(strpos($value, $q_v) === false) {
						}
						else {
							if(!empty($real_con))
								$real_con .= ",";
							$real_con = "'$key'";
						}
					}

					array_push($key_arr, $key);
					array_push($value_arr, $value);
				}
				$db->sql_freeresult($result);
				$key_value_arr = array($num, $key_arr, $value_arr);
				array_push($relation_val, $key_value_arr);
			}
			else if($relation[0] == 2 && count($relation) == 2)
			{
				$num = count($relation[1]);
				if($nid == $i) {
					$relationsearch = true;
					foreach($relation[1] as $key=>$value) {
						if(strpos($value, $q_v) === false) {
						}
						else {
							if(!empty($real_con))
								$real_con .= ",";
							$real_con = "'$key'";
						}
					}
				}
				$key_arr = array_keys($relation[1]);
				$value_arr = array_values($relation[1]);
				$key_value_arr = array($num, $key_arr, $value_arr);
				array_push($relation_val, $key_value_arr);
			}
			else
				array_push($relation_val, 0);


			if($type == "date")
				$fn = "DATE_FORMAT(". $field_arr[$i] . ",'%Y-%m-%d')";
			else if($type == "time")
				$fn = "DATE_FORMAT(". $field_arr[$i] . ",'%H:%i:%s')";
			else if($type == "datetime")
				$fn = "DATE_FORMAT(". $field_arr[$i] . ",'%Y-%m-%d %H:%i:%s')";
			else
				$fn = $field_arr[$i];
			if($tbdisplay_arr[$i] == 1)
				$field .= "," . $fn;
		}

		//print_r($relation_val);
		if($pgno > 0)
			$startpos = ($pgno - 1) * $pagesize;
		else
			$startpos = 0;

		$othersql = "";
		if($tbname == "SYSTEM_GROUPS")
			$othersql = " and sysgroup_is_identity=0 ";

		if($n_id == 0)
			$sql = "SELECT $field FROM $_db_name.$tbname WHERE $tb_fid='$q_v' $othersql LIMIT $startpos, $pagesize";
		else if($relationsearch)
			$sql = "SELECT $field FROM $_db_name.$tbname WHERE $field_arr[$nid] IN ($real_con) $othersql LIMIT $startpos, $pagesize";
		else
			$sql = "SELECT $field FROM $_db_name.$tbname WHERE $field_arr[$nid] LIKE '%$q_v%' $othersql LIMIT $startpos, $pagesize";

		//echo $sql;
		$result = $db->sql_query($sql);

		$num = 0;
		while($line_arr = $db->sql_fetchrow($result))
		{
			$line = array_values($line_arr);
			$num++;
			$msg .= "<tr>\n";
			$id = $line[0];
			$j = 0;
			//$msg .= "	<td class=\"tdc\" align=\"center\" bgcolor=\"#eeeeee\">$id</td>\n";
			for($i = 1; $i <= count($field_arr); $i++)
			{
				if($tbdisplay_arr[$i - 1] == 0)
					continue;
				$k = $i - 1;
				$j++;
				$v = $line[$j];
				if(empty($v) && $v != 0)
				{
					if($v != "")
					{
						if($field_len_arr[$k] == 1)
							$msg .= "	<td class=\"thc\" align=\"center\">0</td>\n";
						else
							$msg .= "	<td class=\"tdc\" align=\"right\">0&nbsp;</td>\n";
					}
					else
					{
						$msg .= "	<td class=\"tdc\">&nbsp;</td>\n";
					}
				}
				else
				{
					$rr = 0;
					if($relation_arr[$k][0] > 0)
					{
						if($relation_val[$k][0] > 0 && count($relation_val[$k]) == 3)
						{
							$key_arr = $relation_val[$k][1];
							$value_arr = $relation_val[$k][2];
							for($pos = 0; $pos < count($key_arr); $pos++)
							{
								if($key_arr[$pos] == $v)
								{
									$v = $value_arr[$pos];
									$rr = 1;
									break;
								}
							}
						}
					}
					if($field_type_arr[$k] == "text" || $field_len_arr[$k] > 100)
					{
						$v = substring($v, 100, false);
						$msg .= "	<td class=\"tdc\">&nbsp;$v</td>\n";
					}
					else if($field_len_arr[$k] == 1)
					{
						$msg .= "	<td class=\"tdc\" align=\"center\">$v</td>\n";
					}
					else if(($field_type_arr[$k] == "int") && ($rr == 0))
					{
						$msg .= "	<td class=\"tdc\" align=\"right\">$v&nbsp;</td>\n";
					}
					else
					{
						$msg .= "	<td class=\"tdc\">&nbsp;$v</td>\n";
					}
				}
			}
			if($edit_right)
				$msg .= "	<td class=\"thc\" align=\"center\"><a href=\"$pageurl?tid=$tb_id&r_do=form&r_id=$id\" title=\"edit this $tbcnname\"><img src=\"images/edit.png\" border=\"0\"></a></td>\n";
			if($view_right)
				$msg .= "	<td class=\"thc\" align=\"center\"><a href=\"$pageurl?tid=$tb_id&r_do=view&r_id=$id\" title=\"display the $tbcnname\"><img src=\"images/view.gif\" border=\"0\"></a></td>\n";
			if($tbname == "ROLES") {
				if($edit_right && $view_right)
					$msg .= "	<td class=\"thc\" align=\"center\"><a href=\"$pageurl?tid=$tb_id&r_do=rrform&r_id=$id\" title=\"set rights for this $tbcnname\"><img src=\"images/signtick.gif\" border=\"0\"></a></td>\n";
			}
			if($del_right)
				$msg .= "	<td class=\"thc\" align=\"center\"><a href=\"$pageurl?tid=$tb_id&r_do=dele&r_id=$id\" title=\"delete this $tbcnname, can't restore after deleted\" onclick=\"return delok('$tbcnname');\"><img src=\"images/del.png\" border=\"0\"></a></td>\n";
			$msg .= "</tr>\n";

			if($num == $pagesize)
				$querynext = true;
		}
		$db->sql_freeresult($result);
	}

	$body = underline("$page_title Search result");
	$body .= "<table width=\"95%\" align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"tbframe\">\n";
	$body .= "<tr align=\"center\">\n";
	//$body .= "<th>NO</td>\n";
	for($i = 0; $i < count($field_arr); $i++) {
		if($tbdisplay_arr[$i] == 1)
			$body .= "<th>$field_cn_arr[$i]</td>\n";
	}
	if($edit_right)
		$body .= "<th>Edit</td>\n";
	if($view_right)
		$body .= "<th>View</td>\n";
	if($tbname == "ROLES") {
		if($edit_right && $view_right)
			$body .= "<th>Right</td>\n";
	}
	if($del_right)
		$body .= "<th>Del</td>\n";
	$body .= "</tr>\n";
	if($num > 0)
		$body .= $msg;
	$body .= "</table>\n";

	if($num == 0)
		$body .= "<p>No entries for your search!</p>";

	return $body;
}


/***************************************************************************************************************/
// create a new entry
function AddRecord($tb_id)
{
	global $_db_name,$table_arr,$table_id_field_arr,$table_name_arr,$table_field_arr,$table_field_type_arr,$table_field_name_arr,$table_field_key_arr,$table_field_len_arr;
	global $db, $pageurl, $_POST;


	$index = $tb_id - 1;
	$tbname = $table_arr[$index];
	$tb_fid = $table_id_field_arr[$index];
	$tbcnname = $table_name_arr[$index];
	$field_arr = $table_field_arr[$index];
	$field_cn_arr = $table_field_name_arr[$index];
	$field_key_arr = $table_field_key_arr[$index];
	$field_type_arr = $table_field_type_arr[$index];

	$res = true;
	$field = "";
	$field_v = "";
	for($i = 0; $i < count($field_arr); $i++)
	{
		$fieldname = "r_" . $field_arr[$i];
		if(isset($_POST[$fieldname]))
		{
			$$fieldname = $_POST[$fieldname];
			if(!get_magic_quotes_gpc())
				$$fieldname = addslashes($$fieldname);
		}
		else
			$$fieldname = "";

		if($field_type_arr[$i] == "password")
		{
			// not save the empty password, or put the md5($password) value
			if(empty($$fieldname))
				continue;
			else
				$$fieldname = md5($$fieldname);
		}


		if($field_key_arr[$i] == 2)
		{
			$sql = "SELECT $tb_fid FROM $_db_name.$tbname WHERE $field_arr[$i]='$$fieldname'";
			$result = $db->sql_query($sql);

			if($line_arr = $db->sql_fetchrow($result))
			{
				$line = array_values($line_arr);
				if($line[0] > 0)
					$res = false; // data repeat

			}
			$db->sql_freeresult($result);
		}
		if(!$res)
			break;

		if($i == 0)
		{
			$field = $field_arr[$i];
			$field_v = "'" . $$fieldname . "'";
		}
		else
		{
			$field .= "," . $field_arr[$i];
			$field_v .= ",'" . $$fieldname . "'";
		}
	}

	if($res)
	{
		$sql = "INSERT INTO $_db_name.$tbname ($field) VALUES ($field_v)";
		//echo $sql;
		$res = $db->sql_query($sql);

		$id = $db->sql_nextid();
		// special funciton for the USERS create date field
		if($tbname == "USERS") {
			user_create_date($tbname, $id);
			UserSystemRoleDefine($id, $_POST);
		}
		if($tbname == "SYSTEMS") {
			SystemDefine($id, $_POST);
		}
		// special operation for PRODUCTS - need to generate META field
		// and ensure that nvd_created is set false. 04-04-2006cfd
		if($tbname == "PRODUCTS") {
		  $meta_sql = "UPDATE " . TN_PRODUCTS . " SET prod_meta = concat(prod_vendor, ' ', prod_name, ' ', prod_version), prod_nvd_defined = 0 WHERE prod_id = $id";
		  $db->sql_query($meta_sql);
		  }
	}

	return $res;
}

// modify entry
function UpdateRecord($tb_id, $id)
{
	global $_db_name,$table_arr,$table_id_field_arr,$table_name_arr,$table_field_arr,$table_field_type_arr,$table_field_name_arr,$table_field_key_arr,$table_field_len_arr;
	global $db, $pageurl, $_POST;

	$temp_password = "";

	$index = $tb_id - 1;
	$tbname = $table_arr[$index];
	$tb_fid = $table_id_field_arr[$index];
	$tbcnname = $table_name_arr[$index];
	$field_arr = $table_field_arr[$index];
	$field_cn_arr = $table_field_name_arr[$index];
	$field_key_arr = $table_field_key_arr[$index];
	$field_type_arr = $table_field_type_arr[$index];

	$res = true;
	$field = "";
	$field_v = "";
	for($i = 0; $i < count($field_arr); $i++)
	{
		$fieldname = "r_" . $field_arr[$i];
		if(isset($_POST[$fieldname]))
		{
			$$fieldname = $_POST[$fieldname];
			if(!get_magic_quotes_gpc())
			{
				$$fieldname = addslashes($$fieldname);
			}
		}
		else
			$$fieldname = "";

		if($field_type_arr[$i] == "password")
		{
			// not save the empty password, or put the md5($password) value
			if(empty($$fieldname))
				continue;
			else {
				$temp_password = $$fieldname;
				$$fieldname = md5($$fieldname);
			}
		}

		if($field_key_arr[$i] == 2)
		{
			$sql = "SELECT $tb_fid FROM $_db_name.$tbname WHERE $field_arr[$i]='$$fieldname'";
			$result = $db->sql_query($sql);

			if($line_arr = $db->sql_fetchrow($result))
			{
				$line = array_values($line_arr);
				if($line[0] > 0)
					$res = false; // data repeat

			}
			$db->sql_freeresult($result);
		}
		if(!$res)
			break;

		if(empty($field))
		{
			$field = $field_arr[$i] . "='" . $$fieldname . "'";
		}
		else
		{
			$field .= "," . $field_arr[$i] . "='" . $$fieldname . "'";
		}
	}

	if($res)
	{
		if($tbname == "SYSTEM_GROUPS")
			$sql = "UPDATE $_db_name.$tbname SET $field WHERE $tb_fid='$id' AND sysgroup_is_identity=0";
		else
			$sql = "UPDATE $_db_name.$tbname SET $field WHERE $tb_fid='$id'";
		//echo $sql;
		$res = $db->sql_query($sql);

		// special funciton for the USERS deactive date field
		if($tbname == "USERS") {
			user_deactive_date($tbname, $id);
			// modify user password, reset change password date to "0000-00-00"
			if(!empty($temp_password))
				user_change_password($tbname, $id);
			UserSystemRoleDefine($id, $_POST);
		}
		if($tbname == "SYSTEMS") {
			SystemDefine($id, $_POST);
		}
	}

	return $res;
}

// delete entry
function DeleteRecord($tb_id, $id)
{
	global $_db_name,$table_arr,$table_id_field_arr,$table_name_arr,$table_field_arr,$table_field_type_arr,$table_field_name_arr,$table_field_key_arr,$table_field_len_arr;
	global $db, $pageurl, $_POST;


	$index = $tb_id - 1;
	$tbname = $table_arr[$index];
	$tb_fid = $table_id_field_arr[$index];

	$res = false;
	if($id > 0)
	{
		$sql = "DELETE FROM $_db_name.$tbname WHERE $tb_fid='$id'";
		//echo $sql;
		$res = $db->sql_query($sql);

		// delete all role's right
		if($tbname == "ROLES") {
			$sql = "DELETE FROM $_db_name.ROLE_FUNCTIONS WHERE role_id='$id'";
			//echo $sql;
			$res = $db->sql_query($sql);
		}

		if($tbname == "USERS") {
			$sql = "DELETE FROM $_db_name.USER_SYSTEM_ROLES WHERE user_id='$id'";
			//echo $sql;
			$res = $db->sql_query($sql);
		}

		if($tbname == "SYSTEMS") {
			$sql = "DELETE FROM $_db_name.SYSTEM_GROUP_SYSTEMS WHERE system_id='$id'";
			//echo $sql;
			$res = $db->sql_query($sql);
		}
	}

	return $res;
}



// list entries
function ListRecord($tb_id, $pgno, $of, $asc, $edit_right, $view_right, $del_right)
{
	global $_db_name,$table_arr,$table_id_field_arr,$table_name_arr,$table_field_arr,$table_field_type_arr,$table_field_name_arr,$table_field_key_arr,$table_field_len_arr,$table_relation_arr,$table_field_display_arr;
	global $db, $pageurl, $pagesize, $page_title;

	$index = $tb_id - 1;
	$tbname = $table_arr[$index];
	$tb_fid = $table_id_field_arr[$index];
	$tbcnname = $table_name_arr[$index];
	$tbdisplay_arr = $table_field_display_arr[$index];
	$field_arr = $table_field_arr[$index];
	$field_cn_arr = $table_field_name_arr[$index];
	$field_type_arr = $table_field_type_arr[$index];
	$field_len_arr = $table_field_len_arr[$index];
	$relation_arr = $table_relation_arr[$index];

	$msg = "";
	$num = 0;

	if(count($field_arr) > 0)
	{
		$field = $tb_fid;
		$relation_val = array();
		for($i = 0; $i < count($field_arr); $i++)
		{
			$relation = $relation_arr[$i];
			$type = $field_type_arr[$i];
			if($relation[0] == 1 && count($relation) == 4)
			{
				$sql = "SELECT $relation[2],$relation[3] FROM $relation[1]";
				//echo $sql;
				$result = $db->sql_query($sql);
				$num = 0;
				$key_arr = array();
				$value_arr = array();
				while($line_arr = $db->sql_fetchrow($result))
				{
					$line = array_values($line_arr);
					$num++;
					list($key,$value) = $line;
					array_push($key_arr, $key);
					array_push($value_arr, $value);
				}
				$db->sql_freeresult($result);
				$key_value_arr = array($num, $key_arr, $value_arr);
				array_push($relation_val, $key_value_arr);
			}
			else if($relation[0] == 2 && count($relation) == 2) {
				$num = count($relation[1]);
				$key_arr = array_keys($relation[1]);
				$value_arr = array_values($relation[1]);
				$key_value_arr = array($num, $key_arr, $value_arr);
				array_push($relation_val, $key_value_arr);
			}
			else
				array_push($relation_val, 0);


			if($type == "date")
				$fn = "DATE_FORMAT(". $field_arr[$i] . ",'%Y-%m-%d')";
			else if($type == "time")
				$fn = "DATE_FORMAT(". $field_arr[$i] . ",'%H:%i:%s')";
			else if($type == "datetime")
				$fn = "DATE_FORMAT(". $field_arr[$i] . ",'%Y-%m-%d %H:%i:%s')";
			else
				$fn = $field_arr[$i];
			if($tbdisplay_arr[$i] == 1)
				$field .= "," . $fn;
		}

		//print_r($relation_val);
		if($pgno > 0)
			$startpos = ($pgno - 1) * $pagesize;
		else
			$startpos = 0;

		$order = $field_arr[$of];
		if($asc == 1)
			$order .= " DESC ";

		if($tbname == "SYSTEM_GROUPS")
			$sql = "SELECT $field FROM $_db_name.$tbname WHERE  sysgroup_is_identity=0 ORDER BY $order LIMIT $startpos, $pagesize";
		else
			$sql = "SELECT $field FROM $_db_name.$tbname ORDER BY $order LIMIT $startpos, $pagesize";
		//echo $sql;
		$result = $db->sql_query($sql);

		if($result) {
			while($line_arr = $db->sql_fetchrow($result))
			{
				$line = array_values($line_arr);
				$num++;
				$msg .= "<tr>\n";
				$id = $line[0];
				$j = 0;
				//$msg .= "	<td class=\"thc\" align=\"center\">$id</td>\n";
				for($i = 1; $i <= count($field_arr); $i++)
				{
					if($tbdisplay_arr[$i - 1] == 0)
						continue;
					$k = $i - 1;
					$j++;
					$v = $line[$j];

					if(empty($v) && $v != 0)
					{
						if($v != "")
						{
							if($field_len_arr[$k] == 1)
								$msg .= "	<td class=\"tdc\" align=\"center\">0</td>\n";
							else
								$msg .= "	<td class=\"tdc\" align=\"right\">0&nbsp;</td>\n";
						}
						else
						{
							$msg .= "	<td class=\"tdc\">&nbsp;</td>\n";
						}
					}
					else
					{
						$rr = 0;
						if($relation_arr[$k][0] > 0)
						{
							if($relation_val[$k][0] > 0 && count($relation_val[$k]) == 3)
							{
								$key_arr = $relation_val[$k][1];
								$value_arr = $relation_val[$k][2];
								for($pos = 0; $pos < count($key_arr); $pos++)
								{
									if($key_arr[$pos] == $v)
									{
										$rr = 1;
										$v = $value_arr[$pos];
										break;
									}
								}
							}
						}
						if($field_type_arr[$k] == "text" || $field_len_arr[$k] > 100)
						{
							$v = htmlspecialchars(substring($v, 100, false));
							$msg .= "	<td class=\"tdc\">&nbsp;$v</td>\n";
						}
						else if($field_len_arr[$k] == 1)
						{
							$msg .= "	<td class=\"tdc\" align=\"center\">$v</td>\n";
						}
						else if(($field_type_arr[$k] == "int") && ($rr == 0))
						{
							$msg .= "	<td class=\"tdc\" align=\"right\">$v&nbsp;</td>\n";
						}
						else
						{
							$msg .= "	<td class=\"tdc\">&nbsp;$v</td>\n";
						}
					}
				}
				if($edit_right)
					$msg .= "	<td class=\"thc\" align=\"center\"><a href=\"$pageurl?tid=$tb_id&pgno=$pgno&of=$of&asc=$asc&r_do=form&r_id=$id\" title=\"edit the $tbcnname\"><img src=\"images/edit.png\" border=\"0\"></a></td>\n";
				if($view_right)
					$msg .= "	<td class=\"thc\" align=\"center\"><a href=\"$pageurl?tid=$tb_id&pgno=$pgno&of=$of&asc=$asc&r_do=view&r_id=$id\" title=\"display the $tbcnname\"><img src=\"images/view.gif\" border=\"0\"></a></td>\n";
				if($tbname == "ROLES") {
					if($edit_right && $view_right)
						$msg .= "	<td class=\"thc\" align=\"center\"><a href=\"$pageurl?tid=$tb_id&pgno=$pgno&of=$of&asc=$asc&r_do=rrform&r_id=$id\" title=\"set right for this $tbcnname\"><img src=\"images/signtick.gif\" border=\"0\"></a></td>\n";
				}
				if($del_right)
					$msg .= "	<td class=\"thc\" align=\"center\"><a href=\"$pageurl?tid=$tb_id&pgno=$pgno&of=$of&asc=$asc&r_do=dele&r_id=$id\" title=\"delete the $tbcnname, then no restore after deleted\" onclick=\"return delok('$tbcnname');\"><img src=\"images/del.png\" border=\"0\"></a></td>\n";
				$msg .= "</tr>\n";
			}
			$db->sql_freeresult($result);
		}
	}

	$body = underline("$page_title $tbcnname List");

	$body .= "<table width=\"95%\" align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"tbframe\">\n";
	$body .= "<tr align=\"center\">\n";
	//$body .= "<th>NO</td>\n";
	for($i = 0; $i < count($field_arr); $i++) {
		 if($tbdisplay_arr[$i] == 1) {
			 if($field_type_arr[$i] == "password" || $field_type_arr[$i] == "text") {
				 $body .= "<th>$field_cn_arr[$i]</td>\n";
			 }
			 else {
			 /*
				 if($i == $of) {
					 if($asc == 1)
						 $body .= "<th><a href=\"$pageurl?tid=$tb_id&of=$i&asc=0\">$field_cn_arr[$i]</a><img src=\"images/down_arrow.gif\" border=\"0\"></td>\n";
					 else
						 $body .= "<th><a href=\"$pageurl?tid=$tb_id&of=$i&asc=1\">$field_cn_arr[$i]</a><img src=\"images/up_arrow.gif\" border=\"0\"></td>\n";
				 }
				 else
					 $body .= "<th><a href=\"$pageurl?tid=$tb_id&of=$i&asc=$asc\">$field_cn_arr[$i]</a></td>\n";
		     */
             $body .= "<th>$field_cn_arr[$i]<a href=\"$pageurl?tid=$tb_id&of=$i&asc=1\"><img src=\"images/up_arrow.gif\" border=\"0\"></a><a href=\"$pageurl?tid=$tb_id&of=$i&asc=0\"><img src=\"images/down_arrow.gif\" border=\"0\"></a></th>";

			 }
		 }
	}
	if($edit_right)
		$body .= "<th>Edit</td>\n";
	if($view_right)
		$body .= "<th>View</td>\n";
	if($tbname == "ROLES") {
		if($edit_right && $view_right)
			$body .= "<th>Right</td>\n";
	}
	if($del_right)
		$body .= "<th>Del</td>\n";
	$body .= "</tr>\n";
	if($num > 0)
		$body .= $msg;
	$body .= "</table>\n";

	if($num == 0)
		$body .= "no $tbcnname or last page!";

	return $body;
}



// form for add or edit the entry
function EditForm($tb_id, $id = 0, $pgno = 1, $of = "", $asc = 0)
{
	global $_db_name,$table_arr,$table_id_field_arr,$table_name_arr,$table_field_arr,$table_field_type_arr,$table_field_name_arr,$table_field_key_arr,$table_field_len_arr,$table_relation_arr,$table_note_arr;
	global $db, $pageurl, $page_title;


	$index = $tb_id - 1;
	$tbname = $table_arr[$index];
	$tb_fid = $table_id_field_arr[$index];
	$tbcnname = $table_name_arr[$index];
	$field_arr = $table_field_arr[$index];
	$field_cn_arr = $table_field_name_arr[$index];
	$field_type_arr = $table_field_type_arr[$index];
	$field_key_arr = $table_field_key_arr[$index];
	$field_len_arr = $table_field_len_arr[$index];
	$relation_arr = $table_relation_arr[$index];
	$note_arr = $table_note_arr[$index];

	$bFlag = false;
	if($id > 0 && count($field_arr) > 0)
	{
		$field = "";
		for($i = 0; $i < count($field_arr); $i++)
		{
			$type = $field_type_arr[$i];
			if($type == "date") {
				$fn = "DATE_FORMAT(". $field_arr[$i] . ",'%m/%d/%Y')";
			}
			else if($type == "time")
				$fn = "DATE_FORMAT(". $field_arr[$i] . ",'%H:%i:%s')";
			else if($type == "datetime") {
				$fn = "DATE_FORMAT(". $field_arr[$i] . ",'%m/%d/%Y %H:%i:%s')";
			}
			else
				$fn = $field_arr[$i];

			if($i == 0)
				$field = $fn;
			else
				$field .= "," . $fn;
		}

		if($tbname == "SYSTEM_GROUPS")
			$sql = "SELECT $field FROM $_db_name.$tbname WHERE $tb_fid='$id' AND sysgroup_is_identity=0";
		else
			$sql = "SELECT $field FROM $_db_name.$tbname WHERE $tb_fid='$id'";
		$result = $db->sql_query($sql);

		if($line_arr = $db->sql_fetchrow($result))
		{
			$line = array_values($line_arr);
			$bFlag = true;
		}
		$db->sql_freeresult($result);
	}
	// format data

	if($id > 0) {
		echo underline("$page_title $tbcnname Edit");
	?>
<table border="0" width="95%" align="center">
<form name="backform" method="post" action="<?=$pageurl?>">
<input type="hidden" name="tid" value="<?=$tb_id?>">
<input type="hidden" name="pgno" value="<?=$pgno?>">
<input type="hidden" name="of" value="<?=$of?>">
<input type="hidden" name="asc" value="<?=$asc?>">
<input type="hidden" name="r_do" value="list">
<tr>
	<td align="left"><font color="blue">*</font> = Required Field</td>
	<td align="right"><input type="submit" value="Back"></td>
</tr>
</form>
</table>
	<?
	}
	else
		echo underline("$page_title Add $tbcnname");
?>


<table width="95%" align="center" border="0" cellpadding="0" cellspacing="0" clASs="tbframe">
<form name="tbform" method="post" action="<?=$pageurl?>" onsubmit="return go(document.tbform);">
<input type="hidden" name="tid" value="<?=$tb_id?>">
<input type="hidden" name="pgno" value="<?=$pgno?>">
<input type="hidden" name="of" value="<?=$of?>">
<input type="hidden" name="asc" value="<?=$asc?>">

<?	if($bFlag) { ?>
<input type="hidden" name="r_do" value="edit">
<input type="hidden" name="r_id" value="<?=$id?>">
<?	} else { ?>
<input type="hidden" name="r_do" value="add">
<?
	}

	for($i = 0; $i < count($field_arr); $i++)
	{
		$ftype = $field_type_arr[$i];
		$relation = $relation_arr[$i];
		if($bFlag && isset($line[$i]))
			$nowval = $line[$i];
		else
			$nowval = null;

		if($relation[0] == 1 && count($relation) == 4)
		{
	?>
<tr>
	<td align="right" class="thc" width="200"><?=$field_cn_arr[$i]?>:</td>
	<td class="tdc">&nbsp;
	<?
			if($bFlag && isset($line[$i]))
				$nowval =  $line[$i];
			$sql = "SELECT $relation[2],$relation[3] FROM $relation[1]";
			//echo $sql;
			$result = $db->sql_query($sql);
			$num = 0;
			$optionlist = "";
			while($line_arr = $db->sql_fetchrow($result))
			{
				$oline = array_values($line_arr);
				$num++;
				list($key,$value) = $oline;
				if(isset($nowval) && $nowval == $key)
					$optionlist .= "<option value=\"$key\" selected>$value</option>\n";
				else
					$optionlist .= "<option value=\"$key\">$value</option>\n";
			}
			$db->sql_freeresult($result);

			if($num > 0)
			{
				echo "<select name=\"r_$field_arr[$i]\">\n";
				if(empty($optionlist))
					echo "<option value=\"0\">no select option</option>\n";
				echo $optionlist;
				echo "</select>\n";
			}
		}
		else if($relation[0] == 2 && count($relation) == 2) {
	?>
<tr>
	<td align="right" class="thc" width="200"><?=$field_cn_arr[$i]?>:</td>
	<td class="tdc">&nbsp;
		<?
			$arr = $relation[1];
			$num = count($arr);
			$optionlist = "";
			foreach($arr as $key=>$value) {
				if(isset($nowval) && $nowval == $key)
					$optionlist .= "<option value=\"$key\" selected>$value</option>\n";
				else
					$optionlist .= "<option value=\"$key\">$value</option>\n";
			}
			if($num > 0)
			{
				echo "<select name=\"r_$field_arr[$i]\">\n";
				if(empty($optionlist))
					echo "<option value=\"0\">no select option</option>\n";
				echo $optionlist;
				echo "</select>\n";
			}
		}
		else
		{
			if($ftype == "text")
			{
		?>
<tr>
	<td align="right" class="thc" width="200"><?=$field_cn_arr[$i]?>:</td>
	<td class="tdc">&nbsp;

	<textarea type="textarea"
	name="r_<?=$field_arr[$i]?>"
	title="<?=$field_cn_arr[$i]?>"
	datatype="<?=$field_type_arr[$i]?>"
	datalength="<?=$field_len_arr[$i]?>"
	isnull="<?if($field_key_arr[$i] > 0) echo "no"; else echo "yes"; ?>"
	size="30"
	maxlength="<?=$field_len_arr[$i]?>" cols="80" rows="5"><? if($bFlag && isset($line[$i])) echo $line[$i]; ?></textarea>
<?			} else if($ftype == "password"){ ?>
<tr>
	<td align="right" class="thc" width="200"><?=$field_cn_arr[$i]?>:</td>
	<td class="tdc">&nbsp;
	<input type="password"
	name="r_<?=$field_arr[$i]?>"
	title="<?=$field_cn_arr[$i]?>"
	datatype="<?=$field_type_arr[$i]?>"
	datalength="<?=$field_len_arr[$i]?>"
	isnull="<?if($field_key_arr[$i] > 0) echo "no"; else echo "yes"; ?>"
	value=""
	size="30"
	maxlength="<?=$field_len_arr[$i]?>">
	</td>
</tr>
<tr>
	<td align="right" class="thc" width="200">Confirm <?=$field_cn_arr[$i]?>:</td>
	<td class="tdc">&nbsp;
	<input type="password"
	id="r_<?=$field_arr[$i]?>_confirm"
	name="r_<?=$field_arr[$i]?>_confirm"
	title="<?=$field_cn_arr[$i]?>"
	datatype="<?=$field_type_arr[$i]?>"
	datalength="<?=$field_len_arr[$i]?>"
	isnull="<?if($field_key_arr[$i] > 0) echo "no"; else echo "yes"; ?>"
	value=""
	size="30"
	maxlength="<?=$field_len_arr[$i]?>">
<?			} else { ?>
<tr>
	<td align="right" class="thc" width="200"><?=$field_cn_arr[$i]?>:</td>
	<td class="tdc">&nbsp;
	<input type="text"
	name="r_<?=$field_arr[$i]?>"
	title="<?=$field_cn_arr[$i]?>"
	datatype="<?=$field_type_arr[$i]?>"
	datalength="<?=$field_len_arr[$i]?>"
	isnull="<?if($field_key_arr[$i] > 0) echo "no"; else echo "yes"; ?>"
	value="<? if($bFlag && isset($line[$i])) echo $line[$i]; ?>"
	size="<?if($field_len_arr[$i] >=30) echo "90"; else echo $field_len_arr[$i];?>"
	maxlength="<?=$field_len_arr[$i]?>" <?if($ftype == "email") echo "isemail=\"yes\"";?>>
<?				if($ftype == "date") {
					echo "<a href=\"#\" onclick=\"javascript:show_calendar('tbform.r_".$field_arr[$i]."');\"><img src=\"images/picker.gif\" width=24 height=22 border=0></a>";
				}
			}
		}
		if($field_key_arr[$i] > 0)
			echo "<font color=\"blue\">*</font> ";
		if(!empty($note_arr[$i]))
			echo "<i>".$note_arr[$i]."</i>";
		echo "</td></tr>\r\n";
	}
?>
</table>
<br>
<?
	if($tbname == "USERS")
		echo UserSystemRoleDefineTable($id, true);
	if($tbname == "SYSTEMS")
		echo SystemDefineTable($id, true);
?>
<table border="0" width="300">
<tr align="center">
	<!--<td><input type="submit" name="submit" value="Submit"></td>-->
	<? if($id > 0) { ?>
	<td><input type="submit" value="Update" title="submit your request"></td>
	<? } else { ?>
	<td><input type="submit" value="Create" title="submit your request"></td>
	<? } ?>
	<!--<td><input type="reset" name="reset" value="Reset"></td>-->
	<td><span style="cursor: pointer"><input type="reset" value="Reset" onclick="document.tbform.reset();"></span></td>
</tr>
</table>
</form>

<?
}

function DisplayItem($tb_id, $id = 0, $pgno = 1, $of = "", $asc = 0)
{
	global $_db_name,$table_arr,$table_id_field_arr,$table_name_arr,$table_field_arr,$table_field_type_arr,$table_field_name_arr,$table_field_key_arr,$table_field_len_arr,$table_relation_arr,$table_note_arr;
	global $db, $pageurl, $page_title;


	$index = $tb_id - 1;
	$tbname = $table_arr[$index];
	$tb_fid = $table_id_field_arr[$index];
	$tbcnname = $table_name_arr[$index];
	$field_arr = $table_field_arr[$index];
	$field_cn_arr = $table_field_name_arr[$index];
	$field_type_arr = $table_field_type_arr[$index];
	$field_key_arr = $table_field_key_arr[$index];
	$field_len_arr = $table_field_len_arr[$index];
	$relation_arr = $table_relation_arr[$index];
	$note_arr = $table_note_arr[$index];

	$bFlag = false;
	if($id > 0 && count($field_arr) > 0)
	{
		$field = "";
		for($i = 0; $i < count($field_arr); $i++)
		{
			$type = $field_type_arr[$i];
			if($type == "date") {
				$fn = "DATE_FORMAT(". $field_arr[$i] . ",'%m/%d/%Y')";
			}
			else if($type == "time")
				$fn = "DATE_FORMAT(". $field_arr[$i] . ",'%H:%i:%s')";
			else if($type == "datetime") {
				$fn = "DATE_FORMAT(". $field_arr[$i] . ",'%m/%d/%Y %H:%i:%s')";
			}
			else
				$fn = $field_arr[$i];

			if($i == 0)
				$field = $fn;
			else
				$field .= "," . $fn;
		}

		$sql = "SELECT $field FROM $_db_name.$tbname WHERE $tb_fid='$id'";
		$result = $db->sql_query($sql);

		if($line_arr = $db->sql_fetchrow($result))
		{
			$line = array_values($line_arr);
			$bFlag = true;
		}
		$db->sql_freeresult($result);
	}
	// format data

	echo underline("$page_title $tbcnname Detail");

	if(!$bFlag) {
		echo "<p>Not Exist this \"$tbcnname\" record</p>";
		return;
	}

?>

<table border="0" width="95%" align="center">
<form name="backform" method="post" action="<?=$pageurl?>">
<input type="hidden" name="tid" value="<?=$tb_id?>">
<input type="hidden" name="pgno" value="<?=$pgno?>">
<input type="hidden" name="of" value="<?=$of?>">
<input type="hidden" name="asc" value="<?=$asc?>">
<input type="hidden" name="r_do" value="list">
<tr>
	<td align="right"><input type="submit" value="Back"></td>
</tr>
</form>
</table>

<table width="95%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
<?
	for($i = 0; $i < count($field_arr); $i++)
	{
		$ftype = $field_type_arr[$i];
		$relation = $relation_arr[$i];
		if($bFlag && isset($line[$i]))
			$nowval = $line[$i];
		else
			$nowval = null;
?>
<tr>
	<td align="right" class="thc" width="200"><?=$field_cn_arr[$i]?>:</td>
	<td class="tdc">&nbsp;
<?
		if($relation[0] == 1 && count($relation) == 4)
		{
			if($bFlag && isset($line[$i]))
				$nowval =  $line[$i];
			$sql = "SELECT $relation[2],$relation[3] FROM $relation[1]";
			//echo $sql;
			$result = $db->sql_query($sql);
			$optionlist = "N/A";
			while($line_arr = $db->sql_fetchrow($result))
			{
				$oline = array_values($line_arr);
				list($key,$value) = $oline;
				if(isset($nowval) && $nowval == $key) {
					$optionlist = $value;
					break;
				}
			}
			$db->sql_freeresult($result);
			echo $optionlist;
		}
		else if($relation[0] == 2 && count($relation) == 2) {
			$arr = $relation[1];
			$num = count($arr);
			$optionlist = "N/A";
			foreach($arr as $key=>$value) {
				if(isset($nowval) && $nowval == $key) {
					$optionlist = $value;
					break;
				}
			}

			echo $optionlist;
		}
		else
		{
			if($ftype == "text") {
				echo htmlspecialchars($line[$i]);
			}
			else if($ftype == "password") {
				echo "****************";
			}
			else {
				echo htmlspecialchars($line[$i]);
			}
		}
		echo "</td></tr>\r\n";
	}

	echo "</table><br>";
	if($tbname == "USERS") {
		echo UserSystemRoleDefineTable($id, false);
	}
	if($tbname == "SYSTEMS") {
		echo SystemDefineTable($id, false);
	}
}

/**********************************************************************/
/**********************************************************************/
/**********************************************************************/
function underline($msg, $now = "") {
	$titleline = "<table width=\"95%\" align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"tbline\">\n";
	$titleline .= "<tr>\n";
	$titleline .= "	<td valign=\"bottom\"><!--<img src=\"images/greenball.gif\" border=\"0\"> -->$msg </td>\n";
	$titleline .= "	<td align=\"right\" valign=\"bottom\"> $now </td>\n";
	$titleline .= "</tr>\n";
	$titleline .= "</table>\n";
	$titleline .= "<br>\n";

	return $titleline;
}


function user_deactive_date($tbname, $uid) {
	global $db,$_db_name;

	$flag = 0;
	$sql = "SELECT user_is_active FROM $_db_name.$tbname WHERE user_id='$uid'";
	$result = $db->sql_query($sql);
	if($line_arr = $db->sql_fetchrow($result)) {
		$flag = $line_arr['user_is_active'];
	}
	$db->sql_freeresult($result);

	// set deactive date by user_is_active value
	if(!$flag) {
		// 0: suspend
		//$now = strftime("%Y-%m-%d", (mktime(0, 0, 0, date("m")  , date("d"), date("Y"))));
		$now = date("Y-m-d H:m:s");
		$sql = "UPDATE $_db_name.$tbname SET user_date_deleted='$now' WHERE user_id='$uid'";
	}
	else {
		// 1: active
		$sql = "UPDATE $_db_name.$tbname SET user_date_deleted='' WHERE user_id='$uid'";
	}
	$res = $db->sql_query($sql);

	return $res;
}

function user_create_date($tbname, $uid) {
	global $db, $_db_name;

	//$now = strftime("%Y-%m-%d", (mktime(0, 0, 0, date("m")  , date("d"), date("Y"))));
	$now = date("Y-m-d H:m:s");
	$sql = "UPDATE $_db_name.$tbname SET user_date_created='$now' WHERE user_id='$uid'";
	$res = $db->sql_query($sql);

	return $res;
}

function user_change_password($tbname, $uid) {
	global $db, $_db_name;

	// reset user_date_password, then user login again need enforce to change password
	$sql = "UPDATE $_db_name.$tbname SET user_date_password='0' WHERE user_id='$uid'";
	$res = $db->sql_query($sql);

	return $res;
}
?>
