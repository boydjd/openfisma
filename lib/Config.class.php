<?PHP 

class Config {

  // -----------------------------------------------------------------------
  // 
  // VARIABLES
  // 
  // -----------------------------------------------------------------------

  // configuration file
  private $CONF_FILE;

  // application variables
  private $APP_URL;
  private $APP_ROOT;
  private $APP_LIB;

  // smarty variables
  private $SMARTY_LIB_DIR;
  private $SMARTY_TEMPLATE_DIR;
  private $SMARTY_CACHE_DIR;
  private $SMARTY_CONFIGS_DIR;
  private $SMARTY_COMPILE_DIR;
  private $SMARTY_DEBUGGING;

  // database variables
  private $DB_TYPE;
  private $DB_HOST;
  private $DB_PORT;
  private $DB_NAME;
  private $DB_USER;
  private $DB_PASS;

  // cipher variables
  private $CIPHER_HASH;
  private $CIPHER_SYMMETRIC;
  private $CIPHER_MODE;

  // session variables
  private $SESSION_TIMEOUT;
  private $SESSION_PATH;
  private $SESSION_DOMAIN;
  private $SESSION_EXPIRATION;
  private $SESSION_SECURE_ONLY;

  // list page variables
  private $PAGE_SIZE;
  private $PAGE_INTERVALS;

  // output variables
  private $BUFFER_OUTPUT;


  // -----------------------------------------------------------------------
  // 
  // CLASS METHODS
  // 
  // -----------------------------------------------------------------------

  public function __construct($OVMS_ROOT = NULL) {

	// check that OVMS_ROOT is given
	if ($OVMS_ROOT) {

	  // capture the configuration file value
	  $this->CONF_FILE = $OVMS_ROOT.'/conf/ovms.conf';

	  // read in the configuration values
	  $this->read_config();

	} // if OVMS_ROOT

	// die on error
	else { die('[ERROR] Config.class.php: OVMS_ROOT not specified!<br>\n'); }

  } // __construct()


  public function __destruct() {

  } // __destruct()


  public function __ToString() {

	return
	  "\n<pre>".
	  "\nConfiguration".
	  "\n-------------".
	  "\nCONF_FILE           : ".$this->CONF_FILE.
	  "\n".
	  "\nAPP_URL             : ".$this->APP_URL().
	  "\nAPP_ROOT            : ".$this->APP_ROOT().
	  "\nAPP_LIB             : ".$this->APP_LIB().
	  "\n".
	  "\nSMARTY_LIB_DIR      : ".$this->SMARTY_LIB_DIR().
	  "\nSMARTY_TEMPLATE_DIR : ".$this->SMARTY_TEMPLATE_DIR().
	  "\nSMARTY_CACHE_DIR    : ".$this->SMARTY_CACHE_DIR().
	  "\nSMARTY_CONFIGS_DIR  : ".$this->SMARTY_CONFIGS_DIR().
	  "\nSMARTY_COMPILE_DIR  : ".$this->SMARTY_COMPILE_DIR().
	  "\nSMARTY_DEBUGGING    : ".$this->SMARTY_DEBUGGING().
	  "\n".
	  "\nDB_TYPE             : ".$this->DB_TYPE().
	  "\nDB_HOST             : ".$this->DB_HOST().
	  "\nDB_PORT             : ".$this->DB_PORT().
	  "\nDB_NAME             : ".$this->DB_NAME().
	  "\nDB_USER             : ".$this->DB_USER().
	  "\nDB_PASS             : ".$this->DB_PASS().
	  "\n".
	  "\nCIPHER_HASH         : ".$this->CIPHER_HASH().
	  "\nCIPHER_SYMEMTRIC    : ".$this->CIPHER_SYMMETRIC().
	  "\nCIPHER_MODE         : ".$this->CIPHER_MODE().
	  "\n".
	  "\nSESSION_TIMEOUT     : ".$this->SESSION_TIMEOUT().
	  "\nSESSION_PATH        : ".$this->SESSION_PATH().
	  "\nSESSION_DOMAIN      : ".$this->SESSION_DOMAIN().
	  "\nSESSION_EXPIRATION  : ".$this->SESSION_EXPIRATION().
	  "\nSESSION_SECURE_ONLY : ".$this->SESSION_SECURE_ONLY().
	  "\n".
	  "\nPAGE_SIZE           : ".$this->PAGE_SIZE().
	  "\nPAGE_INTERVALS      : ".$this->PAGE_INTERVALS().
	  "\n".
	  "\nBUFFER_OUTPUT       : ".$this->BUFFER_OUTPUT().
	  "\n".
	  "\n</pre>";


  } // __ToString()


  // -----------------------------------------------------------------------
  // 
  // INSTANCE METHODS
  // 
  // -----------------------------------------------------------------------

  private function read_config() {

	// open the file
	$FILE = fopen($this->CONF_FILE, 'r') 
	  or die("[ERROR]: Config.class.php: Cannot open $this->CONF_FILE for reading!<br>\n");

	// loop through the lines
	while ($line = fgets($FILE)) {

	  // strip the newline and spaces around the equals
	  $line = rtrim($line);
	  $line = preg_replace('/\s+=\s+/', '=', $line);

	  // skip comment lines and blank lines
	  if (!preg_match('/^#|^$/', $line)) {

		// split the line on the equals
		$split = preg_split('/=/', $line);

		// match our app settings
		if (preg_match('/APP_URL/',  $split[0])) { $this->APP_URL  = $split[1]; }
		if (preg_match('/APP_ROOT/', $split[0])) { $this->APP_ROOT = $split[1]; }
		if (preg_match('/APP_LIB/',  $split[0])) { $this->APP_LIB  = $split[1]; }

		// match our smarty settings
		if (preg_match('/SMARTY_LIB_DIR/',      $split[0])) { $this->SMARTY_LIB_DIR      = $split[1]; }
		if (preg_match('/SMARTY_TEMPLATE_DIR/', $split[0])) { $this->SMARTY_TEMPLATE_DIR = $split[1]; }
		if (preg_match('/SMARTY_CACHE_DIR/',    $split[0])) { $this->SMARTY_CACHE_DIR    = $split[1]; }
		if (preg_match('/SMARTY_CONFIGS_DIR/',  $split[0])) { $this->SMARTY_CONFIGS_DIR  = $split[1]; }
		if (preg_match('/SMARTY_COMPILE_DIR/',  $split[0])) { $this->SMARTY_COMPILE_DIR  = $split[1]; }
		if (preg_match('/SMARTY_DEBUGGING/',    $split[0])) { $this->SMARTY_DEBUGGING    = $split[1]; }

		// match our database settings
		if (preg_match('/DB_TYPE/', $split[0])) { $this->DB_TYPE = $split[1]; }
		if (preg_match('/DB_HOST/', $split[0])) { $this->DB_HOST = $split[1]; }
		if (preg_match('/DB_PORT/', $split[0])) { $this->DB_PORT = $split[1]; }
		if (preg_match('/DB_NAME/', $split[0])) { $this->DB_NAME = $split[1]; }
		if (preg_match('/DB_USER/', $split[0])) { $this->DB_USER = $split[1]; }
		if (preg_match('/DB_PASS/', $split[0])) { $this->DB_PASS = $split[1]; }

		// match our encryption settings
		if (preg_match('/CIPHER_HASH/',      $split[0])) { $this->CIPHER_HASH      = $split[1]; }
		if (preg_match('/CIPHER_SYMMETRIC/', $split[0])) { $this->CIPHER_SYMMETRIC = $split[1]; }
		if (preg_match('/CIPHER_MODE/',      $split[0])) { $this->CIPHER_MODE      = $split[1]; }

		// match our session settings
		if (preg_match('/SESSION_TIMEOUT/',     $split[0])) { $this->SESSION_TIMEOUT     = $split[1]; }
		if (preg_match('/SESSION_PATH/',        $split[0])) { $this->SESSION_PATH        = $split[1]; }
		if (preg_match('/SESSION_DOMAIN/',      $split[0])) { $this->SESSION_DOMAIN      = $split[1]; }
		if (preg_match('/SESSION_EXPIRATION/',  $split[0])) { $this->SESSION_EXPIRATION  = $split[1]; }
		if (preg_match('/SESSION_SECURE_ONLY/', $split[0])) { $this->SESSION_SECURE_ONLY = $split[1]; }

        // match our list page size
        if (preg_match('/PAGE_SIZE/',      $split[0])) { $this->PAGE_SIZE      = $split[1]; }
        if (preg_match('/PAGE_INTERVALS/', $split[0])) { $this->PAGE_INTERVALS = $split[1]; }

		// match our output variables
        if (preg_match('/BUFFER_OUTPUT/', $split[0])) { $this->BUFFER_OUTPUT = $split[1]; }

	  } // if not comment or blank line
	  
	} // while $line

	// close the file
	fclose($FILE);

  } // read_config()


  // -----------------------------------------------------------------------
  // 
  // ACCESSOR METHODS
  // 
  // -----------------------------------------------------------------------

  // application accessors
  public function APP_URL()             { return $this->APP_URL;                   }
  public function APP_ROOT()            { return $this->APP_ROOT;                  }
  public function APP_LIB()             { return $this->APP_ROOT().$this->APP_LIB; }

  // smarty template accessors
  public function SMARTY_LIB_DIR()      { return $this->APP_LIB().$this->SMARTY_LIB_DIR;      }
  public function SMARTY_TEMPLATE_DIR() { return $this->APP_ROOT().$this->SMARTY_TEMPLATE_DIR; }
  public function SMARTY_CACHE_DIR()    { return $this->APP_ROOT().$this->SMARTY_CACHE_DIR;    }
  public function SMARTY_CONFIGS_DIR()  { return $this->APP_ROOT().$this->SMARTY_CONFIGS_DIR;  }
  public function SMARTY_COMPILE_DIR()  { return $this->APP_ROOT().$this->SMARTY_COMPILE_DIR;  }
  public function SMARTY_DEBUGGING()    { return $this->SMARTY_DEBUGGING;    }

  // database accessors
  public function DB_TYPE()             { return $this->DB_TYPE;             }
  public function DB_HOST()             { return $this->DB_HOST;             }
  public function DB_PORT()             { return $this->DB_PORT;             }
  public function DB_NAME()             { return $this->DB_NAME;             }
  public function DB_USER()             { return $this->DB_USER;             }
  public function DB_PASS()             { return $this->DB_PASS;             }

  // encryption accessors
  public function CIPHER_HASH()         { return $this->CIPHER_HASH;         }
  public function CIPHER_SYMMETRIC()    { return $this->CIPHER_SYMMETRIC;    }
  public function CIPHER_MODE()         { return $this->CIPHER_MODE;         }

  // session accessors
  public function SESSION_TIMEOUT()     { return $this->SESSION_TIMEOUT;     }
  public function SESSION_PATH()        { return $this->SESSION_PATH;        }
  public function SESSION_DOMAIN()      { return $this->SESSION_DOMAIN;      }
  public function SESSION_EXPIRATION()  { return $this->SESSION_EXPIRATION;  }
  public function SESSION_SECURE_ONLY() { return $this->SESSION_SECURE_ONLY; }
  
  // list accessors
  public function PAGE_SIZE()           { return $this->PAGE_SIZE;           }
  public function PAGE_INTERVALS()      { return $this->PAGE_INTERVALS;      }

  // display accessors
  public function BUFFER_OUTPUT()       { return $this->BUFFER_OUTPUT;       }

} // class Config


// -----------------------------------------------------------------------------
//
// MAIN
//
// -----------------------------------------------------------------------------

// debugging
$_ENV['OVMS_ROOT'] = '/opt/endeavor/';

// create our instance variable from the root directory
$_CONFIG = new Config($_ENV['OVMS_ROOT']);

?>
