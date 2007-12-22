#! /usr/bin/perl -w
# -------------------------------------------------------------------
# FILE     : finding.pl
# DESC     : Manual finding spreadsheet translator
# INPUTS   : CSV data exported from MANUAL_FINDINGS.xls spreadsheet.
# OUTPUTS  : finding.log - all actions are logged here
# DEPENDS  : this script requires the MySQL DBI to perform the inject
#
# AUTHOR   : Chuck Dolan
# DATE     : 10 Apr 2006
#
# COMMENTS : Spreadsheet format is as follows
#	           first line (doc header): 
#             MANUAL FINDINGS SPREADSHEET
#             blank
#             blank
#             blank
#             Submitted by:
#             submitter name
#             date discovered
#            second line (column headers):
#             SYSTEM
#             SOURCE
#             IP_ADDRESS
#             PORT
#             NETWORK
#             FINDING_DESCRIPTION
#             DATE_DISCOVERED [refers to line above]
#            data lines:
#             system_nickname
#             source_nickname
#             ip_address
#             port
#             network
#             instance_data
#             blank
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

# --------------------------------------------------------------------------
# 
# SUPERFLUOUS CODE - source, system and network ids specified at upload time
#
# --------------------------------------------------------------------------
  
  #
  # Log in to db
  #
#  my $db_hdl = inject_utils::db_open();
#  if (!defined($db_hdl)) {
#    log_write(0, "finding.pl - could not open DB connection");
#    }
  
  #
  # Get source id lookups
  #
#  my $source_id_for = get_source_ids($db_hdl);
#  if(!defined($source_id_for)) {
#    inject_utils::log_write(2, "Unable to retrieve ids for FINDING_SOURCES");
#    die;
#    }

  #
  # Get system id lookups
  #
#  my $system_id_for = get_system_ids($db_hdl);
#  if(!defined($system_id_for)) {
#    inject_utils::log_write(2, "Unable to retrieve ids for SYSTEMS");
#    die;
#    }

#foreach my $key (keys(%$system_id_for)) {
#  print "$key - $system_id_for->{$key}\n";
#  }

  #
  # Get network id lookups
  #
#  my $network_id_for = get_network_ids($db_hdl);
#  if(!defined($network_id_for)) {
#    inject_utils::log_write(2, "Unable to retrieve ids for NETWORKS");
#    die;
#    }

# ---------------------------------------------------------------------------

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
		s/^\s+//;
		s/\s+$//;
		next DATA_LINE if length == 0;
	
		#
		# Ensure that blank fields are recognized by split by inserting
		# a space character between consecutive separators.
		# Then make sure an empty final field is recognized.
		#
		s/$SEPARATOR$SEPARATOR/$SEPARATOR$BLANK_FIELD$SEPARATOR/;
		s/$SEPARATOR$/$SEPARATOR$BLANK_FIELD/;
	
		#
		# Break input line on tab separators
		#
		my @fields = split /$SEPARATOR/;
    
		#
		# Clean any leading and trailing quotation marks, double double quotes
		# introduced by Excel export.
		#
		for (my $i = 0; $i < scalar(@fields); $i++) {
	
			my $field = $fields[$i];
			$field =~ s/^"//;
			$field =~ s/"$//;
			$field =~ s/""/"/g;
			$field = $BLANK_FIELD if length($field) == 0;
			$fields[$i] = $field;
#			print "$i - $fields[$i]\n";
		}
#		print "\n";
		
		#
		# If this is the doc header line (starting with MANUAL FINDINGS)
		#
		if (/^MANUAL/) {

			#
			# Pull out the discovery date (format mm/dd/yyyy)
			#
			my $raw_date     = $fields[6];
			my @date_fields  = split(/\//, $raw_date);
			$date_discovered = sprintf("%s-%02d-%02d", $date_fields[2], $date_fields[0], $date_fields[1]);

			# skip to next line      
			next DATA_LINE;
		} 

		#
		# If this is the second line of input (starting with SYSTEM)
		#
		elsif (/^SYSTEM/) {

			# just ignore it
			next DATA_LINE;
		}

		#
		# Pull out fields of interest
		# Original record format: 
		#  SYSTEM SOURCE IP PORT NETWORK DESCRIPTION DATE
		#
		my $system  = $fields[0];
		my $source  = $fields[1];
		my $ip      = $fields[2];
		my $port    = $fields[3];
		my $network = $fields[4];
		my $desc    = $fields[5];
		my $date_discovered = $fields[6];
		my $tool    = 'MAN';     # manual injection
		
# -----------------------------------------------------------------------
#
# SUPERFLUOUS - information given at upload time on front end
#
# -----------------------------------------------------------------------

		#print "SYSTEM: $system DATE: $date_discovered\n";
#		
#		#
#		# Resolve resource nicknames to numeric IDs for injection
#		#
#		my $system_id;
#		if (defined($system_id_for->{$system})) { $system_id = $system_id_for->{$system}; }
#	
#		else {
#
#			inject_utils::log_write(2, "Unable to retrieve id for SYSTEM '$system'");
#			next DATA_LINE;
#
#		}
#				
#		my $source_id;
#		if (defined($source_id_for->{$source})) { $source_id = $source_id_for->{$source}; }
#
#		else {
#
#			inject_utils::log_write(2, "Unable to retrieve id for FINDING_SOURCE '$source'");
#			next DATA_LINE;
#
#		}
#				
#		my $network_id;
#		if (defined($network_id_for->{$network})) { $network_id = $network_id_for->{$network}; }
#
#		else {
#
#			inject_utils::log_write(2, "Unable to retrieve id for NETWORK '$network'");
#			next DATA_LINE;
#
#		}
				
# ----------------------------------------------------------------------

#		my @injection_fields = (
#			$source_id,
#			$tool,
#			$system_id,
#			$network_id,
#			$ip,
#			$port,
#			$date_discovered,
#			$desc,
#			);

		my @injection_fields = (
			$ip,			# ip
			$port,			# port
			$date_discovered,	# date_discovered
			$desc			# instance data
			);
		  
		  
		#
		# Declare record header 
		#
		my $RECORD_TAG = "finding";
    
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
$inject_utils::this_name  = "finding_list";
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
#$data[0] =~ m/(^upload<>\d*<>\d*<>\d*)/;
#$upload_line = $1;
#$data[0] = substr($data[0], length($upload_line));

$upload_line = shift @data;

# translate the data
@data = translate(@data);

# prepend the data with the upload line
unshift (@data, $upload_line); 

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
# Retrieve source ids hashed by source nickname
#  source_nickname => source_id
#
sub get_source_ids {
  my ($db_hdl) = @_;

  my $sql = 'SELECT source_nickname, source_id FROM FINDING_SOURCES';

#  return {'CSWG' => 1, 'CRG' => 2, 'PCR' => 3, 'OIG' => 4}; # non-db testing
  return hash_field_one_to_zero($db_hdl, $sql);
}

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

#  return {'CSC-CT' => 101}; # non-db testing
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
