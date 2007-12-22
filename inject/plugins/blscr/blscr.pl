#! /usr/bin/perl -w
# -------------------------------------------------------------------
# FILE     : blscr.pl
# DESC     : Baseline Security Requirement translator
# INPUTS   : Tab-delimited data exported from
#            Minimum_Security_Controls xml spreadsheet.
# OUTPUTS  : blscr.log - all actions are logged here
#            MySQL OVMS db table BASELINE_SECURITY_REQUIREMENTS
# DEPENDS  : this script requires the MySQL DBI to perform the inject
#
# AUTHOR   : Chuck Dolan
# DATE     : 25 Jan 2006
#
# COMMENTS :
#
#
# The BASELINE_SECURITY_REQUIREMENTS table is defined as:
#   blscr_number       text 
#   blscr_class        text
#   blscr_subclass     text 
#   blscr_family       text       
#   blscr_control      text
#   blscr_guidance     text
#   blscr_low          bool            
#   blscr_medium       bool            
#   blscr_high         bool            
#   blscr_enhancements text
#   blscr_supplement   text
# 
# The raw data comes from excel in this order:
#   blscr_class        text
#   blscr_family       text       
#   blscr_number       text 
#   blscr_subclass     text 
#   blscr_control      text
#   blscr_guidance     text
#   blscr_low          bool            [input record index 6]
#   blscr_medium       bool            [input record index 7] 
#   blscr_high         bool            [input record index 8]
#   blscr_enhancements text
#   blscr_supplement   text
#
#
#  IMPORTANT - make sure all newlines are removed from incoming
#  data fields (Supplement field in particular)
# 
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
  my $SEPARATOR = "\t";
  my $BLANK_FIELD = ".";

	# handle the data line by line
	foreach (@data_in) {
	
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
    # Clean any leading and trailing quotation marks introduced by
    # Excel export.
    #
    for (my $i = 0; $i < scalar(@fields); $i++) {
      my $field = $fields[$i];
      $field =~ s{^"}{};
      $field =~ s{"$}{};
      $field = $BLANK_FIELD if length($field) == 0;
      $fields[$i] = $field;
      #print "$i - $fields[$i]\n";
      }
		
		#
		# For low/medium/high boolean fields,
		#  convert 'X' to boolean 1, absence of 'X' to boolean 0.
		# Fields of interest are at indices 6, 7 and 8.
		#
		my @BOOLEAN_INDICES = (6, 7, 8);
		foreach my $idx (@BOOLEAN_INDICES) {
	    my $bool_field = $fields[$idx];
	    if ((length($bool_field) == 0) or ($BLANK_FIELD eq $bool_field)) {
	      $bool_field = 0;
	      }
	    $bool_field =~ s{X}{1};
	    $fields[$idx] = $bool_field;
		  }
		
		
		#
		# Declare record header 
		#
    my $RECORD_TAG = "blscr";
    
    #
    # Format record fields into injection record line
    #
    push (@data_out, inject_utils::gen_std($RECORD_TAG, @fields));
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
$inject_utils::this_name  = "blscr";
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
