<?PHP
// require the user file
require_once("ovms.ini.php");

$_db_name = $DB;

$table_class			= array(1=>"Administration",
								);
$table_class_index		= array(array(0, 1),
								array(1, 1),
								array(2, 1),
								array(3, 1),
								array(4, 1),
								array(5, 1),
								array(6, 1),
								);
// database.table (table name, include database name)
$table_arr				= array("USERS",
								"ROLES",
								"SYSTEMS",
								"PRODUCTS",
								"SYSTEM_GROUPS",
								"FUNCTIONS",
								"FINDING_SOURCES",
								);
/************************************************************************************/
// table name for display
$table_name_arr			= array("Users",
								"Roles",
								"Systems",
								"Products",
								"System Groups",
								"Functions",
								"Finding Sources",
								);
/************************************************************************************/
// table "id" field
$table_id_field_arr		= array("user_id",
								"role_id",
								"system_id",
								"prod_id",
								"sysgroup_id",
								"function_id",
								"source_id",
								);
/************************************************************************************/
// table fields except "id"
								// array("user_name","user_password","role_id","user_title","user_name_first","user_name_middle","user_name_last","user_date_created","user_date_password","user_date_last_login","user_date_deleted","user_is_active","user_phone_office","user_phone_mobile","user_email"),
$table_field_arr		= array(array("user_name_last","user_name_first","user_phone_office","user_phone_mobile","user_email","role_id","user_title","user_is_active","user_name","user_password"),
								array("role_name","role_nickname","role_desc"),
								array("system_name","system_nickname","system_primary_office","system_availability","system_integrity","system_confidentiality","system_tier","system_type","system_desc","system_criticality_justification","system_sensitivity_justification"),
								//array("prod_name","prod_nvd_defined","prod_meta","prod_vendor","prod_version","prod_desc"),
								array("prod_name","prod_vendor","prod_version","prod_desc"),
								array("sysgroup_name","sysgroup_nickname"),
								array("function_name","function_screen","function_action","function_open","function_desc"),
								array("source_name","source_nickname","source_desc"),
								);
/************************************************************************************/
// fields name for display
								// array("Username","Password","Role","Title","First name","Middle name","Last name","Created date","Change password date","Last login","Deactive date","Status","Office phone","Mobile phone","Email"),
$table_field_name_arr		= array(array("Last Name","First Name","Office Phone","Mobile Phone","Email","Role","Title","Status","Username","Password"),
									array("Role Name","Nickname","Description"),
									array("System Name","Nickname","Primary Office","Availability","Integrity","Confidentiality","Tier","Type","Description","Criticality Justification","Sensitivity Justification"),
									array("Product Name","Vendor","Version","Description"),
									array("System group Name","System Group Nickname"),
									array("Function Name","Function Screen","Function Action","Open/Enabled","Description"),
									array("Source Name","Nickname","Description"),
								);
/************************************************************************************/
// fields if display or not
/*
0: no display in list and query
1: display in list and query
*/
									// array(1,0,1,0,1,0,1,0,0,0,0,0,1,1,1),
$table_field_display_arr	= array(array(1,1,1,1,1,1,0,0,1,0),
									array(1,1,0),
									array(1,1,1,1,1,1,1,1,0,0,0),
									array(1,1,1,0),
									array(1,1),
									array(1,1,1,1,0),
									array(1,1,1),
								);
/************************************************************************************/
/* stat for table
0: no stat
1: do stat
recommendation: unique field, key field and irregular field not to do stat
*/
								// array(0,0,1,1,0,0,0,0,0,0,0,1,0,0,0),
$table_field_stat_arr	= array(array(0,0,0,0,0,1,1,1,0,0),
								array(0,0,0),
								array(0,0,0,1,1,1,1,1,0,0,0),
								array(0,1,1,0),
								array(1,0),
								array(0,1,0,1,0),
								array(0,0,0),
								);
/************************************************************************************/
/* fields type handle by script
char: all string
text: long text
 int: Integer, length less than 20
date: Date, length is 10, sample: 2004-06-26
time: Time, length is 8, sample: 13:10:04
datetime: Datetime, length is 19, sample: 2004-7-29 13:14:19
*/
								// array("char","password","int","char","char","char","char","date","date","date","date","int","char","char","char"),
$table_field_type_arr	= array(array("char","char","char","char","email","int","char","int","char","password"),
								array("char","char","text"),
								array("char","char","int","int","int","int","int","char","text","text","text"),
								array("char","char","char","text"),
								array("char","char"),
								array("char","char","char","char","text"),
								array("char","char","text"),
								);
/************************************************************************************/
/* index type
0: all
1: not null
2: no repeat
*/
								// array(1,1,1,0,1,0,1,0,0,0,0,1,1,0,1),
$table_field_key_arr	= array(array(1,1,1,0,1,1,0,1,1,1),
								array(2,1,0),
								array(1,1,1,1,1,1,1,1,0,0,0),
								array(1,0,0,0),
								array(1,1),
								array(1,1,1,1,0),
								array(2,1,0),
								);
/************************************************************************************/
// define the field length
								// array(32,32,10,32,32,1,32,10,10,10,10,1,13,13,64),
$table_field_len_arr	= array(array(32,32,13,13,64,10,32,1,32,32),
								array(64,64,65000),
								array(128,8,10,10,10,10,10,22,65000,65000,65000),
								array(64,64,32,65000),
								array(64,64),
								array(64,64,64,1,65000),
								array(64,16,65000),
								);
/************************************************************************************/
/* tables relationship,
flag:
	0.no relative
	1.relationship, so it's refered by last three items. sample: array(1,"database.table","field","desc")
	2.relate by array. sample: array(2,key array,desc array)
*/
								// array(array(0),array(0),array(1,"ROLES","role_id","role_name"),array(0),array(0),array(0),array(0) ,array(0),array(0),array(0),array(0),array(2,array("1"=>"Active","0"=>"Suspend")) ,array(0),array(0),array(0)),
$table_relation_arr		= array(array(array(0),array(0),array(0),array(0),array(0),array(1,"ROLES","role_id","role_name"),array(0),array(2,array("1"=>"Active","0"=>"Suspend")),array(0),array(0)),
								array(array(0),array(0),array(0)),
								// note that the following does a direct map of primary office to FSA - will need to change this when new systems are incorporated
								array(array(0),array(0),array(2,array("0"=>"FSA")),array(2,array("HIGH"=>"high","MODERATE"=>"moderate","LOW"=>"low")),array(2,array("HIGH"=>"high","MODERATE"=>"moderate","LOW"=>"low")),array(2,array("HIGH"=>"high","MODERATE"=>"moderate","LOW"=>"low")),array(2,array("0"=>0,"1"=>1,"2"=>2,"3"=>3,"4"=>4)),array(0),array(0),array(0),array(0)),
								array(array(0),array(0),array(0),array(0)),
								array(array(0),array(0)),
								//array(array(0),array(0),array(0),array(2,array("1"=>"Open","0"=>"Closed")),array(0)),
								array(array(0),array(0),array(0),array(2,array("1"=>"Enable","0"=>"Disable")),array(0)),
								array(array(0),array(0),array(0)),
								);
/************************************************************************************/
/*
note for each fields in add entry form
*/
								// array("","","User must be to act as a Role","","","","","","","","","","","",""),
$table_note_arr			= array(array("","","","","","","","","",""),
								array("","",""),
								array("","","","","","","","","","",""),
								array("","","",""),
								array("",""),
								array("","","","",""),
								array("","",""),
								);
/************************************************************************************/
/************************************************************************************/
/************************************************************************************/


?>
