<?PHP

// 
// INCLUDES
// 
require_once('Database.class.php');


//
// CLASS DEFINITION
// 

class Asset {

	// -----------------------------------------------------------------------
	//
	// VARIABLES
	//
	// -----------------------------------------------------------------------


    private $db;

    private $asset_id;
    private $prod_id;
    private $asset_name;
    private $asset_date_created;
    private $asset_source;


	// -----------------------------------------------------------------------
	// 
	// CLASS METHODS
	// 
	// -----------------------------------------------------------------------

	public function __construct($db, $asset_id = NULL) {

		// utilize an existing database connection
		$this->db = $db;

		// get asset information or create a new one if none specified
		if ($asset_id) {
		  $this->getAsset($asset_id); 
		}

	} // __construct()
	

	public function __destruct() {

		// clear out the asset_id to prevent any updates
		$this->asset_id = 0;

	} // __destruct()


 	public function __ToString() {
 		
 		// return a string of information
 		return	$this->db->__ToString().
 			'<pre>'.
			'<br>ASSETS'.
			'<br>------'.

            '<br>asset_id                                          : '.$this->asset_id.
            '<br>prod_id                                           : '.$this->prod_id.
            '<br>asset_name                                        : '.$this->asset_name.
            '<br>asset_date_created                                : '.$this->asset_date_created.
            '<br>asset_source                                      : '.$this->asset_source.
			'<br></pre>';
 		
 	} // __ToString()
 	

	// -----------------------------------------------------------------------
	// 
	// CLASS MANIPULATION METHODS
	// 
	// -----------------------------------------------------------------------
	
	public function assetExists($asset_id = NULL) {
		
		// make sure we have a positive, non-zero asset_id
		if ($asset_id) {
		
			// build our query
			$query = "SELECT `asset_id` FROM " . TN_ASSETS . " WHERE (`asset_id` = '$asset_id')";
			
			// execute the query
			$this->db->query($query);
			
			// check for results
			if ( $this->db->queryOK() && $this->db->num_rows() ) {
			     return 1; 
			} 
			else {
			     return 0; 
			}
		}
		
		// otherwise don't even bother checking it
		else { 
		     return 0; 
		}
		
	} // assetExists()
	

	public function getAsset($asset_id = NULL) {
		
		// make sure we have a positive, non-zero asset_id
		if ($asset_id && $this->assetExists($asset_id)) {
		
			// designate our retrieval query
			$query = "SELECT * FROM " . TN_ASSETS . "  WHERE (`asset_id` = '$asset_id')";
		
			// execute the query
			$this->db->query($query);
		
			// if we get a hit, store the information
			if ($this->db->num_rows() > 0) {
			
				// retrieve the results query
				$results = $this->db->fetch_assoc();
			
				// store the results locally

                $this->asset_id                                           = $results['asset_id'];
                $this->prod_id                                            = $results['prod_id'];
                $this->asset_name                                         = $results['asset_name'];
                $this->asset_date_created                                 = $results['asset_date_created'];
                $this->asset_source                                       = $results['asset_source'];
			
			} // this->db->fetch_assoc()
			
			// system not retrieved, clear out any potential values
			else {
			     $this->clearAsset(); 
			}
		} // if $asset_id

	} // getAsset()


		
	public function saveAsset(){
	
	    if ($this->asset_id && $this->assetExists($this->asset_id)){
    	    $query = "UPDATE " . TN_ASSETS . " SET ";    
            	    $query .= " `prod_id`                                            = '$this->prod_id', ";
            	    $query .= " `asset_name`                                         = '$this->asset_name', ";
            	    $query .= " `asset_date_created`                                 = '$this->asset_date_created', ";
            	    $query .= " `asset_source`                                       = '$this->asset_source' ";	    
                    $query .= " WHERE `asset_id`                                     = '$this->asset_id' ";
	    }
	    else {
	       $query = "INSERT INTO " . TN_ASSETS . " (
                            `prod_id`, 
                            `asset_name`, 
                            `asset_date_created`, 
                            `asset_source`
                            ) VALUES (
                            '$this->prod_id', 
                            '$this->asset_name', 
                            '$current_time_string', 
                            '$this->asset_source'
                            )";
	    }
	    
    	// execute our query
    	$this->db->query($query);
    
    	if ($this->db->queryOK()) { 
    	   if (!$asset_id || !$this->assetExists($asset_id)){
    	       $this->asset_id = $this->db->insert_id();
    	   }
    	   return 1; 
    	} 
    	else {
    	   return 0; 
    	}
	} //saveAsset()
	
	public function clearAsset() {
		
		// clear out (non-db) user values

        unset($this->asset_id);
        unset($this->prod_id);
        unset($this->asset_name);
        unset($this->asset_date_created);
        unset($this->asset_source);
	} // clearAsset()

	public function createAsset() {
		
		// designate our insertion query
		$query = "INSERT INTO " . TN_ASSETS . " (asset_id, asset_date_created) VALUES (NULL, '$current_time_string')";
		
		// execute the query
		$this->db->query($query);
		
		// grab the new asset_id
		$this->asset_id = $this->db->insert_id();
		
		// update the internal variables
		$this->getAsset($this->asset_id);
		
	} // createAsset()

	public function deleteAsset() {

		// 
		// REMOVES ASSETS FROM " . TN_DATABASE!
		// 

		// ensure that we have an open database connection
		if ($this->db) {

			// define our query
			$query = "DELETE FROM " . TN_ASSETS . "  WHERE (`asset_id` = '$this->asset_id')";

			// execute our query
			$this->db->query($query);

			// clear out the current object
			$this->clearAsset();
		
		} // $this->db

	} // deleteAsset()
	
	

	// -----------------------------------------------------------------------
	// 
	// VARIABLE ACCESS METHODS
	// 
	// -----------------------------------------------------------------------


    public function getAssetId()                                           { return $this->asset_id; }
    public function getProdId()                                            { return $this->prod_id; }
    public function getAssetName()                                         { return $this->asset_name; }
    public function getAssetDateCreated()                                  { return $this->asset_date_created; }
    public function getAssetSource()                                       { return $this->asset_source; }

	public function getValidAssetIds($offset = 0, $limit = NULL) {
		
		// array to store user ids
		$id_array = array();

		// create our query
		$query = "SELECT asset_id FROM " . TN_ASSETS;
		
		// add in our offset and limit if a limit is provided
		if ($limit) { $query .= " LIMIT $offset, $limit";  }		
		
		// execute the query
		$this->db->query($query);
	
		// evaluate the results
		if ($this->db->queryOK()) {
			
			// push the values onto the array
			while ($id = $this->db->fetch_array()) { array_push($id_array, $id[0]); }
			
		}
		
		// return the array of user_ids
		return $id_array;
		
	} // getValidUserIds

	public function getValidAssetNames($offset = 0, $limit = NULL) {
		
		// array to store asset_names
		$name_array = array();

		// create our query
		$query = "SELECT `asset_name` FROM " . TN_ASSETS;

		// add in our offset and limit if a limit is provided
		if ($limit) { $query .= " LIMIT $offset, $limit";  }

		// execute the query
		$this->db->query($query);
	
		// evaluate the results
		if ($this->db->queryOK()) {
			
			// push the values onto the array
			while ($name = $this->db->fetch_array()) { array_push($name_array, $name[0]); }
			
		}
		
		// return the array of asset_names
		return $name_array;
		
	} // getValidAssetNames
	
	// -----------------------------------------------------------------------
	// 
	// VARIABLE MODIFY METHODS
	// 
	// -----------------------------------------------------------------------


    public function setProdId($prod_id  =  NULL){ 
		// error check input (by schema)
		if (strlen($prod_id) <= 10){
            $this->prod_id = $prod_id;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setProdId()
    
    
    public function setAssetName($asset_name  =  NULL){ 
		// error check input (by schema)
		if (strlen($asset_name) <= 32){
            $this->asset_name = $asset_name;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setAssetName()
    
    
    public function setAssetDateCreated($asset_date_created  =  NULL){ 
		// error check input (by schema)
		if (strlen($asset_date_created) >= 0){
            $this->asset_date_created = $asset_date_created;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setAssetDateCreated()
    
    
    public function setAssetSource($asset_source  =  NULL){ 
		// error check input (by schema)
		if (in_array($asset_source, array('MANUAL','SCAN','INVENTORY')) ){
            $this->asset_source = $asset_source;
            return true;
		} // input error check
		else {
		    return false;
		}
	} // setAssetSource()
    
    

} // class Asset
?>
