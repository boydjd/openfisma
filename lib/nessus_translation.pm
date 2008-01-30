# ---------------------------------------------------------------------------
# Nessus translator module.
#
# Extracts finding data from Nessus scan XML output.
# The closest format spec available is from 2002: nessus_1-3_2002.xsl
#
#
# Nessus report data come in two distinct blocks: the first block lists
# the attack plugins that are applied to the network under test and the
# second block lists any hits detected by each plugin.
#
# The Nessus 'plugin' element is the closest thing to a vulnerability
# offered by the report. Often these map to CVE entries.
#
# Each Nessus 'result' element contains information about the target host
# and a list of ports that have responded to plugin attacks. Each port
# may contain one or more 'information' elements, and it is the information
# element that maps back to a plugin id.
#
# To map Nessus results to vulnerabilities:
#  A list of all plugins is generated, containing data such as CVE id.
#  Each port/information entry is read in, storing info about the port
#   and instance data as well as host information. These are grouped
#   by plugin id.
# These 'port_plugin_results' are then cross-referenced against the 
#  plugin list generated in the first stage to generate vulnerability
#  and finding records for injection into the OVMS database. If solution
#  data and related CVEs can be extracted from the data then these records 
#  are generated as well.
#
#
# 11 Jan 2006
# ---------------------------------------------------------------------------


package nessus_translation;

#use lib $ENV{'OVMS_ROOT'};

use basic_xml_parser;
use vuln_translator_utils;

use strict;

my $INITIALIZE_OBJECT_FIELDS = 0;

# ---------------------------------------------------------------------------
# Sync up return codes with translation utility module
# ---------------------------------------------------------------------------
my $OK    = $vuln_translator_utils::OK;
my $ERROR = $vuln_translator_utils::ERROR;

# ---------------------------------------------------------------------------
# Sync up message codes with translation utility module
# ---------------------------------------------------------------------------
my $SYS_MSG  = $vuln_translator_utils::SYS_MSG;
my $ERR_MSG  = $vuln_translator_utils::ERR_MSG;
my $WARN_MSG = $vuln_translator_utils::WARN_MSG;
my $INFO_MSG = $vuln_translator_utils::INFO_MSG;
my $DBG_MSG  = $vuln_translator_utils::DBG_MSG;

# ---------------------------------------------------------------------------
# Plugin record fields - children/attributes of the Nessus 'plugin' object
# ---------------------------------------------------------------------------
my $PLUGIN_ID      = "id";
my $PLUGIN_NAME    = "name";
my $PLUGIN_VERS    = "version";
my $PLUGIN_FAMILY  = "family";
my $PLUGIN_CVE     = "cve_id";
my $PLUGIN_BUGT    = "bugtraq_id";
my $PLUGIN_CAT     = "category";
my $PLUGIN_RISK    = "risk";
my $PLUGIN_SUMMARY = "summary";
my $PLUGIN_COPYR   = "copyright";


# ---------------------------------------------------------------------------
# Port-plugin-result object fields: these are collected at the 'information'
# element level of the Nessus output. Name strings are script-specific and
# don't match XML element names.
# ---------------------------------------------------------------------------
my $RESULT_HOST_IP  = 'host_ip';
my $RESULT_PORT     = 'port';
#my $RESULT_DATE     = 'date';
my $RESULT_HIT_DATA = 'infodata';



# ---------------------------------------------------------------------------
# Convert raw XML scan data into DB injection records.
#
# Input:
#  $input_xml_string
#   scalar: input XML data string
#
# Return:
#  $translated_nessus_strings_ref
#   array ref: list of database injection records
#   undef if error
#
#  $msg_list_ref
#   array ref: status list of array refs 
#              of the format [status string, severity code]
# 
# ---------------------------------------------------------------------------
sub translate {
  my ($input_xml_string) = @_;

  my $translated_nessus_strings_ref = [];
  my $msg_list_ref = [];

  my $call_status;

  my $this_sub = 'translate';

  eval {
    #
    # Parse input stream into XML subelement list
    #
    my $xml_subelt_list_ref;
    ($xml_subelt_list_ref, $call_status) = basic_xml_parser::parse($input_xml_string);
    die "FATAL ERROR: Unable to parse input XML" if $call_status != $basic_xml_parser::SUCCESS;

    #
    # Get run date value
    #
    my $run_date;
    ($run_date, $call_status) = basic_xml_parser::get_first_named_child_data($xml_subelt_list_ref, 'end');
    if ($call_status != $basic_xml_parser::SUCCESS) {
      #push(@$msg_list_ref, ["$this_sub - unable to collect 'end' element from nessus scan status $call_status", $WARN_MSG]);
      push(@$msg_list_ref, "$this_sub - unable to collect 'end' element from nessus scan status $call_status");
      }
    $run_date = format_date_string($run_date);
    #print "DATE: $run_date\n";

    #
    # Read in list of plugin information
    # Plugin objects are array refs hashed by plugin_id value.
    #
    my $plugin_hash_ref;
    ($plugin_hash_ref, $call_status) = read_plugin_list($xml_subelt_list_ref, $msg_list_ref);
    die "FATAL ERROR: Unable to parse plugin list" if $call_status != $OK;


    #
    # Read in list of 'result' objects, cross-check with plugin info to create
    # list of vulnerability objects.
    # Each vulnerability object in this case is a hash of:
    #  $vuln_obj_ref - sparsely populated, has 'information' data in description
    #  $finding_obj_ref - 'result', 'host' object data
    #  $sol_obj_ref - possibly extracted from 'information' element data
    #  $impact_list_ref
    #  $swprod_list_ref?
    #  $reference_list_ref - generated from 'plugin' cve_id entry/entries
    #
    my $results_by_plugin_ref;
    ($results_by_plugin_ref, $call_status) = read_nessus_results($xml_subelt_list_ref, $plugin_hash_ref, $msg_list_ref);
    die "FATAL ERROR: Unable to parse results list" if $call_status != $OK;


    ($translated_nessus_strings_ref, $call_status) = translate_nessus_results($results_by_plugin_ref, $plugin_hash_ref, $run_date, $msg_list_ref);
    die "FATAL ERROR: Unable to translate result list" if $call_status != $OK;


    };
  if ($@) {
    push(@$msg_list_ref, ["FATAL PROCESSING ERROR: $@", $ERR_MSG]);
    #$translation_status = $ERROR;
    }


  return ($translated_nessus_strings_ref, $msg_list_ref);
}


# ---------------------------------------------------------------------------
# Extract Nessus plugin information into hash of plugin hash objects.
# The return hash uses the plugin 'id' as the key to index each plugin
#  object.
#
#
# Input:
#  $nessus_xml_subelts_ref
#   array ref: the full Nessus report XML element list returned by the
#               xml parser
#
#  $msg_list_ref
#   array ref: list to store any processing error messages
#
# Return:
#  $plugin_hash_ref
#   hash ref: list of plugin hash ref objects keyed by plugin id
#   undef if processing error
#
#  $status
#   scalar: processing status
#   $OK on success
#
# ---------------------------------------------------------------------------
sub read_plugin_list {
  my ($nessus_xml_subelts_ref, $msg_list_ref) = @_;

  my $plugins_hash_ref = {};
  my $call_status;

  #
  # Get list of all 'plugin' elements from the raw XML.
  # Note that this uses the basic_xml_parser's ability to grab grandchild
  #  objects directly. There are two 'plugins' container objects in the
  #  Nessus reports, one containing a list of 'setting' objects and the
  #  other containing the list of 'plugin' objects we're interested in here.
  #  -> If a future parser needs to get a direct parent first then this
  #     logic will need to be revisited.
  #
  my $plugin_ref_list_ref;
  ($plugin_ref_list_ref, $call_status) = basic_xml_parser::children_by_name($nessus_xml_subelts_ref, 'plugin');
  die "FATAL ERROR: Unable to retrieve 'plugin' elements from XML data" if $call_status != $basic_xml_parser::SUCCESS;

  #
  # Convert each XML plugin object into a Perl hash object.
  # Hash object fields are keyed by XML field name.
  #
  #
  # Link attribute names to object keys so the code can just spin
  # through the child object data extractions.
  # Each key in this lookup maps to a vulnerability object hash key.
  # Each value maps to the associated object name in the source XML.
  # In this case the xml field and hash object fields are the same.
  #
  my %plugin_child_lookups = (
    $PLUGIN_NAME    => $PLUGIN_NAME,
    $PLUGIN_VERS    => $PLUGIN_VERS,
    $PLUGIN_FAMILY  => $PLUGIN_FAMILY,
    $PLUGIN_CVE     => $PLUGIN_CVE,
    $PLUGIN_BUGT    => $PLUGIN_BUGT,
    $PLUGIN_CAT     => $PLUGIN_CAT,
    $PLUGIN_RISK    => $PLUGIN_RISK,
    $PLUGIN_SUMMARY => $PLUGIN_SUMMARY,
    );

  foreach my $plugin_xml_ref (@$plugin_ref_list_ref) {
    #
    # Initialize fresh plugin object hash
    #
    my $plugin_obj_ref = {};

    #
    # Get top-level vulnerability entry attributes
    #  run through object/attribute name keys
    #
    if (vuln_translator_utils::map_child_data_to_object($plugin_xml_ref, $plugin_obj_ref, \%plugin_child_lookups, $vuln_translator_utils::ELEMENT_NONEXISTENCE_VALUE, $msg_list_ref) != $OK) {
      push(@$msg_list_ref, ["read_plugin_list - error getting child object data", $WARN_MSG]);
      return (undef, $ERROR);
      }

    #
    # Get Nessus plugin id for hash indexing,
    #  store new object in hash by id.
    #
    my $id_val;
    ($id_val, $call_status) = basic_xml_parser::attribute_by_name($plugin_xml_ref, $PLUGIN_ID);
    if ($call_status != $basic_xml_parser::SUCCESS) {
      push(@$msg_list_ref, ["read_plugin_list - unable to retrieve '$PLUGIN_ID' attribute from 'plugin' element", $WARN_MSG]);
      return (undef, $ERROR);
      }

    if (defined($plugin_obj_ref->{$id_val})) {
      push(@$msg_list_ref, ["read_plugin_list - plugin id key '$id_val' already exists in retrieved plugin object list", $INFO_MSG]);
      }
    #print "$id_val\n";
    $plugins_hash_ref->{$id_val} = $plugin_obj_ref;
    }

  # test
  #foreach my $p_key (sort keys %$plugins_hash_ref) {
  #  print "----$p_key\n";
  #  print_hash_ref($plugins_hash_ref->{$p_key});
  #  }

  return ($plugins_hash_ref, $OK);
}

# ---------------------------------------------------------------------------
# Vulnerability hash object keys.
# These give access to the vulnerability/finding/reference/etc objects
#  held in the vulnerability data hashes returned by read_nessus_results()
# ---------------------------------------------------------------------------
my $VULN_VULNERABILITY = 1;
my $VULN_FINDING       = 2;
my $VULN_REFERENCE     = 3;
my $VULN_SOLUTION      = 4;
my $VULN_PRODUCT       = 5;

# ---------------------------------------------------------------------------
# Read in list of 'result' objects, cross-check with plugin info to create
# list of vulnerability objects.
# Each vulnerability object in this case is a hash of:
#  $vuln_obj_ref - sparsely populated, has 'information' data in description
#  $finding_obj_ref - 'result', 'host' object data
#  $sol_obj_ref - possibly extracted from 'information' element data
#  $impact_list_ref
#  $swprod_list_ref?
#  $reference_list_ref - generated from 'plugin' cve_id entry/entries
#
# Input:
#  $nessus_xml_subelts_ref
#   array ref: the full Nessus report XML element list returned by the
#               xml parser
#
#  $plugin_hash_ref
#   hash ref: hash of plugin objects retrieved by read_plugin_list()
#
#  $msg_list_ref
#   array ref: list to store any processing error messages
#
# Return:
#  $vuln_list_ref
#   array ref: list of vulnerability hash ref objects
#   undef if processing error
#
#  $status
#   scalar: processing status
#   $OK on success
#
# ---------------------------------------------------------------------------



# ---------------------------------------------------------------------------
# Analyze the 'results' section of the Nessus output, return port-level
# 'information' object data. Data is grouped in the hash by plugin id.
# The individual port_plugin_result objects are analyzed using the
# $RESULT_* indices ($RESULT_HOST_IP, $RESULT_HIT_DATA, etc.)
#
# Input:
#  $nessus_xml_subelts_ref
#   array ref: the full Nessus report XML element list returned by the
#               xml parser
#
#  $plugin_hash_ref
#   hash ref: hash of plugin objects retrieved by read_plugin_list()
#
#  $msg_list_ref
#   array ref: list to store any processing error messages
#
# Return:
#  $results_by_plugin_ref
#   hash ref: each element is an array (ref) of port_plugin_result hash
#    objects representing plugin hits at the XML 'information' object level.
#    Hash keys are plugin ids.
#   undef if processing error
#
#  $status
#   scalar: processing status
#   $OK on success
#
# ---------------------------------------------------------------------------
sub read_nessus_results {
  my ($xml_subelt_list_ref, $plugin_hash_ref, $msg_list_ref) = @_;

  my $results_by_plugin_ref = {};
  my $this_sub = 'read_nessus_results';
  my $call_status;

  #
  # Get list of result objects from Nessus result scan
  #
  my $result_ref_list_ref;
  ($result_ref_list_ref, $call_status) = basic_xml_parser::children_by_name($xml_subelt_list_ref, 'result');
  if ($call_status != $basic_xml_parser::SUCCESS) {
    push(@$msg_list_ref, ["$this_sub - unable to collect 'result' elements from nessus scan status $call_status", $ERR_MSG]);
    return(undef, $call_status);
    }

  RESULT:
  foreach my $result_xml_ref (@$result_ref_list_ref) {
    #
    # Get host/ip info
    #
    my $host_elt_ref;
    ($host_elt_ref, $call_status) = basic_xml_parser::first_child_by_name($result_xml_ref, 'host');
    if ($call_status != $basic_xml_parser::SUCCESS) {
      push(@$msg_list_ref, ["$this_sub - error getting 'host' child element", $WARN_MSG]);
      return(undef, $call_status);
      }

    my $ip_val;
    ($ip_val, $call_status) = basic_xml_parser::attribute_by_name($host_elt_ref, 'ip');
    if ($call_status != $basic_xml_parser::SUCCESS) {
      push(@$msg_list_ref, ["$this_sub - unable to retrieve 'source' attribute from 'host' child element", $WARN_MSG]);
      return(undef, $call_status);
      }

    #
    # For each port
    #
    my $port_ref_list_ref;
    ($port_ref_list_ref, $call_status) = basic_xml_parser::children_by_name($result_xml_ref, 'port');
    if ($call_status != $basic_xml_parser::SUCCESS) {
      push(@$msg_list_ref, ["$this_sub - unable to collect 'port' elements from nessus scan status $call_status", $WARN_MSG]);
      return(undef, $call_status);
      }

    PORT:
    foreach my $port_ref (@$port_ref_list_ref) {
      #
      # Get service element info if we need it
      #

      #
      # Get port id
      #
      my $portnum_val = $vuln_translator_utils::UNKNOWN_PORT_ID;
      ($portnum_val, $call_status) = basic_xml_parser::attribute_by_name($port_ref, 'portid');
      if ($call_status != $basic_xml_parser::SUCCESS) {
        my $port_elt = $port_ref->[0];
        push(@$msg_list_ref, ["$this_sub - unable to retrieve 'portid' attribute from 'port' child element >>$port_elt<<", $INFO_MSG]);
        #return(undef, $call_status);
        }
      #print " $ip_val\n";

      #
      # For each information object
      #
      my $info_ref_list_ref;
      ($info_ref_list_ref, $call_status) = basic_xml_parser::children_by_name($port_ref, 'information');
      if ($call_status != $basic_xml_parser::SUCCESS) {
        my $port_elt = $port_ref->[0];
        push(@$msg_list_ref, ["$this_sub - unable to collect 'information' elements from port element $port_elt", $INFO_MSG]);
        #return(undef, $call_status);
        }

      INFO:
      foreach my $info_ref (@$info_ref_list_ref) {
        #
        # Create port_plugin_result object hash
        #  ip, etc
        #
        my $port_plugin_result = {};
        $port_plugin_result->{$RESULT_HOST_IP} = $ip_val;
        $port_plugin_result->{$RESULT_PORT}    = $portnum_val;

        #
        # Get, set 'data' element text data
        #
        my $data_val;
        ($data_val, $call_status) = basic_xml_parser::get_first_named_child_data($info_ref, 'data');
        if ($call_status != $basic_xml_parser::SUCCESS) {
          push(@$msg_list_ref, ["$this_sub - unable to collect 'data' element from nessus scan status $call_status", $WARN_MSG]);
          }
        $port_plugin_result->{$RESULT_HIT_DATA} = format_info_data($data_val);

        #test
        #my $cve_list = extract_CVE_list($data_val);
        #foreach my $cve (@$cve_list) {
        #  print "cve:  $cve\n";
        #  }
        #endtest

        #
        # Get plugin_id, add this new object to output list at
        #  that hash key
        # Initialize storage array at $plugin_id if it doesn't already exist
        #
        my $plugin_id_val;
        ($plugin_id_val, $call_status) = basic_xml_parser::get_first_named_child_data($info_ref, 'id');
        if ($call_status != $basic_xml_parser::SUCCESS) {
          push(@$msg_list_ref, ["$this_sub - unable to collect 'id' element from 'information' element status $call_status", $INFO_MSG]);
          }
        #print "setting key $plugin_id_val\n";

        $results_by_plugin_ref->{$plugin_id_val} = [] if (!defined($results_by_plugin_ref->{$plugin_id_val}));
        my $plugin_results_ref = $results_by_plugin_ref->{$plugin_id_val};
        push(@$plugin_results_ref, $port_plugin_result);
        }
      }
    }

  #test
  #foreach my $plugin_key (keys %$results_by_plugin_ref) {
  #  print "key $plugin_key\n";
  #  my $results_ref = $results_by_plugin_ref->{$plugin_key};
  #  foreach my $result_ref (@$results_ref) {
  #    print_hash_ref($result_ref);
  #    }
  #  }
  #endtest

  return ($results_by_plugin_ref, $OK);
}



# ---------------------------------------------------------------------------
# Cross-reference results at the port level with the full set of plugin info
# to generate OVMS vulnerability database entry records.
#
# Mappings:
#  plugin element -> vulnerability object:
#   cve_id -> cve_name (first cve_id in comma-separated list)
#   name -> desc_primary
#   summary -> desc_secondary
#   risk -> vuln_severity
#  plugin element -> related object:
#   cve_id -> cve (any cve after first listed)
#  host element -> finding object
#   ip -> ip
#  port element -> finding object
#   port_id -> port
#  data element -> solution object:
#   any data between 'Solution:' and 'Risk factors:' -> desc
#  data element -> related object:
#   any detected CVE/CAN strings in the data block -> cve
#
#
# Input:
#  $results_by_plugin_ref
#   hash ref: output from read_nessus_results()
#
#  $plugin_hash_ref
#   hash ref: output from read_plugin_list()
#
#  $run_date
#   string: scan run date
#
#  $msg_list_ref
#   array ref: list to store any processing error messages
#
#
# Return:
#  $translated_nessus_strings_ref
#   array ref: list of database injection records
#   undef if error
#
#  $status
#   scalar: processing status
#   $OK on success
# ---------------------------------------------------------------------------
sub translate_nessus_results {

	my ($results_by_plugin_ref, $plugin_hash_ref, $run_date, $msg_list_ref) = @_;
	my $this_sub = 'translate_nessus_results';
	my $TRANSLATOR_SOURCE_ID = 'nessus_reader';
	my $translated_nessus_strings_ref = [];

	#
	# For each nessus plugin hit detected
	#

	FOUND_PLUGIN:
	foreach my $plugin_id (keys %$results_by_plugin_ref) {

		#
		# Initialize db injection record objects
		#
		my $finding_list_ref   = [];
		my $related_list_ref   = [];
		my $sol_list_ref       = [];
		my $impact_list_ref    = [];
		my $swprod_list_ref    = [];
		my $reference_list_ref = [];

		
		# Get associated plugin record from plugin list		
		my $plugin_record_ref = $plugin_hash_ref->{$plugin_id};

		if(!defined($plugin_record_ref)) {

			push(@$msg_list_ref, ["$this_sub - no defined plugin found for plugin id $plugin_id", $WARN_MSG]);
			next FOUND_PLUGIN;

		}

    #
    # Create (sparse) vulnerability db info object
    #
    my $vuln_obj_ref = {}; # hash reference
    if ($INITIALIZE_OBJECT_FIELDS) {
      vuln_translator_utils::initialize_vulnerability_object_fields($vuln_obj_ref);
      }
    else {
      vuln_translator_utils::record_absent_vulnerability_elt($vuln_obj_ref);
      }

    $vuln_obj_ref->{$vuln_translator_utils::VULN_DT_DISCV} = $run_date;
    $vuln_obj_ref->{$vuln_translator_utils::VULN_DESC_PRIMARY} = $plugin_record_ref->{$PLUGIN_NAME};
    $vuln_obj_ref->{$vuln_translator_utils::VULN_DESC_SECONDARY} = $plugin_record_ref->{$PLUGIN_SUMMARY};
    $vuln_obj_ref->{$vuln_translator_utils::VULN_VULN_SEV} = translate_severity($plugin_record_ref->{$PLUGIN_RISK});

    #
    # Initialize related vulnerability list
    #
    my %related_cves = ();

    #
    # Add any related CVE vulnerabilities found in plugin data
    #  to related vulnerability list
    # note - store in hash as keys to enforce uniqueness
    #
    my $plugin_cve_list_ref = extract_CVE_list($plugin_record_ref->{$PLUGIN_CVE});

    #
    # All Nessus CVEs are considered 'related' - set vulnerability CVE 
    #  to default.
    #
    my $primary_cve = $vuln_translator_utils::DEFAULT_CVE;
    #if(scalar(@$plugin_cve_list_ref) > 0) {
    #  $primary_cve = shift(@$plugin_cve_list_ref);
    #  }
    #else {
    #  push(@$msg_list_ref, ["$this_sub - no primary cve found for plugin id $plugin_id", $INFO_MSG]);
    #  }

    #print "$plugin_id PRIMARY CVE: >>$primary_cve<<\n";
    $vuln_obj_ref->{$vuln_translator_utils::VULN_CVENAME} = $primary_cve;

    foreach my $plugin_cve (@$plugin_cve_list_ref) {
      $related_cves{$plugin_cve} = 1;
      }

    #
    # Set up an array of potential solutions gleaned from result data
    #
    my @solution_strs = ();

    #
    # For each port result associated with the plugin
    #
    my $port_plugin_result_list_ref = $results_by_plugin_ref->{$plugin_id};
    foreach my $port_plugin_result_ref (@$port_plugin_result_list_ref) {
      #
      # Generate a finding db info object
      #
      my $finding_obj_ref = {};
      #vuln_translator_utils::initialize_finding_object_fields($finding_obj_ref);
      vuln_translator_utils::record_absent_finding_elt($finding_obj_ref);
      $finding_obj_ref->{$vuln_translator_utils::FIND_SCANTOOL}  = $TRANSLATOR_SOURCE_ID;
      $finding_obj_ref->{$vuln_translator_utils::FIND_IP}        = $port_plugin_result_ref->{$RESULT_HOST_IP};
      $finding_obj_ref->{$vuln_translator_utils::FIND_PORT}      = $port_plugin_result_ref->{$RESULT_PORT};
      $finding_obj_ref->{$vuln_translator_utils::FIND_DATE}      = $run_date;
      $finding_obj_ref->{$vuln_translator_utils::FIND_INST_DATA} = remove_newlines($port_plugin_result_ref->{$RESULT_HIT_DATA});

# test data
#      my $TEST_SYSTEM  = 'TESTSYSTEMID';
#      my $TEST_NETWORK = 'TESTNETWORKID';
#      $finding_obj_ref->{$vuln_translator_utils::FIND_SYSTEMID}  = $TEST_SYSTEM;
#      $finding_obj_ref->{$vuln_translator_utils::FIND_NETID}     = $TEST_NETWORK;
# end test data

      #
      # Scan data section for any related CVE vulnerabilities
      #  and add them to the related vulnerability list
      #
      my $info_cve_list = extract_CVE_list($port_plugin_result_ref->{$RESULT_HIT_DATA});
      foreach my $info_cve (@$info_cve_list) {
        #print "extractions! $info_cve\n";
        $related_cves{$info_cve} = 1 if ($info_cve ne $primary_cve);
        }

      #
      # See if any solutions can be found in information data
      #
      my $solution_str = extract_solution($port_plugin_result_ref->{$RESULT_HIT_DATA});
      if (length($solution_str) > 0) {
        push(@solution_strs, $solution_str);
        }
#	else { push(@solution_strs, '\n'); }

      #
      # Add this new finding to finding list
      #
      push(@$finding_list_ref, $finding_obj_ref);
      }

    #
    # For each related vulnerability detected
    #
    foreach my $related_cve (keys %related_cves) {
      my $related_obj_ref = {};
      $related_obj_ref->{$vuln_translator_utils::REL_CVE} = $related_cve;
      push(@$related_list_ref, $related_obj_ref);
      }

    #
    # For each solution detected
    #
    foreach my $solution_str (@solution_strs) {
      my $sol_obj_ref = {};
      $sol_obj_ref->{$vuln_translator_utils::SOL_DESC} = $solution_str;
      push(@$sol_list_ref, $sol_obj_ref) if (scalar @solution_strs > 0);
      }

    #
    # Translate vulnerability objects into DB injection records
    #
    my $entry_record_list = vuln_translator_utils::format_translated_entry_list($vuln_obj_ref,
                                                                                $finding_list_ref,
                                                                                $related_list_ref,
                                                                                $sol_list_ref,
                                                                                $impact_list_ref,
                                                                                $swprod_list_ref,
                                                                                $reference_list_ref,
                                                                                $msg_list_ref);
    push(@$translated_nessus_strings_ref, @$entry_record_list);

    #test
    #print "PLUGIN $plugin_id\n";
    #foreach my $cve_key (keys %related_cves) {
    #  print " $cve_key\n";
    #  }
    #endtest

    }

  return ($translated_nessus_strings_ref, $OK);
}

# test util
sub print_hash_ref {
  my ($hash_ref) = @_;
  foreach my $key (sort keys %$hash_ref) {
    print "$key:$hash_ref->{$key}\n";
    }
}


sub format_info_data {
  my ($info_data) = @_;

  $info_data =~ s{^\s+}{}gmx;

  return($info_data);
}

sub extract_CVE_list {
  my ($raw_data_str) = @_;

  my @cve_list = ($raw_data_str =~ m{(CAN-\d\d\d\d-\d\d\d\d|CVE-\d\d\d\d-\d\d\d\d)}gi);

  return(\@cve_list);
}

sub extract_solution {
  my ($raw_data_str) = @_;

  #
  # The 'ms' flags generate the 'clean multiline' mode described in
  # Mastering Regular Expressions Ch. 7 - multiline block mode where
  # the dot operator can match \n
  #
  $raw_data_str =~ m{Solution[\s]*:[\s]*(.*)Risk factor}msi;
  my $found_solution = $1;

  #
  # Take those newlines out (replace with space character)
  #
  #$found_solution =~ s{\n}{ }g;
  $found_solution = remove_newlines($found_solution);

  return($found_solution);
}

sub remove_newlines {
  my ($input_str) = @_;

  my $output_str = $input_str;

  #
  # Take those newlines out (replace with space character)
  #  consider replacing these with a tag that can later be used to format
  #  line breaks back in.
  #
  $output_str =~ s{\n}{ }g;

  return ($output_str);
}

# ---------------------------------------------------------------------------
# Convert 'Thu Dec 15 15:57:01 2005' to '2005-12-15'
# ---------------------------------------------------------------------------
sub format_date_string {
  my ($input_str) = @_;

  my @dt_fields = split(/ /, $input_str);

  my $NUM_EXPECTED_FIELDS = 5;
  die ("format_date_string - date '$input_str' not in expected format (e.g.: 'Thu Dec 15 15:57:01 2005')") if scalar(@dt_fields) != $NUM_EXPECTED_FIELDS;

  my %month_for = (
    'jan' => '01', 'feb' => '02', 'mar' => '03',
    'apr' => '04', 'may' => '05', 'jun' => '06',
    'jul' => '07', 'aug' => '08', 'sep' => '09',
    'oct' => '10', 'nov' => '11', 'dec' => '12',
    );
  my $month = $month_for{lc($dt_fields[1])};
  die ("format_date_string - unrecognized month '$dt_fields[1]' in date '$input_str'") if (!defined($month));

  my $output_str = "$dt_fields[4]-$month-$dt_fields[2]";

  return($output_str);
}


#
# Map severity string to level between 0 and 100.
# Defaults to 50 if no level specified in record.
#
my %severity_for = (
  'High'   => $vuln_translator_utils::HIGH_SEVERITY,
  'Medium' => $vuln_translator_utils::MEDIUM_SEVERITY,
  'Low'    => $vuln_translator_utils::LOW_SEVERITY,
  'None'   => $vuln_translator_utils::NO_SEVERITY,
  );

sub translate_severity {
  my ($severity_str, $msg_list_ref) = @_;
  
  my $severity_val;
  
  #
  # If severity is coming in as a number from 1 to 10
  #
  if ($severity_str =~ m{\d+(\.\d*)?}) {
    #
    # Get it into 0 to 100 scale
    #
    $severity_val = $severity_str * 10;
    }
  else {
    #
    # Otherwise map qualitative string to a number
    #
    if (defined($severity_for{$severity_str})) {
      $severity_val = $severity_for{$severity_str};
      }
    else {
      $severity_val = $vuln_translator_utils::DEFAULT_SEVERITY;
      push(@$msg_list_ref, ["translate_severity - unable to map severity for '$severity_str', setting severity to $severity_val", $WARN_MSG]);
      }
    }
  
  return ($severity_val);
}
