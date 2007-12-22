<?PHP
/*
** Notice text used across reports.
** Feb 17, 2006 Chuck Dolan
*/
$ARGH="argh!";

$REPORT_FOOTER_WARNING = "WARNING: This report is for internal, official use only.  This report contains sensitive computer security related information. Public disclosure of this information would risk circumvention of the law. Recipients of this report must not, under any circumstances, show or release its contents for purposes other than official action. This report must be safeguarded to prevent improper disclosure. Staff reviewing this document must hold a minimum of Public Trust Level 5C clearance.";


/*
** Make the warning string available to functions that haven't directly
** included this module.
*/
function footer_warning() {
  GLOBAL $REPORT_FOOTER_WARNING;
  return $REPORT_FOOTER_WARNING;
  }

?>
