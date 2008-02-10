# ---------------------------------------------------------------------------
# FILE     : inject_utils.pm
# DESC     : contains injection utility subroutines
#
# AUTHOR   : Brian Gant
# DATE     : 12/28/05
#
# COMMENTS :
#
# ---------------------------------------------------------------------------

# PACKAGE NAME --------------------------------------------------------------
package inject_utils;


# PRAGMAS -------------------------------------------------------------------
require strict;
require DBI;


# VARIABLES -----------------------------------------------------------------
our $splitter	= '<>';		# set here and used by all others
our %config;				# set by get_config, used internally

our $db_host	= 'localhost';	# where are we connecting?
our $db_name	= 'openfisma';		# what is our database name?
our $db_user	= 'openfisma';		# who do we connect as?
our $db_pass	= '0p3nfism@';	# what is our password?

# Check for ini file settings for db user and pass
($ini_user, $ini_pass) = read_php_login('../www/ovms.ini.php');
if (defined($ini_user) && defined($ini_pass)) {
  #print "found: $ini_user $ini_pass\n";
  $db_user	= $ini_user;
  $db_pass	= $ini_pass;
  }

our $ovms_root  = $ENV{'OVMS_ROOT'};    # grab the root OVMS directory21~b
our $log_dir	= $ovms_root.'/log';

our $this_name	= undef;		# set by invoker, root of all file names
our $this_sub	= undef;		# set by invoker subroutines
our $this_debug	= 2;			# defaults to 1, can be set by others


# ---------------------------------------------------------------------------
# UTILITY SUBROUTINES
# ---------------------------------------------------------------------------

sub timestamp {
# ---------------------------------------------------------------------------
# Purpose : generates a date and time stamp
# Params  : none
# Returns : a SQL DATETIME string
# ---------------------------------------------------------------------------
		
    # grab the local time
    my ($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime time;

    # add the year
    my $date = 1900 + $year;

    # add the dash
    $date .= '-';

    # add the month
    if ($mon < 9) { $date .= '0'; }
    $date .= 1 + $mon;

    # add the dash
    $date .= '-';

    # add the day
    if ($mday < 10) { $date .= '0'; }
    $date .= $mday;
	
	# add the time string
	$date .= " $hour:$min:$sec";

    # return the conconction
    return ($date);

}


# ---------------------------------------------------------------------------
# LOG SUBROUTINES
# ---------------------------------------------------------------------------

sub log_write {
# ---------------------------------------------------------------------------
# Purpose : writes a timestamped, sub located string for log entries
# Params  : none
# Returns : none
#
# COMMENTS :
#
# This is certainly not the most efficient means of handling the logging
# feature, but it works. Global file handles?
#
# ---------------------------------------------------------------------------

        # grab the message level and the string
        my $level  = shift @_;
        my $string = shift @_;
	

	# compare the message level to our debug level
	if ($level <= $this_debug) {


	    # open the log for writing
	    open (LOG, ">>$log_dir/$this_name.log");

	    # tag the message leve;
	    if ($level == 0) { print LOG "[ SYSTEM  ] "; }
	    if ($level == 1) { print LOG "[ ERROR   ] "; }
	    if ($level == 2) { print LOG "[ WARNING ] "; }
	    if ($level == 3) { print LOG "[ INFO    ] "; }
	    if ($level == 4) { print LOG "[ DEBUG   ] "; }

	    # write to the log
	    print LOG ((localtime time)." : $this_name.pl : $this_sub : ".$string."\n");

            # close the log
	    close(LOG);
	    
	}


} # -------------------------------------------------------------------------


# alias logging functions
sub log_start {	log_write(0, "---------- LOGGING SESSION STARTED ----------"); }
sub log_stop  {	log_write(0, "---------- LOGGING SESSION STOPPED ----------\n"); }


# ---------------------------------------------------------------------------
# FILE SUBROUTINES
# ---------------------------------------------------------------------------

sub pipe_read {
# ---------------------------------------------------------------------------
# Purpose : reads the incoming pipe into an array and returns it
# Params  : none
# Returns : 0 - array of piped in data lines
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "pipe_read()";

	# -----------------------------------------------------------------------
 
	my $count = 0;			# count the lines for debug level 2
	my @data;				# store the pipe data

	# LOGGING
	log_write(0, "reading from input pipe");

	# read the datapipe and and count the lines
	while (<>) {chomp $_; unless ('') { push @data, $_; $count++; } }

	# LOGGING
	log_write(2, $count." lines read from pipe");
	log_write(0, "input pipe closed");

	# -----------------------------------------------------------------------

	$this_sub = $last_sub;
	return @data;

}	# -----------------------------------------------------------------------


sub pipe_write {
# ---------------------------------------------------------------------------
# Purpose : writes the incoming array writes it to the pipe
# Params  : data array
# Returns : none
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "pipe_write()";

	# -----------------------------------------------------------------------

	# count the lines read for information purposes
	my $count = 0;


	# LOGGING
	log_write(0, "writing to output pipe");

	# write the array to the output pipe
	foreach (@_) { print $_."\n"; $count++; }

	# LOGGING
	log_write(2, $count." lines written to pipe");
	log_write(0, "output pipe closed");

	# -----------------------------------------------------------------------
	
	$this_sub = $last_sub;

}	# -----------------------------------------------------------------------


# ---------------------------------------------------------------------------
# TRANSLATION PLUGIN TOOLS
# --------------------------------------------------------------------------

sub gen_std {
# ---------------------------------------------------------------------------
# Purpose : generates a standard insert string from the passed in array
# Params  : 0 - array of strings to concatenate into a single standard string
# Returns : 0 - standard string for the injection module
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "gen_std()";

	# -----------------------------------------------------------------------

	# string to catch concatenation
	my $std = '';

	# LOGGING
	log_write(3, "generating standard string");

	# grab each parameter in order and add it onto the string
	while (@_ > 0) { $std .= shift(@_).$splitter; }

	# remove the extra end $splitter
	$std = reverse(substr((reverse $std), length($splitter)));

	# LOGGING
	log_write(4, "std = $std");

	# return the standard string
	$this_sub = $last_sub;
	return ($std);

} # -------------------------------------------------------------------------


# ---------------------------------------------------------------------------
# DATABASE TOOLS
# ---------------------------------------------------------------------------

sub db_open {
# ---------------------------------------------------------------------------
# Purpose : subroutine to open a database connection
# Params  : none
# Returns : 0 - db handler
#
# COMMENTS :
# 
# Subroutine must be called after after config() in order to grab required
# parameters for the database connection.
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "db_open()";

	# -----------------------------------------------------------------------

	# attempt to make the connection
        my $db = DBI->connect("DBI:mysql:$db_name:$db_host", $db_user, $db_pass) or undef;

	# LOGGING
	unless (defined $db) { log_write(0, "database connection could not be opened"); }
	else {

	    log_write(0, "database connection opened");
	    log_write(4, "db = $db");

	}

	# return the db handler
	$this_sub = $last_sub;
	return ($db);

} # -------------------------------------------------------------------------


sub db_close {
# ---------------------------------------------------------------------------
# Purpose : subroutine to close a database connection
# Params  : 0 - db handler
# Returns : none
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "db_close()";

	# -----------------------------------------------------------------------

	# grab the db handler parameter
	my $db = shift @_;

	# close the database connection
	$db->disconnect();

	# LOGGING
	log_write(0, "database connection closed");

	$this_sub = $last_sub;


} # -------------------------------------------------------------------------


sub db_exec {
# ---------------------------------------------------------------------------
# Purpose : executes a SQL statement and return the results or insert id
# Params  : 0 - db handler
#	    1 - SQL statement
# Returns : 0 - success of the operation
#           1 - results array
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "db_exec()";

	# -----------------------------------------------------------------------

	# grab the parameters
	my $db     = shift @_;
	my $query  = shift @_;
	
	# internal variables to make our life easier
	my $success = 1;
	my $select_success = 0;
	my @results;

	# prepare the statement and execute it, noting the results
	log_write(3, "preparing query for execution");
	my $sth = $db->prepare($query);
	$sth->execute() or $success = 0;

	# retrieve LAST_INSERT_ID() on INSERT or query results on SELECT
	if ($success > 0) {

	    # just return the results from our SELECT statement
	    if ($query =~ /^SELECT/) { 

		# return the results and log it
		log_write(3, "SELECT execution successful, retrieving results");
		while (@row = $sth->fetchrow_array()) {

		    # push each returned element on the results array
		    # up to the caller to handle this properly
		    foreach (@row) { 
			
			log_write(4, "SELECT returned: $_");
			push(@results, $_);

		    }

		}

		unless (@results > 0) {log_write(4, "SELECT returned no results"); }
		else { $select_success = 1; }

	    }
		
	    # need to actually SELECT LAST_INSERT_ID() to fetch results
	    if ($query =~ /^INSERT/) {

		# logging
		log_write(3, "INSERT execution successful, retrieving LAST_INSERT_ID()");

		# flag to go ahead and fetch based on a successful SELECT
		$select_success = 1;

		# prepare the statement and execute it, noting the results
		$sth = $db->prepare("SELECT LAST_INSERT_ID()");
		$sth->execute() or $select_success = 0;
			
		# grab the results from the SELECT statement if we're stil good to go
		if ($select_success > 0) {

		    @results = $sth->fetchrow_array();
		    log_write(4, "LAST_INSERT_ID() = ".$results[0]);

		}
		else { log_write(1, "unable to retrieve LAST_INSERT_ID"); }
	
	    }

	}
       
	# the attempt failed
	else { 

	    # log the incident
	    log_write(1, "query execution failed: ".$DBI::errstr);
	    log_write(1, "query = $query");
	}

	# return result array
	$this_sub = $last_sub;
	return ($select_success, @results);

} # -------------------------------------------------------------------------


# ---------------------------------------------------------------------------
# QUERY GENERATION TOOLS
# ---------------------------------------------------------------------------

sub gen_insert {
# ---------------------------------------------------------------------------
# Purpose : generic sub to create insert statements with arbitraty elements
# Params  : 0 - table name
#           1 - hash of column headers and values
# Returns : 0 - SQL insert statement
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "gen_insert()";

	# -----------------------------------------------------------------------

	# shift off the table name, then store the rest as a hash
	my $table = shift @_;
	my %hash  = @_;

	# set up for the concatenation
	my $cols = '';
	my $vals = '';

	# step through the hash
	for my $key (sort keys %hash) {

		# create the column header string and the values string
		$cols .= "$key,";
		$vals .= '\''.$hash{$key}.'\',';

	}

	# drop the last comma on our arrays
	chop $cols;
	chop $vals;

	# create the query and log it
	my $query = "INSERT INTO $table ($cols) VALUES ($vals)";
	log_write(4, "query = $query");

	# return our create query
	$this_sub = $last_sub;
	return $query;

} # -------------------------------------------------------------------------


sub gen_select {
# ---------------------------------------------------------------------------
# Purpose : generic sub to create select statements with arbitraty elements
# Params  : parameter handling depends on the second parameter passed
#
#           the first parameter passed is ALWAYS the table name
#
#			if 0 then is passed next then the remainder of the parameters are
#           a hash of column/value pairs to be used in the WHERE clause
#
#           if any positive nonzero integer is passed instead, that number
#           indicates the number of times to shift the remaining paramater
#           array before storing the reminder in the coumn/value pair hash
#
#           the shifted values are then used to describe the desired return
#           field(s) from the SQL statement
#
# Returns : 0 - SQL select statement
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "gen_select()";

	# -----------------------------------------------------------------------

	# shift off the table name
	my $table  = shift @_;

	# shift off the limiter
	my $lim = shift @_;

	# handle the
	my @returns;
	while ($lim-- > 0) { push (@returns, (shift @_)); }

	# grab the remainder of the parameters to creat the hash
	my %hash  = @_;

	# set up for the concatenation
	my $rets = '';
	my $vals = '';

	# create the return values for the select string
	if (@returns > 0) {

		# add each element and a comma, then chop the last comma off
		foreach (@returns) { $rets .= $_.','; }
		chop $rets;

	}
	else { $rets = '*'; }

	# step through the hash
	for my $key (sort keys %hash) {

		# create our header delimiter string and value string
		$vals .= "$key='".$hash{$key}."' AND ";

	}

	# drop the last comma and the last " AND "
	$vals = reverse(substr((reverse $vals), 5));

	# grab the query and log it
	my $query = "SELECT $rets FROM $table WHERE ($vals)";
	log_write(4, "query = $query");

	# return the values
	$this_sub = $last_sub;
	return $query;
	

} # -------------------------------------------------------------------------


# ---------------------------------------------------------------------------
# STANDARD INJECTION FORMAT PARSING TOOLS
# ---------------------------------------------------------------------------

sub parse_plugin {
# ---------------------------------------------------------------------------
# Purpose : takes a standard injection plugin string and creates a hash
# Params  : 0 - standard plugin string
# Returns : 0 - hash of column names and values
#
# COMMENTS :
#
# Sub works with the following format (replace "|" with $splitter):
# plugin|plugin_name
#
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "parse_plugin()";

	# -----------------------------------------------------------------------

	# split the string on $splitter and parse the array
	my @elements = split ($splitter, $_[0]);

	# return the hash of column names and values
	$this_sub = $last_sub;
	return ("plugin_name" => $elements[1]);

} # -------------------------------------------------------------------------


sub parse_vulnerability {
# ---------------------------------------------------------------------------
# Purpose : takes a standard injection vulnerability string and creates a hash
# Params  : 0 - standard vulnerability string
# Returns : 0 - hash of column names and values
#
# COMMENTS :
#
# Sub works with the following format (replace "|" with $splitter):
# vulnerability|cve_name|desc_primary|desc_secondary|
# 	date_discovered|date_published|date_modified|vuln_severity|
# 	loss_availability|loss_confidentiality|loss_integrity|
# 	loss_security_admin|loss_security_user|loss_security_other|
# 	type_access|type_input|type_input_bound|type_input_buffer|
# 	type_design|type_exception|type_environment|type_config|
# 	type_race|type_other|range_local|range_remote|range_user
#
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "parse_vulnerability()";

	# -----------------------------------------------------------------------

	# split the string on $splitter and parse the array
	my @elements = split ($splitter, $_[0]);

	# return the hash of column names and values
	# use parse_related to get vuln_type and vuln_seq from the cve_name
	$this_sub = $last_sub;
	return ( parse_related("related$splitter".$elements[1]),
		 "vuln_desc_primary"         => $elements[2],
		 "vuln_desc_secondary"       => $elements[3],
		 "vuln_date_discovered"      => $elements[4],
		 "vuln_date_published"       => $elements[5],
		 "vuln_date_modified"        => $elements[6],
		 "vuln_severity"             => $elements[7],
		 "vuln_loss_availability"    => $elements[8],
		 "vuln_loss_confidentiality" => $elements[9],
		 "vuln_loss_integrity"       => $elements[10],
		 "vuln_loss_security_admin"  => $elements[11],
		 "vuln_loss_security_user"   => $elements[12],
		 "vuln_loss_security_other"  => $elements[13],
		 "vuln_type_access"          => $elements[14],
		 "vuln_type_input"           => $elements[15],
		 "vuln_type_input_bound"     => $elements[16],
		 "vuln_type_input_buffer"    => $elements[17],
		 "vuln_type_design"          => $elements[18],
		 "vuln_type_exception"       => $elements[19],
		 "vuln_type_environment"     => $elements[20],
		 "vuln_type_config"          => $elements[21],
		 "vuln_type_race"            => $elements[22],
		 "vuln_type_other"           => $elements[23],
		 "vuln_range_local"          => $elements[24],
		 "vuln_range_remote"         => $elements[25],
		 "vuln_range_user"           => $elements[26]);

} # -------------------------------------------------------------------------


sub parse_blscr {
# ---------------------------------------------------------------------------
# Purpose : takes a standard injection solution string and creates a hash
# Params  : 0 - standard solution string
# Returns : 0 - hash of column names and values
#
# COMMENTS :
#
# TODO: fiddle with hash names some, rename net_id to network_id in DB
#
# Sub works with the following format (replace "|" with $splitter):
# finding|source_id|scan_tool|system_id|network_id|ipv4|ipv6|port|date
#
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "parse_blscr()";

	# -----------------------------------------------------------------------

	# split the string on $splitter and parse the array
	my @elements = split ($splitter, $_[0]);

	# return the hash of column names and values
	$this_sub = $last_sub;
	return ("blscr_class"        => $elements[1],
		"blscr_family"       => $elements[2],
		"blscr_number"       => $elements[3],
		"blscr_subclass"     => $elements[4],
		"blscr_control"      => $elements[5],
		"blscr_guidance"     => $elements[6],
		"blscr_low"          => $elements[7],
		"blscr_moderate"     => $elements[8],
		"blscr_high"         => $elements[9],
		"blscr_enhancements" => $elements[10],
		"blscr_supplement"   => $elements[11]);

} # -------------------------------------------------------------------------

sub parse_finding {
# ---------------------------------------------------------------------------
# Purpose : takes a standard injection finding string and creates a hash
# Params  : 0 - standard finding string
# Returns : 0 - hash of column names and values
#
# COMMENTS :
#
# TODO: fiddle with hash names some, rename net_id to network_id in DB
#
# Sub works with the following format (replace "|" with $splitter):
# finding|address|port|date_discovered|data
#
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "parse_finding()";

	# -----------------------------------------------------------------------

	# split the string on $splitter and parse the array
	my @elements = split ($splitter, $_[0]);

	# return the hash of column names and values
	$this_sub = $last_sub;
	return ("address_ip"  	    	  => $elements[1],
		"address_port"            => $elements[2],
		"finding_date_discovered" => $elements[3],
		"finding_instance_data"   => $elements[4]);

} # -------------------------------------------------------------------------

sub parse_upload {
# ---------------------------------------------------------------------------
# Purpose : takes a standard injection solution string and creates a hash
# Params  : 0 - standard solution string
# Returns : 0 - hash of column names and values
#
# COMMENTS :
#
# Sub works with the following format (replace "|" with $splitter):
# finding|source_id|scan_tool|system_id|network_id|ipv4|ipv6|port|date
#
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "parse_upload()";

	# -----------------------------------------------------------------------

	# split the string on $splitter and parse the array
	my @elements = split ($splitter, $_[0]);

	# return the hash of column names and values
	$this_sub = $last_sub;
	return ("source_id"  => $elements[1],
		"system_id"  => $elements[2],
		"network_id" => $elements[3]);

} # -------------------------------------------------------------------------

sub parse_related {
# ---------------------------------------------------------------------------
# Purpose : takes a standard injection related string and creates a hash
# Params  : 0 - standard related string
# Returns : 0 - hash of column names and values
#
# COMMENTS :
#
# Sub works with the following format (replace "|" with $splitter):
# related|cve-nnnn-nnnn
#
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "parse_related()";

	# -----------------------------------------------------------------------

	# split the string on $splitter and parse the array
	my @elements = split ($splitter, $_[0]);

	# make life easier and split CVE into vuln_type and vuln_seq here
	my @vuln = split ('-', $elements[1]);

	# return the hash of column names and values
	$this_sub = $last_sub;
	return ("vuln_type" => $vuln[0],
		"vuln_seq"  => $vuln[1].$vuln[2]);

} # -------------------------------------------------------------------------


sub parse_reference {
# ---------------------------------------------------------------------------
# Purpose : takes a standard injection reference string and creates a hash
# Params  : 0 - standard reference string
# Returns : 0 - hash of column names and values
#
# COMMENTS :
#
# Sub works with the following format (replace "|" with $splitter):
# reference|name|source|url|is_advisory|has_tool_sig|has_patch
#
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "parse_reference()";

	# -----------------------------------------------------------------------

	# split the string on $splitter and parse the array
	my @elements = split ($splitter, $_[0]);

	# return the hash of column names and values
	$this_sub = $last_sub;
	return ("ref_name"         => $elements[1],
		"ref_source"       => $elements[2],
		"ref_url"          => $elements[3],
		"ref_is_advisory"  => $elements[4],
		"ref_has_tool_sig" => $elements[5],
		"ref_has_patch"    => $elements[6]);

} # -------------------------------------------------------------------------


sub parse_solution {
# ---------------------------------------------------------------------------
# Purpose : takes a standard injection solution string and creates a hash
# Params  : 0 - standard solution string
# Returns : 0 - hash of column names and values
#
# COMMENTS :
#
# Sub works with the following format (replace "|" with $splitter):
# solution|sol_desc|sol_source
#
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "parse_solution()";

	# -----------------------------------------------------------------------

	# split the string on $splitter and parse the array
	my @elements = split ($splitter, $_[0]);

	# return the hash of column names and values
	$this_sub = $last_sub;
	return ("sol_desc"   => $elements[1],
		"sol_source" => $elements[2]);

} # -------------------------------------------------------------------------


sub parse_impact {
# ---------------------------------------------------------------------------
# Purpose : takes a standard injection impact string and creates a hash
# Params  : 0 - standard impact string
# Returns : 0 - hash of column names and values
#
# COMMENTS :
#
# Sub works with the following format (replace "|" with $splitter):
# impact|imp_desc|imp_source
#
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "parse_impact()";

	# -----------------------------------------------------------------------

	# split the string on $splitter and parse the array
	my @elements = split ($splitter, $_[0]);

	# return the hash of column names and values
	$this_sub = $last_sub;
	return ("imp_desc"   => $elements[1],
		"imp_source" => $elements[2]);

} # -------------------------------------------------------------------------


sub parse_product {
# ---------------------------------------------------------------------------
# Purpose : takes a standard injection product string and creates a hash
# Params  : 0 - standard product string
# Returns : 0 - hash of column names and values
#
# COMMENTS :
#
# Sub works with the following format (replace "|" with $splitter):
# product|prod_nvd_defined|prod_vendor|prod_name|prod_version|prod_desc
#
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "parse_product()";

	# -----------------------------------------------------------------------

	# split the string on $splitter and parse the array
	my @elements = split ($splitter, $_[0]);

	# return the hash of column names and values
	$this_sub = $last_sub;
	return ("prod_nvd_defined" => $elements[1],
		"prod_meta"        => $elements[2],
		"prod_vendor"      => $elements[3],
		"prod_name"        => $elements[4],
		"prod_version"     => $elements[5],
		"prod_desc"        => $elements[6]);

} # -------------------------------------------------------------------------

sub parse_inventory {
# ---------------------------------------------------------------------------
# Purpose : takes a standard injection inventory record and creates a hash
# Params  : 0 - standard solution string
# Returns : 0 - hash of column names and values
#
# COMMENTS :
#
# Sub works with the following format (replace "|" with $splitter):
# inventory|hostname|vendor|product|version|prod_description|ip_address|
#  port|system_id|network_id
# ---------------------------------------------------------------------------

	my $last_sub = $this_sub;
	$this_sub = "parse_inventory()";

	# -----------------------------------------------------------------------

	# split the string on $splitter and parse the array
	my @elements = split ($splitter, $_[0]);

	# return the hash of column names and values
	$this_sub = $last_sub;
	return ("hostname"         => $elements[1],
          "vendor"           => $elements[2],
          "product"          => $elements[3],
          "version"          => $elements[4],
          "prod_description" => $elements[5],
          "ip_address"       => $elements[6],
          "port"             => $elements[7],
          "system_id"        => $elements[8],
          "network_id"       => $elements[9],);

} # -------------------------------------------------------------------------


sub read_php_login {
#------------------------------------------------------------------------------
# Purpose: read DB_USER and DB_PASS from common php/perl config file if there's
# one available
# Params: ini file name
# Return: ($db_user, $db_pass)
#         (undef, undef) if not found
#
#------------------------------------------------------------------------------
  my ($ini_file) = @_;

  #
  # Open file if it's available
  #
  if (!open(INIFILE, "$ini_file")) {
    return (undef, undef);
    }
  
  #
  # Read each line, looking for $DB_USER='user', $DB_PASS='pass'
  #
  $db_user = '';
  $db_pass = '';
  while (<INIFILE>) {
    if(/DB_USER.*=.*['"]([^'"]*)['"]/) {
      $db_user = $1;
      }
    if(/DB_PASS.*=.*['"]([^'"]*)['"]/) {
      $db_pass = $1;
      }
    }
    
  if ($db_user ne '' && $db_pass ne '') {
    return ($db_user, $db_pass);
    }
  else {
    return (undef, undef);
    }
}

# ---------------------------------------------------------------------------
# Let's say something positive for all of our adoring fans...
# ---------------------------------------------------------------------------
1;
