<!-- simple upload form -->
<form action="test_upload.php" enctype="multipart/form-data" method="post">
Choose file: <input type="file" name="form_file_name" value=""/><br/>
<input type="submit" name="submit" value="upload"/>
</form>


<?PHP
require_once("upload_utils.php");

// match the input 'name' field from the form
$FORM_FILE_ID = 'form_file_name';

if(upload_occurred($FORM_FILE_ID)) {
  
  // check the status of the upload
  if(get_upload_status($FORM_FILE_ID) != UPLOAD_ERR_OK) {
    $error_msg = get_upload_error_message($FORM_FILE_ID);
    die ($error_msg);
    }

  echo "file size: " . get_upload_file_size($FORM_FILE_ID) . "<br/>";
  echo "file type: " . get_upload_file_type($FORM_FILE_ID) . "<br/>";

  $orig_name = get_upload_original_filename($FORM_FILE_ID);
  echo "client file name: $orig_name<br/>";

  $nameparts = pathinfo($orig_name);
  $orig_base = $nameparts['basename'];
  $orig_ext  = $nameparts['extension'];

  $DEST_PATH = '/tmp/uploads';
  $dest_file = "$DEST_PATH/$orig_base";

  if (!move_upload_file($FORM_FILE_ID, $dest_file)) {
    die ("unable to access or move temp file");
    }
  echo "file has been moved to: $dest_file<br/>";

  }


?>

