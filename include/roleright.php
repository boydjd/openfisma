<?PHP
/****************************************************************************************/
/******************role right config, setting his function point*************************/
/****************************************************************************************/
/**
 *this function for assign functions to a user
 *@global $db,$db_name
 *@param string $uid which user is assigned
 *@param array $role_array which roles is assigned
 *@param array $right_array which privileges to be assigned
 *return string
 */
function UserFunctionDefine($uid,$role_array,$right_array) {
    global $db,$db_name;
    $errnum = 0;

    //$sql = "SELECT role_id FROM " . TN_ROLES . " WHERE role_name = '$uid'";
    $sql = "SELECT r.role_id FROM " . TN_ROLES . "r, " . TN_USERS . "u WHERE r.role_name = u.extra_role AND u.user_id = '$uid'";
    $res = $db->sql_query($sql);
    $line_array = $db->sql_fetchrow($res);
    if ($line_array) {
        $role_id = $line_array['role_id'];
        $sql = "DELETE FROM " . TN_USER_ROLES . " WHERE user_id = $uid AND role_id!=$role_id";
        $result = $db->sql_query($sql);
        if ($result) {
            foreach ($role_array as $rid) {
                $sql = "INSERT INTO " . TN_USER_ROLES . " (user_id,role_id) VALUES ('$uid','$rid')";
                $res = $db->sql_query($sql) or die("Query failed : " . $this->dbConn->sql_error());
                if (!$res) {
                    $errnum++;
                }
            }
        }
        $sql = "DELETE FROM " . TN_ROLE_FUNCTIONS . " WHERE role_id = $role_id";
        $result = $db->sql_query($sql);
        if ($result) {
            if (!empty($right_array)) {
                foreach ($right_array as $right_id) {
                    $sql = "INSERT INTO " . TN_ROLE_FUNCTIONS . " (role_id,function_id) VALUES ($role_id,$right_id)";
                    $res = $db->sql_query($sql);
                    if (!$res) {
                        $errnum++;
                    }
                }
            } else {
                $sql = "DELETE FROM " . TN_ROLE_FUNCTIONS . " WHERE role_id = $role_id";
                $result = $db->sql_query($sql);
                if (!$result) {
                    $errnum++;
                }
            }
        }
    } else {
        if (!empty($right_array)) {
            $sql = "DELETE FROM " . TN_USER_ROLES . " WHERE user_id = $uid";
            $result = $db->sql_query($sql);
            foreach ($role_array as $rid) {
                $sql = "INSERT INTO " . TN_USER_ROLES . " (user_id,role_id) VALUES ('$uid','$rid')";
                $res = $db->sql_query($sql);
                if (!$res) {
                    $errnum++;
                }
            }
            $sql = "SELECT extra_role FROM " . TN_USERS . " WHERE user_id=$uid";
            $line_array = $db->sql_fetchrow($db->sql_query($sql));
            $sql = "INSERT INTO " . TN_ROLES . " (role_name,role_nickname,role_desc)VALUES ('$line_array[extra_role]','none','extra role for users')";
            $result = $db->sql_query($sql);
            if ($result) {
                $role_id = $db->sql_nextid();
                $sql = "INSERT INTO " . TN_USER_ROLES . "(user_id,role_id)VALUES ($uid,$role_id)";
                $result = $db->sql_query($sql);
                if ($result) {
                    foreach ($right_array as $right_id) {
                        $sql = "INSERT INTO " . TN_ROLE_FUNCTIONS . " (role_id,function_id) VALUES ($role_id,$right_id)";
                        $res = $db->sql_query($sql);
                        if (!$res) {
                            $errnum++;
                        }
                    }
                }
            }
        } else {
            $sql = "DELETE FROM " . TN_USER_ROLES . " WHERE user_id = $uid";
            $result = $db->sql_query($sql);
            foreach ($role_array as $role_id) {
                $sql = "INSERT INTO " . TN_USER_ROLES . " (user_id,role_id) VALUES ('$uid','$role_id')";
                $res = $db->sql_query($sql) or die("Query failed : " . $this->dbConn->sql_error());
                if (!$res) {
                    $errnum++;
                }
            }
        }
    }

    if($errnum > 0)
        $msg = "Error setting rights for User, please try again";
    else
        $msg = "Successfully set right for User.";

    return $msg;

}

function UserFunctionDefineForm($tb_id,$pgno,$of,$asc,$uid, $edit_right) {
    global $db, $pageurl, $page_title;

    $sql = "SELECT user_name FROM " . TN_USERS . " WHERE user_id='$uid'";
    $result = $db->sql_query($sql);
    if($result && $line_arr = $db->sql_fetchrow($result)) {
                $username = $line_arr['user_name'];
        $db->sql_freeresult($result);
    }

    $msg = UserFunctionDefineTable($uid, $username, $edit_right);
?>
<table border="0" width="100%">
<form name="backform" method="post" action="<?php echo $pageurl?>">
<input type="hidden" name="tid"  value="<?php echo $tb_id?>">
<input type="hidden" name="pgno" value="<?php echo $pgno?>">
<input type="hidden" name="of"  value="<?php echo $of?>">
<input type="hidden" name="asc"  value="<?php echo $asc?>">
<input type="hidden" name="r_do"  value="form">
<input type="hidden" name="r_id"  value="<?php echo $uid?>">
<tr>
    <td><b>User Name:</b> <?php echo $username?></td>
    <td>&nbsp;&nbsp;</td>
    <td align="right"><input type="submit" value="Back" name="back"></td>
</tr>
</form>
</table>

<?php     if($edit_right) { ?>
<script language="javascript" src="javascripts/func.js"></script>
<form name="rtable" id="rtable" method="post" action="<?php echo $pageurl?>">
<input type="hidden" name="tid"  id="menu" value="<?php echo $tb_id?>">
<input type="hidden" name="pgno"  id="pgno" value="<?php echo $pgno?>">
<input type="hidden" name="of" id="of" value="<?php echo $of?>">
<input type="hidden" name="asc" id="asc" value="<?php echo $asc?>">
<input type="hidden" name="u_do" id="u_do" value="uright">
<input type="hidden" name="u_id" id="u_id" value="<?php echo $uid?>">
<?php echo $msg?>
<table border="0" width="300">
<tr align="center">
    <td><input type=button value=Update onclick=assign()></td>
    <td><input type="reset" onclick="document.rtable.reset();" value="Reset"></td>
</tr>
</table>
</form>
<?php     } else {
    echo $msg;
    }
}


function UserFunctionDefineTable($uid, $username, $edit_right) {
    global $db,$_db_name;
    $role_have_msg = "";
    $role_none_msg = "";
    $right_have_msg = "";
    $right_none_msg = "";

    $cols = 3;
    $tdwidth = 250;//ceil(100 / $cols);
    $msg = "";
    //select all roles except the extra role
    $sql = "SELECT role_id,role_nickname FROM " . TN_ROLES . " WHERE role_nickname <>'none' ORDER BY role_id";
    $result = $db->sql_query($sql);
    if($result) {
        while($line_arr = $db->sql_fetchrow($result)) {
             $role_nickname[$line_arr['role_id']] = $line_arr['role_nickname'];
        }
    }
    //select user's roles except the extra role/*
    $sql = "SELECT ur.role_id,r.role_nickname FROM " . TN_USER_ROLES . " ur," . TN_ROLES . " r, " . TN_USERS . "u WHERE
            ur.user_id=$uid AND ur.role_id = r.role_id AND r.role_name <> u.extra_role AND u.user_id=$uid";
    $result = $db->sql_query($sql);
    if($result) {
        while($line_arr = $db->sql_fetchrow($result)) {
             $role_nickname_have[$line_arr['role_id']] = $line_arr['role_nickname'];
        }
    }
    //get roles which user not have
    $role_nickname_none = array_diff($role_nickname,$role_nickname_have);

    if(!empty($role_nickname_none)) {
        foreach ($role_nickname_none as $role_id =>$nickname){
            $role_none_msg.= '<option value='.$role_id.'>'.$nickname.'</option>';
        }
    }

    if (!empty($role_nickname_have)) {
        foreach ($role_nickname_have as $role_id =>$nickname){
            $role_have_msg.= '<option value="'.$role_id.'" selected="selected">'.$nickname.'</option>';
        }
    }

    //select functions which user have
    $sql = "SELECT rf.function_id,f.function_name,f.function_desc FROM " . TN_FUNCTIONS . " f," . TN_ROLE_FUNCTIONS . " rf
            ," . TN_USER_ROLES . " ur WHERE ur.user_id = $uid AND ur.role_id = rf.role_id AND rf.function_id = f.function_id";
    $result = $db->sql_query($sql);
    if ($result) {
        while ($line_arr = $db->sql_fetchrow($result)) {
            $fid_arr_have[$line_arr['function_id']] = array($line_arr['function_name'],$line_arr['function_desc']);
        }
    }

    //select all functions
    $sql = "SELECT function_id,function_name,function_desc FROM " . TN_FUNCTIONS . " ORDER BY function_id";
    $result = $db->sql_query($sql);
    if ($result) {
        while ($line_arr = $db->sql_fetchrow($result)) {
            $fid_arr[$line_arr['function_id']] = array('function_name'=>$line_arr['function_name'],'function_desc'=>$line_arr['function_desc']);
        }
    }
    //get functions which user not have
    $fid_arr_none = array_diff_assoc($fid_arr,$fid_arr_have);
    if (!empty($fid_arr_none)) {
        foreach ($fid_arr_none as $fid=>$function){
            $right_none_msg.='<option value='.$fid.' title="'.$function['function_desc'].'">'.$function['function_name'].'</option>';
        }
    }

    $sql = "SELECT rf.function_id,f.function_name,f.function_desc FROM " . TN_FUNCTIONS . " f," . TN_ROLES . " r, " . TN_ROLE_FUNCTIONS . " rf," . TN_USERS . " u
            WHERE u.user_id =$uid AND r.role_name = u.extra_role AND r.role_id = rf.role_id AND rf.function_id = f.function_id";
    $result = $db->sql_query($sql);
    if ($result) {
        while ($line_arr = $db->sql_fetchrow($result)) {
            $right_have_msg.='<option value="'.$line_arr['function_id'].'" title="'.$line_arr['function_desc'].'" selected="selected">'.$line_arr['function_name'].'</option>';
        }
    }
        $msg = '<fieldset style="border: 1px solid rgb(68, 99, 122); padding: 3px;"><legend><b></b></legend>
                <table height="205" border="0" style="margin-left:5">
                <tr><td><b>Available Roles</b></td><td></td><td><b>Assigned Roles</b></td></tr>
                <tr>
                <td width="254">
                <select multiple size="7" name="role_none" style="width:250px">'.$role_none_msg.'</select>
                <b>Available Privileges</b><br><br>
                <select multiple size="24" name="right_none" style="width:250px">'.$right_none_msg.'</select></td>
                <td width="54" valign="top">
                <input type="button" value="   ->    " onclick="move(this.form.role_none,this.form.role_have)" name="B1">
                <br><br>
                <input type="button" value="   <-    " onclick="move(this.form.role_have,this.form.role_none)" name="B1">
                <br><br><br><br><br><br><br><br>
                <input type="button" value="   ->    " onclick="move(this.form.right_none,this.form.right_have)" name="B1">
                <br><br>
                <input type="button" value="   <-    " onclick="move(this.form.right_have,this.form.right_none)" name="B1"></td>
                <td width="302">
                <select multiple size="7" id="role_have" name="role_have" style="width:250px">'.$role_have_msg.'</select>
                <b>Assigned Privileges</b><br><br>
                <select multiple size="24" id="right_have" name="right_have" style="width:250px">'.$right_have_msg.'</select></td>
                </tr>
                </table></fieldset>';
         return $msg;
}



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
<form name="backform" method="post" action="<?php echo $pageurl?>">
<input type="hidden" name="tid" value="<?php echo $tb_id?>">
<input type="hidden" name="pgno" value="<?php echo $pgno?>">
<input type="hidden" name="of" value="<?php echo $of?>">
<input type="hidden" name="asc" value="<?php echo $asc?>">
<input type="hidden" name="r_do" value="list">
<tr>
	<td><b>Role Name:</b> <?php echo $rolename?></td>
	<td>&nbsp;&nbsp;</td>
	<td><span style="cursor: pointer"><input type="button" value="Select All" onclick="selectall('rtable', 'function_', true);">&nbsp;
	<span style="cursor: pointer"><input type="button" value="Select None" onclick="selectall('rtable', 'function_', false);"></td>
	<td align="right"><input type="submit" value="Back" name="back"></td>
</tr>
</form>
</table>

<?php 	if($edit_right) { ?>
<script language="javascript" src="javascripts/func.js"></script>
<form name="rtable" method="post" action="<?php echo $pageurl?>">
<input type="hidden" name="tid" value="<?php echo $tb_id?>">
<input type="hidden" name="pgno" value="<?php echo $pgno?>">
<input type="hidden" name="of" value="<?php echo $of?>">
<input type="hidden" name="asc" value="<?php echo $asc?>">
<input type="hidden" name="r_do" value="rright">

<input type="hidden" name="r_id" value="<?php echo $rid?>">
<?php echo $msg?>
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
