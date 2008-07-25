<?php
/**
 * postResults.php
 *http://www.reyosoft.com/SWS-v3_1/test/core/TestRunner.html?test=..%2Ftest%2FTestSuite.html&auto=on&resultsUrl=..%2FpostResults.php
 * @package Test
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * $Id$
 */

define('MAIL_TO', 'alixl@reyosoft.com;jimc@reyosoft.com');
define('PROJ_NAME', 'OVMS');
define('MAIL_FROM', 'NO_REPLY@Selenium.test');
define('REPORT_DIR', './log');

$title = strtoupper($_POST['result']).'_'.PROJ_NAME.'_TEST_REPORT_'.date('Y-m-d').'_'.uniqid();
$s = '<table>';
foreach($_POST as $k=>$v){
	$s .= '<tr><td>'.$k.'</td><td>'.$v.'</td></tr>';
}
$s .= '</table>';

	if(!is_dir(REPORT_DIR)) mkdir(REPORT_DIR);
	$r = file_put_contents(REPORT_DIR.'/'.$title.'.html', $s);
	if($r>0){
	    echo "Log to file OK!";
	}
	else {
	    echo "Log to file fail!";
	}

if (ini_get('sendmail_path'))	{
	$r = sendmail(PROJ_NAME.'_TEST', MAIL_FROM, 'Admin', MAIL_TO, $title, 'TEST REPORT', $s, '');
	if($r===true){
	    echo "Mail report OK!";
	}
	else {
	    echo "Mail report fail!";
	}
}

function sendmail ($from_name, $from_email, $to_name, $to_email, $subject, $text_message="", $html_message, $attachment="")
{
    $message="";
    $from = "$from_name <$from_email>";
    $to   = "$to_name <$to_email>";
    $main_boundary = "----=_NextPart_".md5(rand());
    $text_boundary = "----=_NextPart_".md5(rand());
    $html_boundary = "----=_NextPart_".md5(rand());
    $headers  = "From: $from\n";
    $headers .= "Reply-To: $from\n";
    $headers .= "X-Mailer: Sendmail \n";
    $headers .= "MIME-Version: 1.0\n";
    $headers .= "Content-Type: multipart/mixed;\n\tboundary=\"$main_boundary\"\n";
    $message .= "\n--$main_boundary\n";
    $message .= "Content-Type: multipart/alternative;\n\tboundary=\"$text_boundary\"\n";
    $message .= "\n--$text_boundary\n";
    $message .= "Content-Type: text/plain; charset=\"ISO-8859-1\"\n";
    $message .= "Content-Transfer-Encoding: 7bit\n\n";
    $message .= ($text_message!="")?"$text_message":"Text portion of HTML Email";
    $message .= "\n--$text_boundary\n";
    $message .= "Content-Type: multipart/related;\n\tboundary=\"$html_boundary\"\n";
    $message .= "\n--$html_boundary\n";
    $message .= "Content-Type: text/html; charset=\"ISO-8859-1\"\n";
    $message .= "Content-Transfer-Encoding: quoted-printable\n\n";
    $message .= str_replace ("=", "=3D", $html_message)."\n";
    if (isset ($attachment) && $attachment != "" && count ($attachment) >= 1)
    {
        for ($i=0; $i<count ($attachment); $i++)
        {
            $attfile = $attachment[$i];
            $file_name = basename ($attfile);
            $fp = fopen ($attfile, "r");
            $fcontent = "";
            while (!feof ($fp))
            {
                $fcontent .= fgets ($fp, 1024);
            }
            $fcontent = chunk_split (base64_encode($fcontent));
            @fclose ($fp);
            $message .= "\n--$html_boundary\n";
            $message .= "Content-Type: application/octetstream\n";
            $message .= "Content-Transfer-Encoding: base64\n";
            $message .= "Content-Disposition: inline; filename=\"$file_name\"\n";
            $message .= "Content-ID: <$file_name>\n\n";
            $message .= $fcontent;
        }
    }
    $message .= "\n--$html_boundary--\n";
    $message .= "\n--$text_boundary--\n";
    $message .= "\n--$main_boundary--\n";
    return @mail ($to, $subject, $message, $headers);
}
?>
