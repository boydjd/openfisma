<?PHP
/*
** upload_utils
** Helper functions for file upload.
** Chuck Dolan Feb 21, 2006
**
** File uploads are driven by forms such as this:
** 
<form action="test_upload.php" enctype="multipart/form-data" method="post">
Choose file: <input type="file" name="form_file_name" value=""/><br/>
<input type="submit" name="submit" value="upload"/>
</form>
** PHP accesses files via the name attribute of the file input element (in this ** case 'form_file_name').
** These calls take the form file name as the identifying argument and wrap 
** PHP's raw upload file calls.
*/


/*
** Check if an upload has taken place for a given file name.
** 
** Input:
**  form_file_name - the name of the file element in the calling form.
**
** Return:
**  status - boolean TRUE if upload has just occurred for this file, FALSE
**           otherwise
*/
function upload_occurred($form_file_name) {
  $status = isset($_FILES[$form_file_name]);
  return ($status);
  }

/*
** Get the status code for the named file upload.
**
** Input:
**  form_file_name - the name of the file element in the calling form.
**
** Return:
**  status
**   UPLOAD_ERR_OK - file ok
**   UPLOAD_ERR_INI_SIZE - file exceeds config upload_max_filesize
**   UPLOAD_ERR_FORM_SIZE - file exceeds form MAX_FILE_SIZE
**   UPLOAD_ERR_PARTIAL - file was not completely uploaded
**   UPLOAD_ERR_NO_FILE - no file was set in the form
*/

function get_upload_status($form_file_name) {
  $status = $_FILES[$form_file_name]['error'];
  return ($status);
  }

/*
** Get a descriptive message if file upload has error.
**
** Input:
**  form_file_name - the name of the file element in the calling form.
**
** Return:
**  err_msg - descriptive error string
*/
function get_upload_error_message($form_file_name) {
  $status = $_FILES[$form_file_name]['error'];

  $err_message = "Unknown error code: $status";

  switch ($status) {
    case (UPLOAD_ERR_OK):
      $err_message = "No error";
      break;
    case (UPLOAD_ERR_INI_SIZE):
      $size_limit = ini_get('upload_max_filesize');
      $err_message = "File size exceeds server limit of $size_limit";
      break;
    case (UPLOAD_ERR_FORM_SIZE):
      $err_message = "File size exceeds page form limit";
      break;
    case (UPLOAD_ERR_PARTIAL):
      $err_message = "Incomplete upload";
      break;
    case (UPLOAD_ERR_NO_FILE):
      $err_message = "No filename specified";
      break;
    default:
      // leave initial message as-is
      break; 
    }

  return ($err_message);
  }

/*
** Get original name of file specified in the web form.
**
** Input:
**  form_file_name - the name of the file element in the calling form.
**
** Return:
**  filename - original file as named on client machine
*/
function get_upload_original_filename($form_file_name) {
  $filename = $_FILES[$form_file_name]['name'];
  return ($filename);
  }

/*
** Get upload file name.
**
** Input:
**  form_file_name - the name of the file element in the calling form.
**
** Return:
**  size - uploaded file size
**  
*/
function get_upload_file_size($form_file_name) {
  $size = $_FILES[$form_file_name]['size'];
  return ($size);
  }

/*
** Get local temporary file name.
** Files are given local temp names during process of upload.
**
** Input:
**  form_file_name - the name of the file element in the calling form.
**
** Return:
**  $filename - local temp file name
**  
*/
function get_upload_temp_filename($form_file_name) {
  $filename = $_FILES[$form_file_name]['tmp_name'];
  return ($filename);
  }

/*
** Get type of file.
** e.g.: text/plain
**
** Input:
**  form_file_name - the name of the file element in the calling form.
**
** Return:
**  type - file type descriptive string
*/
function get_upload_file_type($form_file_name) {
  $type = $_FILES[$form_file_name]['type'];
  return ($type);
  }

/*
** Move temporary uploaded file to another location.
** Errors may occur if there are filesystem issues or if a move
** is attempted without first checking for upload success. 
**
** Input:
**  form_file_name - the name of the file element in the calling form.
**
** Return:
**  status - boolean TRUE if successful, FALSE otherwise
*/
function move_upload_file($form_file_name, $destination_file) {
  $temp_file = $_FILES[$form_file_name]['tmp_name'];
  /*
  ** move_uploaded_file makes sure that the requested file
  ** was actually uploaded and not some trick to get a local server file.
  */
  $status = move_uploaded_file($temp_file, $destination_file);
  return ($status);
  }
?>
