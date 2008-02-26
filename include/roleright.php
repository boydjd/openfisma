<?PHP
/****************************************************************************************/
/******************role right config, setting his function point*************************/
/****************************************************************************************/
function RoleFunctionDefine($rid, $post) {
	global $db,$_db_name;

	$sql = "DELETE FROM " . TN_ROLE_FUNCTIONS . " WHERE role_id='$rid'";
	$res = $db->sql_query($sql);

	$errnum = 0;
	if($res) {
		foreach($post as $fname=>$fid) {
			if(substr($fname, 0, 9) == "function_") {
				$sql = "INSERT INTO " . TN_ROLE_FUNCTIONS . " (role_id, function_id) VALUES ('$rid', '$fid')";
				//echo $sql;
				$res  = $db->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
				if(!$res)
					$errnum++;
			}
		}
	}
	if($errnum > 0)
		$msg = "Error setting rights for role, please try again";
	else
		$msg = "Successfully set right for role.";

	return $msg;
}


function RoleFunctionDefineForm($tb_id,$pgno,$of,$asc,$rid, $edit_right) {
	global $db, $pageurl, $page_title;

	$sql = "SELECT role_name FROM " . TN_ROLES . " WHERE role_id='$rid'";
	$result = $db->sql_query($sql);
	if($result && $line_arr = $db->sql_fetchrow($result)) {
		$rolename = $line_arr['role_name'];
		$db->sql_freeresult($result);
	}

	$msg = RoleFunctionDefineTable($rid, $edit_right);
?>
<table border="0" width="100%">
<form name="backform" method="post" action="<?=$pageurl?>">
<input type="hidden" name="tid" value="<?=$tb_id?>">
<input type="hidden" name="pgno" value="<?=$pgno?>">
<input type="hidden" name="of" value="<?=$of?>">
<input type="hidden" name="asc" value="<?=$asc?>">
<input type="hidden" name="r_do" value="list">
<tr>
	<td><b>Role Name:</b> <?=$rolename?></td>
	<td>&nbsp;&nbsp;</td>
	<td><span style="cursor: pointer"><input type="button" value="Select All" onclick="selectall('rtable', 'function_', true);">&nbsp;
	<span style="cursor: pointer"><input type="button" value="Select None" onclick="selectall('rtable', 'function_', false);"></td>
	<td align="right"><input type="submit" value="Back" name="back"></td>
</tr>
</form>
</table>

<?php 	if($edit_right) { ?>
<script language="javascript" src="javascripts/func.js"></script>
<form name="rtable" method="post" action="<?=$pageurl?>">
<input type="hidden" name="tid" value="<?=$tb_id?>">
<input type="hidden" name="pgno" value="<?=$pgno?>">
<input type="hidden" name="of" value="<?=$of?>">
<input type="hidden" name="asc" value="<?=$asc?>">
<input type="hidden" name="r_do" value="rright">

<input type="hidden" name="r_id" value="<?=$rid?>">
<?=$msg?>
<table border="0" width="300">
<tr align="center">
	<td><input type="submit" value="Update"></td>
	<td><input type="reset" onclick="document.rtable.reset();" value="Reset"></span></td>
</tr>
</table>
</form>
<?php 	} else {
	echo $msg;
	}
}


function RoleFunctionDefineTable($rid, $edit_right) {
	global $db,$_db_name;
	$cols = 3;
	$tdwidth = 250;//ceil(100 / $cols);
	$msg = "";

	$fid_arr = array();

	$sql = "SELECT function_id FROM " . TN_ROLE_FUNCTIONS . " WHERE role_id='$rid' ORDER BY function_id";
	$result = $db->sql_query($sql);

	if($result) {
		while($line_arr = $db->sql_fetchrow($result)) {
			$fid_arr[] = $line_arr['function_id'];
		}
		$db->sql_freeresult($result);
		//print_r($fid_arr);
	}
	$sql = "SELECT function_screen,function_id,function_name,function_desc FROM " . TN_FUNCTIONS . " WHERE function_open = 1 ORDER BY function_screen,function_id";
	$result = $db->sql_query($sql);

	if($result) {
		$num = 0;
		$snum = 0;
		$tscreen = "";
		$section = "";
		while($line_arr = $db->sql_fetchrow($result)) {
			$fscreen = $line_arr['function_screen'];
			$fid = $line_arr['function_id'];
			$fname = $line_arr['function_name'];
			//$faction = $line_arr['function_action'];
			$fdesc = $line_arr['function_desc'];
			if(empty($fscreen))
				continue;

			if($tscreen != $fscreen) {
				if($num > 0) {
					if($snum % $cols != 0) {
						$colspan = ($cols - $snum % $cols) * 2;
						$section .= "<td colspan='$colspan'>&nbsp;</td></tr>\r\n";
					}
					$section = "<table border=\"0\">" . $section . "</table>\r\n";

					$msg .= $section . "</fieldset></td></tr>\r\n";
				}
				$colspan = $cols * 2;
				$msg .= "<tr>\r\n";
				$msg .= "<td><fieldset style=\"border:1px solid #44637A; padding:3\"><legend><b>$fscreen</b></legend>\r\n";

				$tscreen = $fscreen;
				$section = "";
				$snum = 0;
			}


			if($snum % $cols == 0) {
				$section .= "<tr>\r\n";
			}
			$num++;
			$snum++;

			$fontcolor = "";
			$fonttail = "";
			$handcursor = " style=\"cursor: pointer\"";
			if(in_array($fid, $fid_arr)) {
				$checkflag = " checked";
			}
			else {
				if(!$edit_right) {
					$fontcolor = "<font color=\"888888\">";
					$fonttail = "</font>";
				}
				$checkflag = "";
			}

			if(!$edit_right) {
				$checkflag .= " onclick=\"return false;\"";
				$handcursor = "";
			}

			$section .= "<td width=\"30\" align=\"right\"><input type='checkbox' id='f_$fid' name='function_$fid' value='$fid' $checkflag></td>\r\n";
			$section .= "<td width=\"$tdwidth\">$fontcolor<label for='f_$fid'><span title=\"$fdesc\" $handcursor>$fname</span></label>$fonttail</td>\r\n";
			if($snum % $cols == $cols)
				$section .= "</tr>\r\n";
		}

		if($num > 0) {
			if($snum % $cols != $cols) {
				$colspan = ($cols - $snum % $cols) * 2;
				$section .= "<td colspan='$colspan'>&nbsp;</td></tr>\r\n";
			}
			$section = "<table border=\"0\">" . $section . "</table>\r\n";

			$msg .= $section . "</fieldset></td></tr>\r\n";
			$msg = "<table border=\"0\" width=\"100%\" align=\"center\">" . $msg . "</table>\r\n";
		}

		$db->sql_freeresult($result);
	}

	return $msg;
}


/****************************************************************************************/
/******************User's system group config, setting user's system group***************/
/****************************************************************************************/
function UserSystemRoleDefine($uid, $post) {
	global $db,$_db_name;

	$rid = 0;
	$sql = "DELETE FROM " . TN_USER_SYSTEM_ROLES . " WHERE user_id='$uid'";
	$res = $db->sql_query($sql);

	$sql = "SELECT role_id FROM " . TN_USERS . " WHERE user_id='$uid'";
	$result = $db->sql_query($sql);
	if($result && $line_arr = $db->sql_fetchrow($result)) {
		$rid = $line_arr['role_id'];
		$db->sql_freeresult($result);
		//print_r($sgid_arr);
	}

	$errnum = 0;
	if($rid > 0) {
		foreach($post as $sysname=>$sysid) {
			if(substr($sysname, 0, 7) == "system_") {
				$sql = "INSERT INTO " . TN_USER_SYSTEM_ROLES . " (user_id, system_id, role_id) VALUES ('$uid', '$sysid', '$rid')";
				//echo $sql;
				$res  = $db->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
				if(!$res)
					$errnum++;
			}
		}
	}
	if($errnum > 0)
		$msg = "Error setting user's system, please try again";
	else
		$msg = "Successfully set system for user.";

	return $msg;
}


function UserSystemRoleDefineTable($uid, $editflag) {
	global $db,$_db_name;
	$cols = 4;
	//$tdwidth = 170;//ceil(100 / $cols);
	$msg = "";
	$section = "";

	$sysid_arr = array();

	$sql = "SELECT system_id FROM " . TN_USER_SYSTEM_ROLES . " WHERE user_id='$uid' ORDER BY system_id";
	$result = $db->sql_query($sql);

	if($result) {
		while($line_arr = $db->sql_fetchrow($result)) {
			$sysid_arr[] = $line_arr['system_id'];
		}
		$db->sql_freeresult($result);
		//print_r($sgid_arr);
	}
	$sql = "SELECT system_name,system_id,system_nickname FROM " . TN_SYSTEMS . " ORDER BY system_id";
	$result = $db->sql_query($sql);

	if($result) {
		$num = 0;
		while($line_arr = $db->sql_fetchrow($result)) {
			$sysname = $line_arr['system_name'];
			$sysid = $line_arr['system_id'];
			$sysnickname = $line_arr['system_nickname'];

			if($num % $cols == 0) {
				$section .= "<tr>\r\n";
			}
			$num++;

			$fontcolor = "";
			$fonttail = "";
			$handcursor = " style=\"cursor: pointer\"";
			if(in_array($sysid, $sysid_arr)) {
				$checkflag = " checked";
			}
			else {
				if(!$editflag) {
					$fontcolor = "<font color=\"888888\">";
					$fonttail = "</font>";
				}
				$checkflag = "";
			}

			if(!$editflag) {
				$checkflag .= " onclick=\"return false;\"";
				$handcursor = "";
			}

			$section .= "<td align=\"right\"><input type='checkbox' id='sys_$sysid' name='system_$sysid' value='$sysid' $checkflag></td>\r\n";
			$section .= "<td>$fontcolor<label for='sys_$sysid'><span title=\"$sysnickname\" $handcursor>$sysname</span></label>$fonttail</td>\r\n";
			if($num % $cols == $cols)
				$section .= "</tr>\r\n";
		}

		if($num > 0) {
			if($num % $cols != 0) {
				$colspan = ($cols - $num % $cols) * 2;
				$section .= "<td colspan='$colspan'>&nbsp;</td></tr>\r\n";
			}

			$msg .= "<br>";
			$msg .= "<div align=\"right\"><span style=\"cursor: pointer\"><input type=\"button\" value=\"Select All\" onclick=\"selectall('tbform', 'system_', true);\">>&nbsp;";
			$msg .= "<span style=\"cursor: pointer\"><input type=\"button\" value=\"Select None\" onclick=\"selectall('tbform', 'system_', false);\"></div>";

			$msg .= "<fieldset style=\"border:1px solid #BEBEBE; padding:3\"><legend><b>Systems</b></legend>\r\n";
			$msg .= "<input type=\"hidden\" name=\"p_checkhead\" value=\"system_\">\r\n";
			$msg .= "<input type=\"hidden\" name=\"p_checktip\" value=\"System\">\r\n";
			$msg .= "<table border=\"0\" width=\"100%\">\r\n";
			$msg .= $section;
			$msg .= "</table>";
			$msg .= "</fieldset><br>\r\n";

			//$msg .= "<input type=\"button\" name=\"test\" value=\"Test\" onclick=\"mustHaveCheckbox(document.tbform, 'system_');\">\r\n";
		}

		$db->sql_freeresult($result);
	}

	return $msg;
}



/****************************************************************************************/
/******************System's system group config, setting system's system group***********/
/****************************************************************************************/
function SystemDefine($sid, $post) {
	global $db,$_db_name;

	$sysgroup_id = SystemGroup4Self($sid, $post['r_system_name'], $post['r_system_nickname']);

	$sql = "DELETE FROM " . TN_SYSTEM_GROUP_SYSTEMS . " WHERE system_id='$sid'";
	$res = $db->sql_query($sql);

	$errnum = 0;
	if($res) {
		// same name's system group
		$sql = "INSERT INTO " . TN_SYSTEM_GROUP_SYSTEMS . " (system_id, sysgroup_id) VALUES ('$sid', '$sysgroup_id')";
		$res  = $db->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
		if(!$res)
			$errnum++;

		foreach($post as $sgname=>$sgid) {
			if(substr($sgname, 0, 9) == "sysgroup_") {
				$sql = "INSERT INTO " . TN_SYSTEM_GROUP_SYSTEMS . " (system_id, sysgroup_id) VALUES ('$sid', '$sgid')";
				//echo $sql;
				$res  = $db->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
				if(!$res)
					$errnum++;
			}
		}
	}
	if($errnum > 0)
		$msg = "Error setting system's system group, please try again";
	else
		$msg = "Successfully set system group for System.";

	return $msg;
}


function SystemGroup4Self($id, $sname, $nname) {
	global $db,$_db_name;
	$sysgroup_id = 0;

	$sql = "SELECT sgs.sysgroup_id AS system_id FROM " . TN_SYSTEM_GROUP_SYSTEMS . " AS sgs, " . TN_SYSTEM_GROUPS . " AS sg
				WHERE sgs.sysgroup_id=sg.sysgroup_id AND sgs.system_id='$id' AND sg.sysgroup_is_identity=1";
	$result = $db->sql_query($sql);

	if($result && $line_arr = $db->sql_fetchrow($result)) {
		$sysgroup_id = $line_arr['sysgroup_id'];
		$db->sql_freeresult($result);
		//print_r($sgid_arr);
	}

	if($sysgroup_id == 0) {
		$sql = "INSERT INTO " . TN_SYSTEM_GROUPS . " (sysgroup_name,sysgroup_nickname,sysgroup_is_identity) VALUES ('$sname','$nname',1)";
		$res = $db->sql_query($sql);
		$sysgroup_id = $db->sql_nextid();
	}
	else {
		$sql = "UPDATE " . TN_SYSTEM_GROUPS . " SET sysgroup_name='$sname',sysgroup_nickname='$nname' WHERE sysgroup_id='$sysgroup_id'";
		$res = $db->sql_query($sql);
	}

	return $sysgroup_id;
}



function SystemDefineTable($sid, $editflag) {
	global $db,$_db_name;
	$cols = 4;
	//$tdwidth = 170;//ceil(100 / $cols);
	$msg = "";
	$section = "";

	$sgid_arr = array();

	$sql = "SELECT sysgroup_id FROM " . TN_SYSTEM_GROUP_SYSTEMS . " WHERE system_id='$sid' ORDER BY sysgroup_id";
	$result = $db->sql_query($sql);

	if($result) {
		while($line_arr = $db->sql_fetchrow($result)) {
			$sgid_arr[] = $line_arr['sysgroup_id'];
		}
		$db->sql_freeresult($result);
		//print_r($sgid_arr);
	}
	$sql = "SELECT sysgroup_name,sysgroup_id,sysgroup_nickname FROM " . TN_SYSTEM_GROUPS . " WHERE sysgroup_is_identity=0 ORDER BY sysgroup_id";
	$result = $db->sql_query($sql);

	if($result) {
		$num = 0;
		while($line_arr = $db->sql_fetchrow($result)) {
			$sgname = $line_arr['sysgroup_name'];
			$sgid = $line_arr['sysgroup_id'];
			$sgnickname = $line_arr['sysgroup_nickname'];

			if($num % $cols == 0) {
				$section .= "<tr>\r\n";
			}
			$num++;

			$fontcolor = "";
			$fonttail = "";
			$handcursor = " style=\"cursor: pointer\"";
			if(in_array($sgid, $sgid_arr)) {
				$checkflag = " checked";
			}
			else {
				if(!$editflag) {
					$fontcolor = "<font color=\"888888\">";
					$fonttail = "</font>";
				}
				$checkflag = "";
			}

			if(!$editflag) {
				$checkflag .= " onclick=\"return false;\"";
				$handcursor = "";
			}

			$section .= "<td align=\"right\"><input type='checkbox' id='sg_$sgid' name='sysgroup_$sgid' value='$sgid' $checkflag></td>\r\n";
			$section .= "<td>$fontcolor<label for='sg_$sgid'><span title=\"$sgnickname\" $handcursor>$sgname</span></label>$fonttail</td>\r\n";
			if($num % $cols == $cols)
				$section .= "</tr>\r\n";
		}

		if($num > 0) {
			if($num % $cols != 0) {
				$colspan = ($cols - $num % $cols) * 2;
				$section .= "<td colspan='$colspan'>&nbsp;</td></tr>\r\n";
			}
			//$msg = "<br>";
			$msg .= "<fieldset style=\"border:1px solid #BEBEBE; padding:3\"><legend><b>System Groups</b></legend>\r\n";
			$msg .= "<input type=\"hidden\" name=\"p_checkhead\" value=\"sysgroup_\">\r\n";
			$msg .= "<input type=\"hidden\" name=\"p_checktip\" value=\"System Group\">\r\n";
			$msg .= "<table border=\"0\" width=\"100%\">\r\n";
			$msg .= $section;
			$msg .= "</table>";
			$msg .= "</fieldset><br>\r\n";

			//$msg .= "<input type=\"button\" name=\"test\" value=\"Test\" onclick=\"mustHaveCheckbox(document.tbform, 'sysgroup_');\">\r\n";
		}

		$db->sql_freeresult($result);
	}

	return $msg;
}
?>
