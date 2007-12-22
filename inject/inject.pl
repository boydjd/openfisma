#! /usr/bin/perl
# ---------------------------------------------------------------------------
# FILE     : inject.pl
# DESC     : handles the injection of data into the OVMS database
# INPUTS   : data source to be translated
# OUTPUTS  : data source translated to standard injection format
# DEPENDS  : inject_utils.pm
#            DBI
#
# AUTHOR   : Brian Gant
# DATE     : 12/29/30
#
# COMMENTS :
#
# This file should be used as template for all future plugin creation. The
# developer need only look for the TODO comments for instructions on how to
# successfully implement a new OVMS plugin.
#
# ---------------------------------------------------------------------------

# ---------------------------------------------------------------------------
# PRAGMAS
# ---------------------------------------------------------------------------
require strict;
require warnings;
require diagnostics;


# ---------------------------------------------------------------------------
# ENVIRONMENT
# ---------------------------------------------------------------------------

require $ENV{'OVMS_ROOT'}."/lib/inject_utils.pm";


# ---------------------------------------------------------------------------
# INJECTION UTILITIES
# ---------------------------------------------------------------------------

sub create_asset {
# ---------------------------------------------------------------------------
# Purpose : does the actual ASSETS and ASSET_ADDRESSES injection
# Params  : 0 - db handler
#           1 - ip address
#           2 - port
#           1 - hash of upload data
# Returns : 0 - asset_id
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "create_asset()";

    # -----------------------------------------------------------------------

    # grab the parameters
    my $db        = shift @_;
    my $ip        = shift @_;
    my $port      = shift @_;
    my %upload    = @_;

    # set up our other variables
    my $asset_id = 'NULL';

	# check to see if the asset_address already exists
	my $query = inject_utils::gen_select('ASSET_ADDRESSES', 1, 'asset_id',
					     'network_id',   $upload{'network_id'},
					     'address_ip',   $ip,
					     'address_port', $port
					     );

	# execute the query
	my ($success, @results) = inject_utils::db_exec($db, $query);

	# grab the asset_id from the address if our query was successful
	if ($success > 0) { 

		$asset_id = $results[0]; 
		inject_utils::log_write(4, 'asset_id = '.$asset_id);

	}
    
	# since there was no asset_address, we must first create an asset
	else {

		# Conditional hostname field added for inventory injection
		my $hostname = ($upload{'hostname'}) ? $upload{'hostname'} : "";
  	 	my $default_name = "$hostname:$ip:$port";

		# generate the asset injection query
   		$query = inject_utils::gen_insert("ASSETS", 
						 'asset_id',           $asset_id, 
						 'prod_id',            'NULL',
						 'asset_source',       'SCAN', 
						 'asset_date_created', inject_utils::timestamp(),
						 'asset_name',         $default_name
				 		 );

		# attempt to create our asset
		($success, @results) = inject_utils::db_exec($db, $query);

		# grab the asset_id if our query was successful
		if ($success > 0) { 

			# grab the asset_id
			$asset_id = $results[0];

			# create the ASSET_ADDRESSES query
			$query = inject_utils::gen_insert('ASSET_ADDRESSES',
							  'asset_id',             $asset_id,
							  'network_id',           $upload{'network_id'},
							  'address_date_created', inject_utils::timestamp(),
							  'address_ip',           $ip,
							  'address_port',         $port
							  );

			# execute the query
			($success, @results) = inject_utils::db_exec($db, $query);

			# log the results
			if ($success > 0) { inject_utils::log_write(3, "ASSET_ADDRESSES insert successful"); }
			else { inject_utils::log_write(2, "ASSET_ADDRESSES insert failed"); }

			# create the SYSTEM_ASSETS query
			$query = inject_utils::gen_insert('SYSTEM_ASSETS',
							  'asset_id', $asset_id,
							  'system_id', $upload{'system_id'},
							  'system_is_owner', '1'
							  );

			# execute the query
			($success, @results) = inject_utils::db_exec($db, $query);

			# log the results
			if ($success > 0) { inject_utils::log_write(3, "SYSTEM_ASSETS insert successful"); }
			else { inject_utils::log_write(2, "SYSTEM_ASSETS insert failed"); }

		} # asset creation

	} # no asset address returned

	# -----------------------------------------------------------------------
	# restore the sub name and return the results

	$inject_utils::this_sub = $last_sub;
	return ($asset_id);

}

sub create_blscr {
# ---------------------------------------------------------------------------
# Purpose : does the actual PRODUCTS injection
# Params  : 0 - db handler
#           1 - line of data to be parsed/injected
# Returns : 0 - product id
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "create_blscr()";

    # -----------------------------------------------------------------------

    # grab the parameters
    my $db      = shift @_;
    my $line    = shift @_;

    # set up our other variables
    my $blscr_number = 0;
    my $table        = "BLSCR";
    my %hash         = inject_utils::parse_blscr($line);
    my $query        = inject_utils::gen_select($table, 1, 'blscr_number', %hash);

    # perform the duplicate check
    my ($success, @results) = inject_utils::db_exec($db, $query);

    # we need to check all of the results if something was returned
    if ($success > 0) { 

	# grab the blscr_number and log it
	$blscr_number = $results[0];
	inject_utils::log_write(3, "existing blscr found, not inserting");
	inject_utils::log_write(4, "results returned : $blscr_number");

    }

    # perform the insert if we didn't find it
    else {

	inject_utils::log_write(3, "blscr_number not found");

	# create the insertion query
	$query = inject_utils::gen_insert($table, %hash);
	inject_utils::log_write(3, "generating $table insert query");
	inject_utils::log_write(4, "query = $query");

	# perform the insertion (no PK to be returned, so no need to check)
	($success, @results) = inject_utils::db_exec($db, $query);

	# grab the prod_id if we were successful
	if ($success > 0) {

	    # grab the prod_id and log it
	    $prod_id = $results[0];
	    inject_utils::log_write(3, "$table inject successful");
	    inject_utils::log_write(4, "blscr_number = $blscr_number");

	}

    }

    # -----------------------------------------------------------------------
    # restore the sub name and return the results
    $inject_utils::this_sub = $last_sub;
    return ($blscr_number);

}

sub create_finding {
# ---------------------------------------------------------------------------
# Purpose : does the actual FINDINGS injection
# Params  : 0 - db handler
#           1 - asset_id
#           2 - line of data to be parsed/injected
#           3 - hash of upload data
# Returns : 0 - asset_id
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "create_finding()";

    # -----------------------------------------------------------------------

    # grab the parameters
    my $db         = shift @_;
    my $asset_id   = shift @_;
    my $line       = shift @_;
    my %upload     = @_;

    my %finding    = inject_utils::parse_finding($line);
    my $table      = "FINDINGS";

    my $finding_id = 0;

    # create the asset insert query and execute it
    my $query = inject_utils::gen_insert($table,
					 'source_id',               $upload{'source_id'}, 
					 'asset_id',                $asset_id, 
					 'finding_status',          'open',
					 'finding_date_created',    inject_utils::timestamp(),
					 'finding_date_discovered', $finding{'finding_date_discovered'},
					 'finding_date_closed',     '0000-00-00',
					 'finding_data',            $finding{'finding_instance_data'},
					 );

    my ($success, @results) = inject_utils::db_exec($db, $query);

    # handle the results
    if ($success > 0) { 

	# grab the finding_id
	$finding_id = $results[0];

	# log it
	inject_utils::log_write(3, "$table insert successful");
	inject_utils::log_write(3, "finding_id = $finding_id");
	  
    }
    else { inject_utils::log_write(2, "$table insert failed"); }

    # -----------------------------------------------------------------------
    # restore the sub name and return the results
    $inject_utils::this_sub = $last_sub;
    return ($finding_id);

}

sub create_finding_vuln {
# ---------------------------------------------------------------------------
# Purpose : does the actual VULN_PRODUCT injection
# Params  : 0 - db handler
#           1 - finding_id
#           2 - vuln_type
#           3 - vuln_seq
# Returns : none
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "create_finding_vuln()";

    # -----------------------------------------------------------------------

    # grab the parameters
    my $db         = shift @_;
    my $finding_id = shift @_;
    my $vuln_type  = shift @_;
    my $vuln_seq   = shift @_;

    my $table = "FINDING_VULNS";

    # generate the VULN_PRODUCT select query to check for duplicates
    inject_utils::log_write(3, "preparing $table duplicate check query");
    my $query = inject_utils::gen_select($table, 0, 
					 'finding_id', $finding_id,
					 'vuln_type',  $vuln_type,
					 'vuln_seq',   $vuln_seq);

    # execute the query
    inject_utils::log_write(3, "executing $table duplicate check query");
    my ($success, @results) = inject_utils::db_exec($db, $query);

    # do the insert unless we find a dupe
    unless ($success > 0) {
		
	# generate the VULN_PRODUCT insert query
	inject_utils::log_write(3, "$table entry does not exist, preparing insert query");
	$query = inject_utils::gen_insert($table, 
					  'finding_id', $finding_id,
					  'vuln_type',  $vuln_type,
					  'vuln_seq',   $vuln_seq);

	# execute the query
	inject_utils::log_write(3, "executing $table insert query");
	($success, @results) = inject_utils::db_exec($db, $query);

	# log the resuls
	if ($success > 0) { inject_utils::log_write(3, "$table insert successful"); }
	else { inject_utils::log_write(1, "$table insert failed"); }

    }

    # log that we hit a dupe
    else { inject_utils::log_write(2, "finding vulnerability found for $finding_id and $vuln_type-$vuln_seq, skipping insert"); }

    # -----------------------------------------------------------------------
    # restore the sub name and return the results
    $inject_utils::this_sub = $last_sub;

}


sub create_product {
# ---------------------------------------------------------------------------
# Purpose : does the actual PRODUCTS injection
# Params  : 0 - db handler
#           1 - line of data to be parsed/injected
# Returns : 0 - product id
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "create_product()";

    # -----------------------------------------------------------------------

    # grab the parameters
    my $db      = shift @_;
    my $line    = shift @_;

    # set up our other variables
    my $prod_id = 0;
    my $table   = "PRODUCTS";
    my %hash    = inject_utils::parse_product($line);
    my $query   = inject_utils::gen_select($table, 1, "prod_id", %hash);

    # perform the duplicate check
    my ($success, @results) = inject_utils::db_exec($db, $query);

    # we need to check all of the results if something was returned
    if ($success > 0) { 

	# grab the prod_id and log it
	$prod_id = $results[0];
	inject_utils::log_write(3, "existing id found, not inserting");
	inject_utils::log_write(4, "results returned : $prod_id");

    }

    # perform the insert if we didn't find it
    else {

	inject_utils::log_write(3, "prod_id not found");

	# create the insertion query
	$query = inject_utils::gen_insert($table, %hash);
	inject_utils::log_write(3, "generating $table insert query");
	inject_utils::log_write(4, "query = $query");

	# perform the insertion (no PK to be returned, so no need to check)
	($success, @results) = inject_utils::db_exec($db, $query);

	# grab the prod_id if we were successful
	if ($success > 0) {

	    # grab the prod_id and log it
	    $prod_id = $results[0];
	    inject_utils::log_write(3, "$table inject successful");
	    inject_utils::log_write(4, "prod_id = $prod_id");

	}

    }

    # -----------------------------------------------------------------------
    # restore the sub name and return the results
    $inject_utils::this_sub = $last_sub;
    return ($prod_id);

}

sub create_vulnerability {
# ---------------------------------------------------------------------------
# Purpose : does the actual VULNERABILITIES injection
# Params  : 0 - db handler
#           1 - type of vulnerability
#           2 - line of data to be parsed/injected
# Returns : 0 - results of the operation
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "create_vulnerability()";

    # -----------------------------------------------------------------------

    # grab the parameters
    my $db        = shift @_;
    my $vuln_type = shift @_;
    my $vuln_seq  = 0;
    my $line      = shift @_;

    # set up our other variables
    my $found   = 0;
    my $table   = "VULNERABILITIES";
    my %hash    = inject_utils::parse_vulnerability($line);
    my $query   = inject_utils::gen_select($table, 2, 'vuln_type', 'vuln_seq', 'vuln_desc_primary', $hash{'vuln_desc_primary'}, 'vuln_desc_secondary', $hash{'vuln_desc_secondary'});

    # perform the duplicate check
    my ($success, @results) = inject_utils::db_exec($db, $query);

    # perform the insert unless we found something 
    unless ($success > 0) {

	# create the insertion query
	inject_utils::log_write(3, "vulnerability not found for ".
				$hash{'vuln_type'}."-".$hash{'vuln_seq'}.
				", generating INSERT query");
	$hash{'vuln_type'} = $vuln_type;
	$query = inject_utils::gen_insert($table, %hash);

	# perform the insertion
	($success, @results) = inject_utils::db_exec($db, $query);

	# report on the results
	if ($success > 0) {

	    # store the given vuln_seq, or retrieve the last inserted one
	    if ($hash{'vuln_type'} eq 'CVE') { 
		inject_utils::log_write(3, "vuln_type is CVE, ignoring LAST_INSERT_ID()");
		$vuln_seq = $hash{'vuln_seq'}; }
	    else { $vuln_seq = $results[0]; }

	    # log the results
	    inject_utils::log_write(3, "INSERT successful");
	    inject_utils::log_write(4, "vuln_type = $vuln_type");
	    inject_utils::log_write(4, "vuln_seq  = $vuln_seq");

	}

	else { 

	    # log the failure
	    inject_utils::log_write(3, "INSERT failed, scrubbing vuln_seq");
	    $vuln_seq  = '';

	}

    }

    # log the we got a hit and move along
    else {

	# grab the returns and mark it
	$vuln_type = shift @results;
	$vuln_seq  = shift @results;
	inject_utils::log_write(3, "vulnerability found for $vuln_type-$vuln_seq, skipping insert");

    }

    # -----------------------------------------------------------------------
    # restore the sub name and return the results
    $inject_utils::this_sub = $last_sub;
    return ($vuln_type, $vuln_seq);

}

sub create_vuln_impact {
# ---------------------------------------------------------------------------
# Purpose : does the actual VULN_IMPACT injection
# Params  : 0 - db handler
#           1 - vuln_type
#           2 - vuln_seq
#           4 - line of data to be parsed/injected
# Returns : 0 - results of the operation
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "create_impact()";

    # -----------------------------------------------------------------------

    # grab the parameters
    my $db        = shift @_;
    my $vuln_type = shift @_;
    my $vuln_seq  = shift @_;
    my $line      = shift @_;

    # set up our other variables
    my $found   = 0;
    my $table   = "VULN_IMPACTS";
    my %hash    = inject_utils::parse_impact($line);
    my $query   = inject_utils::gen_select($table, 2, "vuln_type", "vuln_seq", %hash);

    # perform the duplicate check
    my ($success, @results) = inject_utils::db_exec($db, $query);

    # perform the insert unless we found something 
    unless ($success > 0) {

	# create the insertion query
	inject_utils::log_write(3, "impact not found for $vuln_type-$vuln_seq, generating INSERT query");
	$query = inject_utils::gen_insert($table, 
					  'vuln_type', $vuln_type,
					  'vuln_seq',  $vuln_seq,
					  %hash);

	# perform the insertion (no PK to be returned, so no need to check)
	@results = inject_utils::db_exec($db, $query);

    }

    # log the we got a hit and move along
    else { inject_utils::log_write(2, "impact found for $vuln_type-$vuln_seq, skipping insert"); }

    # -----------------------------------------------------------------------
    # restore the sub name and return the results
    $inject_utils::this_sub = $last_sub;
    return ($success);

}

sub create_vuln_product {
# ---------------------------------------------------------------------------
# Purpose : does the actual VULN_PRODUCT injection
# Params  : 0 - db handler
#           1 - vuln_type
#           2 - vuln_seq
#           3 - prod_id
# Returns : none
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "create_impact()";

    # -----------------------------------------------------------------------

    # grab the parameters
    my $db        = shift @_;
    my $vuln_type = shift @_;
    my $vuln_seq  = shift @_;
    my $prod_id   = shift @_;

    my $table     = "VULN_PRODUCTS";

    # generate the VULN_PRODUCT select query to check for duplicates
    inject_utils::log_write(3, "preparing $table duplicate check query");
    my $query = inject_utils::gen_select($table, 0, 
					 'vuln_type', $vuln_type,
					 'vuln_seq',  $vuln_seq, 
					 'prod_id',   $prod_id);

    # execute the query
    inject_utils::log_write(3, "executing $table duplicate check query");
    my ($success, @results) = inject_utils::db_exec($db, $query);

    # do the insert unless we find a dupe
    unless ($success > 0) {
		
	# generate the VULN_PRODUCT insert query
	inject_utils::log_write(3, "$table entry does not exist, preparing insert query");
	$query = inject_utils::gen_insert($table, 
					  'vuln_type', $vuln_type,
					  'vuln_seq',  $vuln_seq, 
					  'prod_id',   $prod_id);

	# execute the query
	inject_utils::log_write(3, "executing $table insert query");
	($success, @results) = inject_utils::db_exec($db, $query);

	# log the resuls
	if ($success > 0) { inject_utils::log_write(3, "$table insert successful"); }
	else { inject_utils::log_write(1, "$table insert failed"); }

    }

    # log that we hit a dupe
    else { inject_utils::log_write(2, "vulnerable product found for $vuln_type-$vuln_seq and $prod_id, skipping insert"); }

    # -----------------------------------------------------------------------
    # restore the sub name and return the results
    $inject_utils::this_sub = $last_sub;

}

sub create_vuln_reference {
# ---------------------------------------------------------------------------
# Purpose : does the actual VULN_REFERENCES injection
# Params  : 0 - db handler
#           1 - vuln_type
#           2 - vuln_seq
#           4 - line of data to be parsed/injected
# Returns : none
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "create_reference()";

    # -----------------------------------------------------------------------

    # grab the parameters
    my $db        = shift @_;
    my $vuln_type = shift @_;
    my $vuln_seq  = shift @_;
    my $line      = shift @_;

    # set up our other variables
    my $found   = 0;
    my $table   = "VULN_REFERENCES";
    my %hash    = inject_utils::parse_reference($line);
    my $query   = inject_utils::gen_select($table, 2, "vuln_type", "vuln_seq", %hash);

    # perform the duplicate check
    my ($success, @results) = inject_utils::db_exec($db, $query);

    # perform the insert unless we found something 
    unless ($success > 0) {

	# create the insertion query
	inject_utils::log_write(3, "reference not found for $vuln_type-$vuln_seq, generating INSERT query");
	$query = inject_utils::gen_insert($table, 
					  'vuln_type', $vuln_type,
					  'vuln_seq',  $vuln_seq,
					  %hash);

	# perform the insertion (no PK to be returned, so no need to check)
	@results = inject_utils::db_exec($db, $query);

    }

    # log the we got a hit and move along
    else { inject_utils::log_write(2, "reference found for $vuln_type-$vuln_seq, skipping insert"); }

    # -----------------------------------------------------------------------
    # restore the sub name and return the results
    $inject_utils::this_sub = $last_sub;
    return ($success);

}

sub create_vuln_solution {
# ---------------------------------------------------------------------------
# Purpose : does the actual VULN_SOLUTIONS injection
# Params  : 0 - db handler
#           1 - vuln_type
#           2 - vuln_seq
#           4 - line of data to be parsed/injected
# Returns : 0 - results of the operation
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "create_solution()";

    # -----------------------------------------------------------------------

    # grab the parameters
    my $db        = shift @_;
    my $vuln_type = shift @_;
    my $vuln_seq  = shift @_;
    my $line      = shift @_;

    # set up our other variables
    my $found   = 0;
    my $table   = "VULN_SOLUTIONS";
    my %hash    = inject_utils::parse_solution($line);
    my $query   = inject_utils::gen_select($table, 2, "vuln_type", "vuln_seq", %hash);

    # perform the duplicate check
    my ($success, @results) = inject_utils::db_exec($db, $query);

    # perform the insert unless we found something 
    unless ($success > 0) {

	# create the insertion query
	inject_utils::log_write(3, "solution not found for $vuln_type-$vuln_seq, generating INSERT query");
	$query = inject_utils::gen_insert($table, 
					  'vuln_type', $vuln_type,
					  'vuln_seq',  $vuln_seq,
					  %hash);

	# perform the insertion (no PK to be returned, so no need to check)
	@results = inject_utils::db_exec($db, $query);

    }

    # log the we got a hit and move along
    else { inject_utils::log_write(2, "solution found for $vuln_type-$vuln_seq, skipping insert"); }

    # -----------------------------------------------------------------------
    # restore the sub name and return the results
    $inject_utils::this_sub = $last_sub;
    return ($success);

}


# ---------------------------------------------------------------------------
# INJECTION SUBROUTINES
# ---------------------------------------------------------------------------

sub inject_blscr {
# ---------------------------------------------------------------------------
# Purpose : does the actual blscr injection
# Params  : 0 - db handler
#           1 - line of data to be injected
# Returns : 0 - results of the operation
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "inject_blscr()";

    # -------------------------------------------------------------------

    # grab the original data set and create the return array
    my $db       = shift @_;
    my @data_in  = @_;

    # let's count the entries for logging purposes
    my $lc   = 0;

    # handle each line of data to be inputted
    foreach (@data_in) {

	# strip out bad characters and increment our line counter
	$_ =~ tr/\'//; #'
	$lc++;

	# log that we've found a product line
	inject_utils::log_write(3, " [$lc] ---------- BLSCR LINE ----------");

	# create the product
	create_blscr($db, $_);

    }

    # -------------------------------------------------------------------
    $inject_utils::this_sub = $last_sub;

} # -------------------------------------------------------------------------


sub inject_product {
# ---------------------------------------------------------------------------
# Purpose : does the actual product injection
# Params  : 0 - db handler
#           1 - line of data to be injected
# Returns : 0 - results of the operation
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "inject_product()";

    # -------------------------------------------------------------------

    # grab the original data set and create the return array
    my $db       = shift @_;
    my @data_in  = @_;

    # let's count the entries for logging purposes
    my $lc   = 0;

    # handle each line of data to be inputted
    foreach (@data_in) {

	# strip out bad characters and increment our line counter
	$_ =~ tr/\'//; #'
	$lc++;

	# log that we've found a product line
	inject_utils::log_write(3, " [$lc] ---------- PRODUCT LINE ----------");

	# create the product
	create_product($db, $_);

    }

    # -------------------------------------------------------------------
    $inject_utils::this_sub = $last_sub;

} # -------------------------------------------------------------------------


sub inject_nvd {
# ---------------------------------------------------------------------------
# Purpose : does the actual nvd injection
# Params  : 0 - db handler
#           1 - line of data to be injected
# Returns : 0 - results of the operation
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "inject_nvd()";

    # -------------------------------------------------------------------

    # grab the original data set and create the return array
    my $db       = shift @_;
    my @data_in  = @_;

    # local variables
    my $vuln_type = 'CVE';
    my $vuln_seq  = 0;
    my $lc   = 0;

    # handle each line of data to be inputted
    foreach (@data_in) {

	# strip out bad characters and increment our line counter
	my $line = $_;
	$line =~ tr/\'//; #'
	$lc++;

	# handle a vulnerability line
	if ($line =~ /^vulnerability/) {

	    # log the line and create it
	    inject_utils::log_write(3, "[$lc] ---------- VULNERABILITY LINE ----------");   
	    ($vuln_type, $vuln_seq) = create_vulnerability($db, $vuln_type, $line);

	}

	# handle an impact line
	if ($line =~ /^impact/)        {

	    # log the line and create it
	    inject_utils::log_write(3, "[$lc] ---------- IMPACT LINE ----------");   
	    create_vuln_impact($db, $vuln_type, $vuln_seq, $line);

	}

	# handle a reference line
	if ($line =~ /^reference/)     {

	    # log the line and create it
	    inject_utils::log_write(3, "[$lc] ---------- REFERENCE LINE ----------");   
	    create_vuln_reference($db, $vuln_type, $vuln_seq, $line);

	}

	# handle a solution line
	if ($line =~ /^solution/)      {

	    # log the line and create it
	    inject_utils::log_write(3, "[$lc] ---------- SOLUTION LINE ----------");   
	    create_vuln_solution($db, $vuln_type, $vuln_seq, $line);

	}

	# handle a product line
	if ($line =~ /^product/)       {

	    # log the line and create it
	    inject_utils::log_write(3, "[$lc] ---------- PRODUCT LINE ----------");   
	    my $prod_id = create_product($db, $line);

	    inject_utils::log_write(3, "[$lc] prod_id = $prod_id");

	    # we need to create a vulnerable product now
	    if (($prod_id > 0) && ($vuln_seq > 0)) {

		# create the product
		create_vuln_product($db, $vuln_type, $vuln_seq, $prod_id);

	    }

	}

    }

    # -------------------------------------------------------------------
    $inject_utils::this_sub = $last_sub;

} # -------------------------------------------------------------------------


sub inject_scan {
# ---------------------------------------------------------------------------
# Purpose : does the actual scan injection injection
# Params  : 0 - db handler
#           1 - line of data to be injected
# Returns : 0 - results of the operation
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "inject_scan()";

    # -------------------------------------------------------------------

    # grab the original data set and create the return array
    my $vuln_type = shift @_;
    my $vuln_seq  = 0;
    my $db        = shift @_;
    my @data_in   = @_;

    # upload variables
    my %upload;

    # finding variables
    my $asset_id;
    my $finding_id;
    my $suppressable_vulns;

    # local variables
    my $lc   = 0;

    # handle each line of data to be inputted
    foreach (@data_in) {

	# strip out bad characters and increment our line counter
	my $line = $_;
	$line =~ tr/\'//; #'
	$lc++;

	# handle an upload line
	if ($line =~ /^upload/)        {

	    # log the line and create it
	    inject_utils::log_write(3, "[$lc] ---------- UPLOAD LINE ----------");   
	    %upload = inject_utils::parse_upload($line);

	}

	# handle a finding line
	if ($line =~ /^finding/)       {

	    # log the line and create it
	    inject_utils::log_write(3, "[$lc] ---------- FINDING LINE ----------");   

	    # create an asset
	    my %finding = inject_utils::parse_finding($line);
	    ($asset_id) = create_asset($db, $finding{'address_ip'}, $finding{'address_port'}, %upload);

	    # create a finding
	    ($finding_id) = create_finding($db, $asset_id, $line, %upload);

	    # collect vulnerabilities for this asset that have already been determined 
	    # false positives or accepted risks.
	    $suppressable_vulns = get_suppressable_vulns($db, $asset_id);
	}

	# handle a related line
	if ($line =~ /^related/)       {

	    # log the line and create it
	    inject_utils::log_write(3, "[$lc] ---------- RELATED LINE ----------");   

	    # create the finding_vuln
	    my %related = inject_utils::parse_related($line);
	    create_finding_vuln($db, $finding_id, $related{'vuln_type'}, $related{'vuln_seq'});

	    # if vulnerability has already been determined false positive
	    # or accepted risk on this asset
	    my $v_type = $related{'vuln_type'};
	    my $v_seq  = $related{'vuln_seq'};
	    my $is_suppressable = $suppressable_vulns->{get_suppress_key($v_type, $v_seq)};
	    if ($is_suppressable) {
	      # suppress the associated finding
	      inject_utils::log_write(3, "vulnerability $v_type, $v_seq suppressable for finding $finding_id on asset $asset_id");
	      suppress_finding($db, $finding_id);
	      }
	}


	# handle a vulnerability line
	if ($line =~ /^vulnerability/) {

	    # log the line and create it, relate to the current finding
	    inject_utils::log_write(3, "[$lc] ---------- VULNERABILITY LINE ----------");   
	    ($vuln_type, $vuln_seq) = create_vulnerability($db, $vuln_type, $line);
	    create_finding_vuln($db, $finding_id, $vuln_type, $vuln_seq);

	    # if vulnerability has already been determined false positive
	    # or accepted risk on this asset
	    my $is_suppressable = $suppressable_vulns->{get_suppress_key($vuln_type, $vuln_seq)};
	    if ($is_suppressable) {
	      # suppress the associated finding
	      inject_utils::log_write(3, "vulnerability $vuln_type, $vuln_seq suppressable for finding $finding_id on asset $asset_id");
	      suppress_finding($finding_id);
	      }
	}

	# handle an impact line
	if ($line =~ /^impact/)        {

	    # log the line and create it
	    inject_utils::log_write(3, "[$lc] ---------- IMPACT LINE ----------");   
	    create_vuln_impact($db, $vuln_type, $vuln_seq, $line);

	}

	# handle a reference line
	if ($line =~ /^reference/)     {

	    # log the line and create it
	    inject_utils::log_write(3, "[$lc] ---------- REFERENCE LINE ----------");   
	    create_vuln_reference($db, $vuln_type, $vuln_seq, $line);

	}

	# handle a solution line
	if ($line =~ /^solution/)      {

	    # log the line and create it
	    inject_utils::log_write(3, "[$lc] ---------- SOLUTION LINE ----------");   
	    create_vuln_solution($db, $vuln_type, $vuln_seq, $line);

	}

	# handle a product line
	if ($line =~ /^product/)       {

	    # log the line and create it
	    inject_utils::log_write(3, "[$lc] ---------- PRODUCT LINE ----------");   
	    my $prod_id = create_product($db, $line);

	    inject_utils::log_write(3, "[$lc] prod_id = $prod_id");

	    # we need to create a vulnerable product now
	    if (($prod_id > 0) && ($vuln_seq > 0)) {

		# create the vuln_product
		create_vuln_product($db, $vuln_type, $vuln_seq, $prod_id);

	    }

	}

    }

    # -------------------------------------------------------------------
    $inject_utils::this_sub = $last_sub;

} # -------------------------------------------------------------------------


sub inject_finding_list {
# ---------------------------------------------------------------------------
# Purpose : does the actual manual finding injection
# Params  : 0 - db handler
#           1 - line of data to be injected
# Returns : 0 - results of the operation
# ---------------------------------------------------------------------------

    my $last_sub = $inject_utils::this_sub;
    $inject_utils::this_sub = "inject_finding_list()";

    # -------------------------------------------------------------------

    # grab the original data set and create the return array
    my $db       = shift @_;
    my @data_in  = @_;

    # let's count the entries for logging purposes
    my $lc   = 0;

    # handle each line of data to be inputted
    foreach (@data_in) {

  $line = $_;

	# strip out bad characters and increment our line counter
	$line =~ tr/\'//; #'
	$lc++;

	if ($line =~ /^finding/)       {

	    # log the line and create it
	    inject_utils::log_write(3, "[$lc] ---------- FINDING LINE ----------");   

	    # create an asset
	    my %finding = inject_utils::parse_finding($line);
	    ($asset_id) = create_asset($db, $finding{'address_ip'}, $finding{'address_port'}, %finding);

	    # create a finding
	    ($finding_id) = create_finding($db, $asset_id, $line, %finding);

	}

    }

    # -------------------------------------------------------------------
    $inject_utils::this_sub = $last_sub;

} # -------------------------------------------------------------------------


sub inject_inventory {
# ---------------------------------------------------------------------------
# Purpose : does the actual inventory item injection
# Params  : 0 - db handler
#           1 - line of data to be injected
# Returns : 0 - results of the operation
# ---------------------------------------------------------------------------

  my $last_sub = $inject_utils::this_sub;
  $inject_utils::this_sub = "inject_inventory()";

  # -------------------------------------------------------------------

  # grab the original data set and create the return array
  my $db       = shift @_;
  my @data_in  = @_;

  # let's count the entries for logging purposes
  my $lc   = 0;

  DATA_LINE:
  # handle each line of data to be inputted
  foreach (@data_in) {

    $line = $_;

    # strip out bad characters and increment our line counter
    $line =~ tr/\'//; #'
    $lc++;

    if ($line =~ /^inventory/)       {

      # log the line and create it
      inject_utils::log_write(3, "[$lc] ---------- INVENTORY LINE ----------");   

      # parse out data elements from record
      %inventory = inject_utils::parse_inventory($line);

      # Create product (or access existing product)
      my $PRODUCT_TAG = 'product';
      my $prod_meta = "$inventory{'vendor'} $inventory{'product'} $inventory{'version'}";
      my @product_fields = (0, # is_nvd_defined
                            $prod_meta,
                            $inventory{'vendor'}, 
                            $inventory{'product'},
                            $inventory{'version'}, 
                            $inventory{'prod_description'});
      my $prod_record = inject_utils::gen_std($PRODUCT_TAG, @product_fields);
      my $prod_id = create_product($db, $prod_record);

      if (!$prod_id) {
        inject_utils::log_write(2, "[$lc] Unable to create PRODUCTS entry for $inventory{'vendor'} $inventory{'product'} $inventory{'version'}");
        next DATA_LINE;
        }

      # Create or access ASSETS, ASSET_ADDRESSES, SYSTEM_ASSETS
      # %inventory meets needs of %upload in create_asset()
      my $asset_id = create_asset($db, 
                                  $inventory{'ip_address'},
                                  $inventory{'port'},
                                  %inventory);      
      
      if (!$asset_id) {
        inject_utils::log_write(2, "[$lc] Unable to create ASSETS entry for $inventory{'hostname'} $inventory{'ip_address'} $inventory{'port'}");
        next DATA_LINE;
        }
      
      #
      # Associate asset with product
      #
      my $sql = "UPDATE ASSETS SET prod_id = $prod_id WHERE asset_id = $asset_id";
		  ($success, @results) = inject_utils::db_exec($db, $sql);
		  if (!$success) {
        inject_utils::log_write(2, "[$lc] Unable to associate asset_id $asset_id with prod_id $prod_id");
        next DATA_LINE;
		    }
      }

    }

    # -------------------------------------------------------------------
    $inject_utils::this_sub = $last_sub;

} # -------------------------------------------------------------------------


sub get_suppressable_vulns {
# ---------------------------------------------------------------------------
# Purpose : retrieves asset's vulnerabilities that have been previously
#           determined Accepted Risk or False Positive
# Params  : 0 - asset id
# Returns : hash with key of "vuln_type:vuln_seq" for each vulnerability
#           found
# ---------------------------------------------------------------------------

	my $last_sub = $inject_utils::this_sub;
	$inject_utils::this_sub = "get_suppressable_vulns()";

	# -------------------------------------------------------------------

	my $db        = shift @_;
	my $asset_id  = shift @_;
	my @data_in   = @_;

	# create empty hash to store vuln seq/type keys
	my $suppressables = {};

	$sql = "SELECT fv.vuln_type, fv.vuln_seq
	        FROM FINDINGS f, POAMS p, FINDING_VULNS fv 
	        WHERE f.asset_id = $asset_id
	        AND p.finding_id = f.finding_id
        	AND p.poam_status = 'CLOSED'
	        AND (p.poam_type = 'AR' OR p.poam_type = 'FP')
	        AND fv.finding_id = f.finding_id";

	my ($success, @results) = inject_utils::db_exec($db, $sql);

	if($success) {

		# get number of 2-element rows from flat list of data elements
		$num_rows = scalar(@results) / 2;
    
		# for each vulnerability detected
		foreach my $i (0..$num_rows-1) {

			# store related vulnerability data in key of lookup hash
			$vuln_type = $results[(2*$i)];
			$vuln_seq  = $results[(2*$i)+1];
			$hash_key  = get_suppress_key($vuln_type, $vuln_seq);
			$suppressables->{$hash_key} = 1;
		}  

	} # if success

	else { inject_utils::log_write(3, "unable to find suppressable vulnerability for asset $asset_id"); }

	# -----------------------------------------------------------------------
	# restore the sub name and return the results

	$inject_utils::this_sub = $last_sub;
	return ($suppressables);

} # -------------------------------------------------------------------------


sub get_suppress_key {
# ---------------------------------------------------------------------------
# Purpose : unify generation of vuln type/seq hash key inserts & lookups
# Params  : 0 - vulnerability type
#           1 - vulnerability sequence number
# Returns : hash with key of "vuln_type:vuln_seq" 
# ---------------------------------------------------------------------------

	my ($vuln_type, $vuln_seq) = @_;
	return ("$vuln_type:$vuln_seq");

} # -------------------------------------------------------------------------


sub suppress_finding {
# ---------------------------------------------------------------------------
# Purpose : set status of $finding_id to SUPPRESSED
# Params  : 0 - finding id
# Returns : none
# ---------------------------------------------------------------------------

	my $last_sub = $inject_utils::this_sub;
	$inject_utils::this_sub = "inject_scan()";

	# -------------------------------------------------------------------

	my ($db, $finding_id) = @_;

	$sql = "UPDATE FINDINGS 
	        SET finding_status = 'SUPPRESSED'
        	WHERE finding_id = '$finding_id'";
          
	# check for success and report on error
	my ($success, @results) = inject_utils::db_exec($db, $sql);	
	if(!$success) { inject_utils::log_write(2, "unable to find suppress finding $finding_id"); }

} # -------------------------------------------------------------------------


# ---------------------------------------------------------------------------
# MAIN INJECTION SUBROUTINE
# ---------------------------------------------------------------------------

sub inject {
# ---------------------------------------------------------------------------
# Purpose : injects incoming data (in standardized injection format)
# Params  : 0 - array of data to be injected
# Returns : none
# ---------------------------------------------------------------------------

	# change the subroutine name for logging
	my $last_sub = $inject_utils::this_sub;
	$inject_utils::this_sub = "inject()";

	# grab the original data set and create the return array
	my @data_in  = @_;

	# -------------------------------------------------------------------
	# IMPORTANT: DO NOT MODIFY SUBROUTINE ABOVE THIS LINE!
	# -------------------------------------------------------------------

	# open the database connection once for the script
	my $db = inject_utils::db_open();

	# grab the first line so we can handle what comes next
	my @line = split($inject_utils::splitter, shift @data_in);

	# UTILITY PLUGINS
	if ($line[1] =~ /^blscr/)        { inject_blscr($db, @data_in);        }
	if ($line[1] =~ /^product/)      { inject_product($db, @data_in);      }
	if ($line[1] =~ /^nvd/)          { inject_nvd($db, @data_in);          }
	if ($line[1] =~ /^finding_list/) { inject_finding_list($db, @data_in); }
	if ($line[1] =~ /^inventory/)    { inject_inventory($db, @data_in);    }

	# SCANNER PLUGINS
	if ($line[1] =~ /^nessus/)       { inject_scan('NES', $db, @data_in);  }
	if ($line[1] =~ /^appdetective/) { inject_scan('APP', $db, @data_in);  }
	if ($line[1] =~ /^shadowscan/)   { inject_scan('SHA', $db, @data_in);  }
	
	# close the db connection
	inject_utils::db_close($db);

	# -------------------------------------------------------------------
	# IMPORTANT: DO NOT MODIFY SUBROUTINE BELOW THIS LINE!
	# -------------------------------------------------------------------

	# restore original subroutine for logger and return the 
	$inject_utils::this_sub = $last_sub;

} # -------------------------------------------------------------------------


# ---------------------------------------------------------------------------
# PLUGIN CONFIGURATION
# ---------------------------------------------------------------------------

# initialize core values
$inject_utils::this_name  = "inject"; # TODO: plugin name (lowercase, no spaces)
$inject_utils::this_sub   = "main()";
$inject_utils::this_debug = 1;

# grab the configuration and mark the start of logging session
inject_utils::log_start();

# ---------------------------------------------------------------------------
# MAIN LOGIC BLOCK
# ---------------------------------------------------------------------------

# create the array to handle the data
my @data;

# read the data from the input pipe
@data = inject_utils::pipe_read();

# inject the data
@data = inject(@data);

# ---------------------------------------------------------------------------
# PLUGIN CLEANUP
# ---------------------------------------------------------------------------

# mark the stop of logging
inject_utils::log_stop();
