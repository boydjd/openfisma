<?PHP
$report_lang= array (
array (
),
array (
	"FISMA Report to OMB: POA&M Status Report",
	"POA&M Status Information",
	"Agency Wide",
	"System",
	"Total",
	"Brief Explanation",
	"A. Total number of weaknesses identified at the start of the reporting period",
	"B. Number of weaknesses for which corrective action was completed on time(including testing) by the end of the reporting period",
	"C. Number of weaknesses for which corrective action is ongoing and is on track to complete as originally scheduled",
	"D. Number of weaknesses for which corrective action has been delayed including a brief explanation for the delay",
	"E. Number of weaknesses discovered following the last POA&M update and a brief Explanation of how they were identified (e.g., agency review, IG evaluation, etc.)",
	"Total number of weaknesses remaining at the end of the reporting period"	
),
array (
"Results",
"PO",
"System",
"Tier",
"Remediation#",
"Finding",
"Type",
"Status",
"Source",
"Server/Database",
"Location",
"Risk Level",
"Recommendation",
"Corrective Action",
"Estimated Completion Date"
),
array (
//because report 3 have many sub report,so we need more deep dimension 
//reports list
array(
"1" => "NIST Baseline Security Controls Report",
"2" => "FIPS 199 Categorization Breakdown",
"3" => "Products with Open Vulnerabilities",
"4" => "Software Discovered Through Vulnerability Assessments",
"5" => "Total # of Systems /w Open Vulnerabilities"
//,"6" => "XXX"
),
array(
	1=>array(
		"Management","Operational","Technical",
		"BLSR Category","Total Vulnerabilities",
		"Total"
	),
	2=>array(
		"FIPS 199 Category","Low","Moderate","High","Total Systems",
		"System Name","System Type","Mission Criticality","FIPS 199 Category",
		"Confidentiality","Integrity","Availability","Last Inventory Update"
	),
	3=>array(
	"Vendor","Product","Version","# of Open Vulnerabilities"
	),
	4=>array(
	"Vendor","Product","Version"
	),
	5=>array(
	"Total # Systems With Open Vulnerabilities",
	"Systems",
	"Open Vulnerabilities",
	"Total",
	"Total # of system with open vulnerability",
	"Total # of vulnerabilities",
	"# of Vulnerabilities",
	"Vulnerabilities per system"
	)
	//,6=>array(),
)
)
);

?>
