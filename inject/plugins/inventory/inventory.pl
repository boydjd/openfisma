#! /usr/bin/perl -w
# -------------------------------------------------------------------
# FILE     : inventory.pl
# DESC     : Manual inventory spreadsheet translator
# INPUTS   : Tab-delimited data exported from MANUAL_FINDINGS.xls spreadsheet.
# OUTPUTS  : inventory.log - all actions are logged here
# DEPENDS  : this script requires the MySQL DBI to perform the inject
#
# AUTHOR   : Chuck Dolan
# DATE     : 12 Apr 2006
#
# COMMENTS : Spreadsheet format is as follows
#	           first line (doc header): 
#             Inventory Upload Spreadsheet
#             blank
#             blank
#             blank
#             blank
#             Submitted by:
#             submitter name
#             blank
#             Date Created:
#             date discovered
#            second line (column headers):
#             HOSTNAME
#             PRODUCT
#             VENDOR
#             VERSION
#             PRODUCT_DESCRIPTION
#             IP_ADDRESS
#             PORT
#             SYSTEM
#             NETWORK
#            data lines:
#             hostname
#             product
#             vendor
#             version
#             product_description
#             ip_address
#             port
#             system
#             network
# -------------------------------------------------------------------

# ---------------------------------------------------------------------------
# ENVIRONMENT
# -------------------------------------------


# ---------------------------------------------------------------------------
# PRAGMAS
# ---------------------------------------------------------------------------
require strict;
require warnings;
require diagnostics;

use lib "$ENV{'OVMS_ROOT'}/lib";
use inject_utils;
use vuln_translator_utils;

# ---------------------------------------------------------------------------
# TRANSLATION SUBROUTINE
# ---------------------------------------------------------------------------

sub translate {
# ---------------------------------------------------------------------------
# Purpose : translates incoming data into the standardized injection format
# Params  : 0 - array of string data to be translated
# Returns : 0 - array of standard injection strings
# ---------------------------------------------------------------------------

	# change the subroutine name for logging
	my $last_sub = $inject_utils::this_sub;
	$inject_utils::this_sub = "translate()";

	# grab the original data set and create the return array
	my @data_in  = @_;
	my @data_out;

	# -----------------------------------------------------------------------
	# IMPORTANT: DO NOT MODIFY SUBROUTINE ABOVE THIS LINE!
	# -----------------------------------------------------------------------
  
  #
  # Log in to db
  #
  my $db_hdl = inject_utils::db_open();
  if (!defined($db_hdl)) {
    log_write(0, "inventory.pl - could not open DB connection");
    }

  
  #
  # Get system id lookups
  #
  my $system_id_for = get_system_ids($db_hdl);
  if(!defined($system_id_for)) {
    inject_utils::log_write(2, "Unable to retrieve ids for SYSTEMS");
    die;
    }

  #
  # Get network id lookups
  #
  my $network_id_for = get_network_ids($db_hdl);
  if(!defined($network_id_for)) {
    inject_utils::log_write(2, "Unable to retrieve ids for NETWORKS");
    die;
    }


  my $SEPARATOR = "\t";
  my $BLANK_FIELD = ".";

  
  my $date_discovered;

	# handle the data line by line
  DATA_LINE:
  foreach (@data_in) {

    # pull off any line-ending character
    chomp;

    #
    # Clear leading and trailing spaces, then skip line if it's blank.
    #
	  s{^\s+}{};
	  s{\s+$}{};
	  next DATA_LINE if length == 0;
	
	  #
	  # Ensure that blank fields are recognized by split by inserting
	  # a space character between consecutive separators.
	  # Then make sure an empty final field is recognized.
	  #
	  s{$SEPARATOR$SEPARATOR}{$SEPARATOR$BLANK_FIELD$SEPARATOR};
	  s{$SEPARATOR$}{$SEPARATOR$BLANK_FIELD};
	
    #
    # Break input line on tab separators
    #
    my @fields = split /$SEPARATOR/;
    
    #
    # Clean any leading and trailing quotation marks, double double quotes
    # introduced by Excel export as well as leading and trailing spaces.
    #
    for (my $i = 0; $i < scalar(@fields); $i++) {
      my $field = $fields[$i];
      $field =~ s{^"}{};
      $field =~ s{"$}{};
      $field =~ s{""}{"}g;
      $field =~ s{^\s+}{};
      $field =~ s{\s+$}{};
      $field = $BLANK_FIELD if length($field) == 0;
      $fields[$i] = $field;
      #print "$i - $fields[$i]\n";
      }
		
    #
    # If this is the doc header line (starting with 'Inventory Upload Spreadsheet')
    #
    if (/^Inventory/) {
      #
      # Pull out the discovery date (format mm/dd/yyyy)
      #
      my $raw_date = $fields[9];
      my @date_fields = split(/\//, $raw_date);
      if (scalar(@date_fields) == 3) {
        $date_discovered = sprintf("%s-%02d-%02d", $date_fields[2], $date_fields[0], $date_fields[1]);
        }
      # skip to next line      
      next DATA_LINE;
		  } 
    #
    # If this is the second line of input (starting with 'HOSTNAME')
    #
    elsif (/^HOSTNAME\t/) {
      # just ignore it
      next DATA_LINE;
      }

		#
		# Pull out fields of interest
		# Original record format: 
    #  HOSTNAME PRODUCT VENDOR VERSION PRODUCT_DESCRIPTION IP_ADDRESS PORT SYSTEM NETWORK
		#
    my $hostname  = $fields[0];
    my $product   = $fields[1];
    my $vendor    = $fields[2];
    my $version   = $fields[3];
    my $desc      = $fields[4];
    my $ip        = $fields[5];
    my $port      = $fields[6];
    my $system    = $fields[7];
    my $network   = $fields[8];
		
#print "SYSTEM: $system DATE: $date_discovered\n";
		
    #
    # Resolve resource nicknames to numeric IDs for injection
    #
	  my $system_id;
    if (defined($system_id_for->{$system})) {
      $system_id = $system_id_for->{$system};
      }
    else {
      inject_utils::log_write(2, "Unable to retrieve id for SYSTEM '$system'");
      next DATA_LINE;
      }
				
	  my $network_id;
    if (defined($network_id_for->{$network})) {
      $network_id = $network_id_for->{$network};
      }
    else {
      inject_utils::log_write(2, "Unable to retrieve id for NETWORK '$network'");
      next DATA_LINE;
      }
				
		my @injection_fields = (
		  $hostname,
		  $vendor,
		  $product,
		  $version,
		  $desc,
		  $ip,
		  $port,
		  $system_id,
		  $network_id,
		  );
		  
		  
		#
		# Declare record header 
		#
    my $RECORD_TAG = "inventory";
    
    #
    # Format record fields into injection record line
    #
    push (@data_out, inject_utils::gen_std($RECORD_TAG, @injection_fields));
	  }

	# -----------------------------------------------------------------------
	# IMPORTANT: DO NOT MODIFY SUBROUTINE BELOW THIS LINE!
	# -----------------------------------------------------------------------

	# restore original subroutine for logger and return the 
	$inject_utils::this_sub = $last_sub;
	return (@data_out);

} # -------------------------------------------------------------------------


# ---------------------------------------------------------------------------
# PLUGIN CONFIGURATION
# ---------------------------------------------------------------------------

# initialize core values
$inject_utils::this_name  = "inventory";
$inject_utils::this_sub   = "main()";
$inject_utils::this_debug = 1;

# grab the configuration and mark the start of logging session
inject_utils::log_start();

# ---------------------------------------------------------------------------
# MAIN LOGIC BLOCK
# ---------------------------------------------------------------------------

# set up an array to grab the input data
my @data;

# read the data from the input pipe
@data = inject_utils::pipe_read();

# pull off 'upload' line from the beginning of the data
$data[0] =~ m/(^upload<>\d*<>\d*<>\d*)/;
$upload_line = $1;
$data[0] = substr($data[0], length($upload_line));

# translate the data
@data = translate(@data);

# prepend the data with the upload line
#unshift (@data, $upload_line); 

# prepend the data with the plugin name
unshift (@data, inject_utils::gen_std("plugin", $inject_utils::this_name));

# write the data to the output pipe
inject_utils::pipe_write(@data);

# ---------------------------------------------------------------------------
# PLUGIN CLEANUP
# ---------------------------------------------------------------------------

# mark the stop of logging
inject_utils::log_stop();


# ---------------------------------------------------------------------------
# UTILITY CALLS
# ---------------------------------------------------------------------------


#
# Retrieve system ids hashed by system nickname
#  system_nickname => system_id
#
sub get_system_ids {
  my ($db_hdl) = @_;

  my $sql = 'SELECT system_nickname, system_id FROM SYSTEMS';

#  return {'CDDTS' => 11, 'COD' => 12, 'DLSS' => 13}; # non-db testing
  return hash_field_one_to_zero($db_hdl, $sql);
  }

#
# Retrieve network ids hashed by network name
#  network_name => network_id
#
sub get_network_ids {
  my ($db_hdl) = @_;
  
  my $sql = 'SELECT network_nickname, network_id FROM NETWORKS';

#  return {'CSC-CT' => 102}; # non-db testing
  return hash_field_one_to_zero($db_hdl, $sql);
  }

#
# Creates hash from passed-in sql in following format:
#  $row[0] => $row[1]
#
# Returns hash ref on success, undef otherwise.
#
sub hash_field_one_to_zero {
  my ($db_hdl, $sql) = @_;

  my ($success, @results) = inject_utils::db_exec($db_hdl, $sql);
  
  if(!$success) {
    return undef;
    }
  
  my $id_for = {};
  
  #
  # db_exec
  #
  $num_rows = scalar(@results) / 2;

  for($i = 0; $i < $num_rows; $i++) {
    $id_for->{$results[(2*$i)]} = $results[((2*$i)+1)];
    }

  return $id_for;
  }