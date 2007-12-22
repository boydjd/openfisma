#! /usr/bin/perl
# ---------------------------------------------------------------------------
# FILE     : nvd.pl
# DESC     : Plugin to translate NVD XML from National Vulnerability Database 
#            XML feed files
#            http://nvd.nist.gov/download/nvdcve.dtd
# INPUTS   : data source to be translated
# OUTPUTS  : data source translated to standard injection format
# DEPENDS  : inject_utils.pm
#            nvd_translation.pm
#             vuln_translator_utils.pm
#             rex_utils.pm
#             rex.pm
#
# AUTHOR   : Chuck Dolan
# DATE     : 10 Jan 2006
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

use lib "$ENV{'OVMS_ROOT'}/lib";

require inject_utils;
require basic_xml_parser;
require nvd_translation;


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
	my @data_out = undef;

	# -----------------------------------------------------------------------
	# IMPORTANT: DO NOT MODIFY SUBROUTINE ABOVE THIS LINE!
	# -----------------------------------------------------------------------
	#
	# Grab subroutine input parameter
	#
        my $raw_data_string = join("\n", @_);

	# TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO
	# TODO: implement translation engine between above and below comments
	# TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO
        my ($std_string_arr_ref, $err_str_arr_ref) = nvd_translation::translate($raw_data_string);

          #
          # Message strings are returned in array refs of the form:
          #  [string, severity]
          # Check for plain strings being passed back instead of
          #  array, print bare strings as warnings.
          #
          foreach my $err_str_ref (@$err_str_arr_ref) {
            if (ref($err_str_ref) eq 'ARRAY') {
              #print "$err_str_ref->[1], $err_str_ref->[0]";
              inject_utils::log_write($err_str_ref->[1], $err_str_ref->[0]);
              }
            else {
              inject_utils::log_write(2, "$err_str_ref (bare string - check reporting code)");
              }
          }

        @data_out = @$std_string_arr_ref;

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
$inject_utils::this_name  = "nvd"; # TODO: put plugin name here (lowercase, no spaces)
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
