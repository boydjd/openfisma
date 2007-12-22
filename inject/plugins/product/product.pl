#! /usr/bin/perl -w
# -------------------------------------------------------------------
# FILE     : products.pl
# DESC     : input data injection script
# INPUTS   : csv file nvd_dictionary piped in
# OUTPUTS  : products.log - all actions are logged here
#            MySQL OVMS db table PRODUCTS
# DEPENDS  : this script requires the MySQL DBI to perform the inject
#
# AUTHOR   : Brian Gant
# DATE     : 12.20.05
#
# COMMENTS :
#
# The nvd_dictionary.txt file has the following csv format:
#
# vendor name, product name, version
#
#
# The PRODUCTS table is defined as:
#   prod_id           integer, nn, ai, pk
#   prod_nvd_defined  bool, nn
#   prod_meta         text
#   prod_vendor       text, nn
#   prod_name         text, nn
#   prod_version      text, nn
#   prod_desc         text
# 
# prod_nvd_defined is set true for any addition made from this script
# prod_vendor, prod_name, prod_version are pulled from csv records
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

require $ENV{'OVMS_ROOT'}."/lib/inject_utils.pm";


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

	# handle the data line by line
	foreach (@data_in) {

    # pull off any line-ending character
    chomp;

		# sanitize the single quotes
		$_ =~ tr/\'/,/;

		# split out the CSV
		my @line = split(',', $_);

		# sanitize lines that have no version number (. == NULL)
		if (@line == 2) { push (@line, '.'); }

		# translate the sanitized line and add to the output array
		if (@line == 3) {

			# create the prod_meta field and create the standard line
			my $meta = $line[0]." ".$line[1];
			if ($line[2] ne '.') { $meta .= ' '.$line[2]; }
			
			# add it to the mix
			push (@data_out, inject_utils::gen_std("product","1",$meta,@line,"."));

		}

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
$inject_utils::this_name  = "product";
$inject_utils::this_sub   = "main()";
$inject_utils::this_debug = 1;

# grab the configuration and mark the start of logging session
#inject_utils::configure();
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
unshift (@data, "plugin".$inject_utils::splitter.$inject_utils::this_name);

# write the data to the output pipe
inject_utils::pipe_write(@data);

# ---------------------------------------------------------------------------
# PLUGIN CLEANUP
# ---------------------------------------------------------------------------

# mark the stop of logging
inject_utils::log_stop();
