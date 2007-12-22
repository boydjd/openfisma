<?PHP

// 
// INCLUDES
// 
require_once('Database.class.php');
require_once('BasicList.class.php');


//
// CLASS DEFINITION
// 

class AssetList extends BasicList {

  // -----------------------------------------------------------------------
  // 
  // CLASS METHODS
  // 
  // -----------------------------------------------------------------------
  
  public function __construct($db = NULL) { 

	// call the parent constructor with the db connection and table name
	parent::__construct($db, 'ASSETS'); 

  } // __construct()
  

  public function __destruct() {

	// call the parent destructor
	parent::__destruct();

  } // __destruct()
  
  public function __ToString() {} // __ToString()
 	

  // -----------------------------------------------------------------------
  // 
  // PARAMETERS
  // 
  // -----------------------------------------------------------------------
  
  public function getAssetId($isKey = FALSE)                                        { array_push($this->params, 'asset_id');                                          if ($isKey) { $this->key = 'asset_id'; } }
  public function getProdId($isKey = FALSE)                                         { array_push($this->params, 'prod_id');                                           if ($isKey) { $this->key = 'prod_id'; } }
  public function getAssetName($isKey = FALSE)                                      { array_push($this->params, 'asset_name');                                        if ($isKey) { $this->key = 'asset_name'; } }
  public function getAssetDateCreated($isKey = FALSE)                               { array_push($this->params, 'asset_date_created');                                if ($isKey) { $this->key = 'asset_date_created'; } }
  public function getAssetSource($isKey = FALSE)                                    { array_push($this->params, 'asset_source');                                      if ($isKey) { $this->key = 'asset_source'; } }  

  // -----------------------------------------------------------------------
  // 
  // FILTERS
  // 
  // -----------------------------------------------------------------------
  
  public function filterAssetId($value = NULL, $bool = TRUE)                                        { $this->filters['asset_id']                                          = array($value, $bool); }
  public function filterProdId($value = NULL, $bool = TRUE)                                         { $this->filters['prod_id']                                           = array($value, $bool); }
  public function filterAssetName($value = NULL, $bool = TRUE)                                      { $this->filters['asset_name']                                        = array($value, $bool); }
  public function filterAssetDateCreated($value = NULL, $bool = TRUE)                               { $this->filters['asset_date_created']                                = array($value, $bool); }
  public function filterAssetSource($value = NULL, $bool = TRUE)                                    { $this->filters['asset_source']                                      = array($value, $bool); }  

} // class AssetList

?>