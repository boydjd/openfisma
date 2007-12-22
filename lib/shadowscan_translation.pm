# ---------------------------------------------------------------------------
# Shadow Scan translator module.
#
# This translator operates on XML output from the Shadow Scan application.
# Data files contain a ShadowSecurityScannerXML element block containing
# results grouped by IP address.
# Each IPAddress element contains a General element which provides details
# about the host scanned, followed by results grouped in an Audits element.
# The result element name matches the type of audit scan:
#  Accounts
#  CGIScripts
#  CISCO
#  DNSServices
#  DoS
#  DoSBugs
#  DoSTests
#  FTPServers
#  IPServices
#  IRCServers
#  LDAP
#  Local
#  MailServers
#  Miscellaneous
#  NetBIOS
#  NewsServers
#  Proxy
#  RPCServices
#  Registry
#  RemoteAccess
#  SSHServers
#  ServiceControl
#  WebServers
# (This list was generated from viewing a set of scan results
# and extrapolating from the list found at:
# http://www.safety-lab.com/audits/categorylist.pl?lang=en
# This list is subject to change.)
# 
# Each individual result maps to a CVE number. After all results are 
# collected for all IP addresses, the results are 'pivoted' to group
# IP address result instances (findings) by CVEs/descriptions 
# (vulnerabilities).
#
# The Port field is optional in the XML result object.
#
# The mapping from XML to database injection fields is as follows:
# from General to finding:
#  ReportDate -> finding:date
#  IPAddress -> finding:ip
# from General to vulnerability:
#  ReportDate -> vulnerability:date_discovered
# from result (NetBIOS, etc) to vulnerability:
#  Description -> vulnerability:desc_primary
#  Risklevel -> vulnerability:vuln_severity
#  CVE -> vulnerability:cve_name (distilled from CVE url)
#  Port -> finding:port (optional)
# from result to solution:
#  Howtofix -> solution:desc
# from result to reference:
#  MicrosoftSecurityBulletinMSxxxxx -> reference:url, reference:source
#  CVE -> reference:url (full CVE url), reference:source
#
#
#  
# 19 Jan 2006
# ---------------------------------------------------------------------------


package shadowscan_translation;

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
# Data fields collected in test results
# ---------------------------------------------------------------------------
my $RESULT_NAME        = 1;
my $RESULT_DESCRIPTION = 2;
my $RESULT_RISK        = 3;
my $RESULT_FIX         = 4;
my $RESULT_MS_BULLETIN = 5;
my $RESULT_CVE         = 6;
my $RESULT_IP          = 7;
my $RESULT_PORT        = 8;
my $RESULT_DATE        = 9;


# ---------------------------------------------------------------------------
# Tag marking unspecified CVE reference
# ---------------------------------------------------------------------------
my $UNKNOWN_CVE = 'XXX-0000-0000';


# ---------------------------------------------------------------------------
# Convert raw XML scan data into DB injection records.
#
# Input:
#  $input_xml_string
#   scalar: input XML data string
#
# Return:
#  $translated_scan_strings_ref
#   array ref: list of database injection records
#   undef if error
#
#  $status
#   scalar: processing status
#   $OK on success
# 
# ---------------------------------------------------------------------------
sub translate {
  my ($input_xml_string) = @_;

  my $translated_scan_strings_ref = [];
  my $msg_list_ref = [];

  my $call_status;

  my $this_sub = 'translate';

  eval {
    #
    # Parse input stream into XML subelement list
    #
    my $xml_subelt_list_ref;
    ($xml_subelt_list_ref, $call_status) = basic_xml_parser::parse($input_xml_string);
    die "FATAL ERROR: $this_sub unable to parse input XML" if $call_status != $basic_xml_parser::SUCCESS;

    #
    # Get list of IPAddress result groups
    #
    my $ip_record_list_ref;
    ($ip_record_list_ref, $call_status) = basic_xml_parser::children_by_name($xml_subelt_list_ref, 'IPAddress');
    if ($call_status != $basic_xml_parser::SUCCESS) {
      die "FATAL ERROR: $this_sub problem looking for 'IPAddress' elements" if $call_status != $basic_xml_parser::SUCCESS;
      }

    #
    # Initialize list of all test results
    #
    my $all_results_ref = [];

    #
    # For each IPAddress grouping element
    #
    IP_GROUP:
    foreach my $ipresults_xml_list_ref (@$ip_record_list_ref) {
      #
      # Get run date value
      #
      my $run_date;
      ($run_date, $call_status) = get_report_date($ipresults_xml_list_ref, $msg_list_ref);
      #print "DATE: $run_date\n";

      #
      # Get IPAddress itself
      #
      my $ip_addr;
      ($ip_addr, $call_status) = get_ip_addr($ipresults_xml_list_ref, $msg_list_ref);
      #print "IP: $ip_addr\n";
      
      #
      # Get all result children of 'Audits' element of this IPAddress parent.
      #  The name of each result object indicates the type of test, 
      #  e.g.: NetBIOS, Accounts, Registry, etc.
      #  The element names themselves are wildcarded.
      #
      my $audits_xml_ref;
      ($audits_xml_ref, $call_status) = basic_xml_parser::first_child_by_name($ipresults_xml_list_ref, 'Audits');
      if ($call_status != $basic_xml_parser::SUCCESS) {
        push(@$msg_list_ref, ["$this_sub - error collecting 'Audits' record from IPAddress parent ($call_status)", $WARN_MSG]);
        next IP_GROUP;
        }
        
      my $result_xml_list;
      ($result_xml_list, $call_status) = basic_xml_parser::all_direct_children($audits_xml_ref);
      if ($call_status != $basic_xml_parser::SUCCESS) {
        push(@$msg_list_ref, ["$this_sub - error collecting 'Audits' record children ($call_status)", $WARN_MSG]);
        next IP_GROUP;
        }
      
      #
      # For each wildcard result element
      #
      RESULT_XML:
      foreach my $result_xml_obj (@$result_xml_list) {
        #
        # Get descriptive data for result - 
        #  transform XML into a local result object
        #
        my ($result_obj_ref, $call_status) = read_test_result($result_xml_obj, $msg_list_ref);
        if ($call_status != $OK) {
          my $trouble_elt = (scalar(@$result_xml_obj) > 0) ? $result_xml_obj->[0] : '';
          push(@$msg_list_ref, ["$this_sub - error collecting test result info for element '$trouble_elt' ($call_status)", $WARN_MSG]);
          next RESULT_XML;
          }
        
        #
        # Add IP address and run date to result object
        #
        $result_obj_ref->{$RESULT_IP}   = $ip_addr;
        $result_obj_ref->{$RESULT_DATE} = $run_date;
        
        #
        # Add result object to full (cross-ip address) result list
        #
        push(@$all_results_ref, $result_obj_ref);
        }
      }
    
    #
    # Group results by CVE/vulnerability
    #
    my $findings_hash_ref = pivot_results($all_results_ref, $msg_list_ref);
    
    
    #
    # Generate database injection records for the vulnerabilities
    #
    ($translated_scan_strings_ref, $call_status) = translate_scan_results($findings_hash_ref, $msg_list_ref);
    die "FATAL ERROR: Unable to translate result list" if $call_status != $OK;
    };
  if ($@) {
    push(@$msg_list_ref, ["FATAL PROCESSING ERROR: $@", $ERR_MSG]);
    #die;
    #$translation_status = $ERROR;
    }

  return ($translated_scan_strings_ref, $msg_list_ref);
}
  
  
# ---------------------------------------------------------------------------
# Pull report date from IPAddress XML block.
# Date in form: YYYY-MM-DD.
#
# Input:
#  $ipaddr_xml_list_ref
#   array ref: parsed XML data of IPAddress element
# 
#  $msg_list_ref
#   array ref: list to store any processing error messages
# 
# Return:
#  $formatted_date
#   string: formatted date
#
#  $status
#   scalar: processing status
#   $OK on success
# 
# ---------------------------------------------------------------------------
sub get_report_date {
  my ($ipaddr_xml_list_ref, $msg_list_ref) = @_;
  my $this_sub = 'get_report_date';

  my $report_date = '';
  my $call_status;

  #
  # Get 'General' element
  #
  my $general_xml_ref;
  ($general_xml_ref, $call_status) = basic_xml_parser::first_child_by_name($ipaddr_xml_list_ref, 'General');
  if ($call_status != $basic_xml_parser::SUCCESS) {
    push(@$msg_list_ref, ["$this_sub - error collecting 'General' element from IPAddress parent ($call_status)", $WARN_MSG]);
    return($report_date, $ERROR);
    }
  
  #
  # Get ReportDate attribute
  #
  ($report_date, $call_status) = basic_xml_parser::attribute_by_name($general_xml_ref, 'ReportDate');
  if ($call_status != $basic_xml_parser::SUCCESS) {
    push(@$msg_list_ref, ["$this_sub - unable to retrieve 'ReportDate' attribute from 'General' element ($call_status)", $WARN_MSG]);
    return($report_date, $ERROR);
    }
  
  $report_date = vuln_translator_utils::convert_url_chars($report_date);
  
  my $formatted_date = $vuln_translator_utils::DEFAULT_DATE;
  my $date_len = length('MM-DD-YYYY');
  if (length($report_date) > $date_len) {
    my $date_itself = substr($report_date, 0, $date_len);
    my @date_fields = split(/\//, $date_itself);
    $formatted_date = "$date_fields[2]-$date_fields[0]-$date_fields[1]";
    }
  
  return ($formatted_date, $OK);
}


# ---------------------------------------------------------------------------
# Pull host IP address from IPAddress XML block.
#
# Input:
#  $ipaddr_xml_list_ref
#   array ref: parsed XML data of IPAddress element
# 
#  $msg_list_ref
#   array ref: list to store any processing error messages
# 
# Return:
#  $ip_address
#   string: dotted IP address
#
#  $status
#   scalar: processing status
#   $OK on success
# 
# ---------------------------------------------------------------------------
sub get_ip_addr {
  my ($ipaddr_xml_list_ref, $msg_list_ref) = @_;
  my $this_sub = 'get_ip_addr';

  my $ip_address = '';
  my $call_status;

  #
  # Get 'General' element
  #
  my $general_xml_ref;
  ($general_xml_ref, $call_status) = basic_xml_parser::first_child_by_name($ipaddr_xml_list_ref, 'General');
  if ($call_status != $basic_xml_parser::SUCCESS) {
    push(@$msg_list_ref, ["$this_sub - error collecting 'General' element from IPAddress parent ($call_status)", $WARN_MSG]);
    return($ip_address, $ERROR);
    }
  
  #
  # Get IPAddress attribute
  #
  ($ip_address, $call_status) = basic_xml_parser::attribute_by_name($general_xml_ref, 'IPAddress');
  if ($call_status != $basic_xml_parser::SUCCESS) {
    push(@$msg_list_ref, ["$this_sub - unable to retrieve 'IPAddress' attribute from 'General' element ($call_status)", $WARN_MSG]);
    return($ip_address, $ERROR);
    }
  
  $ip_address = vuln_translator_utils::convert_url_chars($ip_address);
  
  return ($ip_address, $OK);
}

# ---------------------------------------------------------------------------
# Convert test result element into intermediate result object.
# These intermediate objects are indexed using the $RESULT_XXXX indices.
#
# Input:
#  $result_xml_list_ref
#   array ref: parsed XML data of test result element
#              (e.g.: NetBIOS, WebServers)
# 
#  $msg_list_ref
#   array ref: list to store any processing error messages
# 
# Return:
#  $result_obj_ref
#   hash ref: intermediate result object
#
#  $status
#   scalar: processing status
#   $OK on success
# 
# ---------------------------------------------------------------------------
sub read_test_result {
  my ($result_xml_list_ref, $msg_list_ref) = @_;

  my $call_status;

  #
  # Initialize result object
  #
  my $result_obj_ref = {};

	#
	# Get element name to identify test
	#

  my $attribute_hash_ref;
  ($attribute_hash_ref, $call_status) = basic_xml_parser::shallow_attributes($result_xml_list_ref);

  #
  # Get the fields that are easy to pull from the xml attributes
  #  Description
  #  Risklevel
  #  Howtofix
  #  CVE
  #
  my %key_for = (
    'Description' => $RESULT_DESCRIPTION,
    'Risklevel'   => $RESULT_RISK,
    'Howtofix'    => $RESULT_FIX,
    'CVE'         => $RESULT_CVE,
    'Port'        => $RESULT_PORT,
    );
  foreach my $elt_field (keys %key_for) {
	  my $url_coded_val = $attribute_hash_ref->{$elt_field};
	  $result_obj_ref->{$key_for{$elt_field}} = vuln_translator_utils::convert_url_chars($url_coded_val);
    #print "$elt_field:$result_obj_ref->{$key_for{$elt_field}}\n";
    }
  
  #
  # Translate risk level to a value the DB can handle
  #
  $result_obj_ref->{$RESULT_RISK} = translate_severity($result_obj_ref->{$RESULT_RISK});
  
  #
  # Look for MS security bulletins.
  #  Bulletin attribute name has bulletin number appended,
  #  so the code needs to look through all attributes.
  # Field name is similar to: MicrosoftSecurityBulletinMS05048
  #
  foreach my $attr_name (keys %$attribute_hash_ref) {
    if ($attr_name =~ m{MicrosoftSecurityBulletin([^\s=]*)}) {
      #print "bulletin $1\n";
      my $raw_bulletin = $attribute_hash_ref->{$attr_name};
      $result_obj_ref->{$RESULT_MS_BULLETIN} = vuln_translator_utils::convert_url_chars($raw_bulletin);
      }
    }
    
  return ($result_obj_ref, $OK);
}


# ---------------------------------------------------------------------------
# Indices into pivot result value list
# ---------------------------------------------------------------------------
my $PIVOT_RESULT_DATE         = 0;
my $PIVOT_RESULT_NAME         = 1;
my $PIVOT_RESULT_RISK         = 2;
my $PIVOT_RESULT_FIX          = 3;
my $PIVOT_RESULT_REF          = 4;
my $PIVOT_RESULT_MS_REF       = 5;
my $PIVOT_RESULT_FINDING_LIST = 6;


# ---------------------------------------------------------------------------
# 'Pivot' the intermediate result objects, grouping them by 
# general vulnerabilities.
# The grouping keys are concatenated CVE codes and descriptions
#  - there may be numerous entries without known CVEs and this
#    allows separation of those.
# The values of each key are array references indexed by $PIVOT_RESULT_XXXX
#  values. Each array consists of vulnerability-level data (risk, fix, etc.)
#  and a list of associated finding instances (the intermediate results
#  extracted by read_test_result()).
#
# Input:
#  $all_results_ref
#   array ref: list of intermediate result objects
# 
#  $msg_list_ref
#   array ref: list to store any processing error messages
# 
# Return:
#  $results_by_vuln_ref
#   hash ref: list of results keyed by CVE/description
#
# ---------------------------------------------------------------------------
sub pivot_results {
  my ($all_results_ref, $msg_list_ref) = @_;
  my $this_sub = 'pivot_results';
  
  my $results_by_vuln_ref = {};
  
  #
  # Initialize pivoted result hash
  #
  my $pivoted_results = {};
  
  #
  # For each result gleaned from raw scan data
  #
  foreach my $raw_result_obj (@$all_results_ref) {
    #
    # Pull CVE string, date, description
    #
    my $full_cve = $raw_result_obj->{$RESULT_CVE};
    $full_cve =~ m{((CVE|CAN)-\d\d\d\d-\d\d\d\d)};
    my $cve_code = $1;
    if (length($cve_code) < 1) {
      #
      # CVE-MAP-NOMATCH, GENERIC-MAP-NOMATCH are common
      #  Don't bother recording instances.
      #
      #push(@$msg_list_ref, ["$this_sub - unable to retrieve CVE code from result object '$full_cve' ", $WARN_MSG]);
      $cve_code = $UNKNOWN_CVE;
      }
    
    #
    # Use CVE+description as key
    #  This way multiple vulnerabilities with unknown CVEs can be separated. 
    #
    my $hash_key = build_pivot_key($cve_code, $raw_result_obj->{$RESULT_DESCRIPTION});
    
    my $vulnerability_result_ref = $pivoted_results->{$hash_key};
    
    #
    # If this CVE has been seen already on this run
    #
    if (defined($vulnerability_result_ref)) {
      #
      # Add record to CVE's finding list
      #
      my $record_list_ref = $vulnerability_result_ref->[$PIVOT_RESULT_FINDING_LIST];
      push(@$record_list_ref, $raw_result_obj);
      }
    else {
      #
      # Create new CVE entry
      #
      my $record_list_ref = [];
      $record_list_ref->[$PIVOT_RESULT_NAME]   = $raw_result_obj->{$RESULT_NAME};
      $record_list_ref->[$PIVOT_RESULT_DATE]   = $raw_result_obj->{$RESULT_DATE};
      $record_list_ref->[$PIVOT_RESULT_RISK]   = $raw_result_obj->{$RESULT_RISK};
      $record_list_ref->[$PIVOT_RESULT_FIX]    = $raw_result_obj->{$RESULT_FIX};
      $record_list_ref->[$PIVOT_RESULT_REF]    = $raw_result_obj->{$RESULT_CVE};
      $record_list_ref->[$PIVOT_RESULT_MS_REF] = $raw_result_obj->{$RESULT_MS_BULLETIN};
      
      #
      # Add result object to finding list
      #
      my $results = [];
      push(@$results, $raw_result_obj);
      $record_list_ref->[$PIVOT_RESULT_FINDING_LIST] = $results;
      
      #
      # Add entry into hash, keyed by CVE string & description
      #
      $results_by_vuln_ref->{$hash_key} = $record_list_ref;
      }
    }
    
  # test
  #foreach my $hkey (keys %$results_by_vuln_ref) {
  #  print "$hkey\n";
  #  }
  # endtest
  
  return ($results_by_vuln_ref);
}


# ---------------------------------------------------------------------------
# Build result pivot hash key by concatenating CVE and description.
# ---------------------------------------------------------------------------
sub build_pivot_key {
  my ($cve_code, $desc) = @_;
  
  my $key = "$cve_code$desc";
  
  return ($key);
}


# ---------------------------------------------------------------------------
# Separate CVE and description from pivot hash key.
# ---------------------------------------------------------------------------
sub extract_pivot_key_data {
  my ($key) = @_;
  
  my $CVE_CODE_LEN = length('CVE-9999-9999');

  my $cve_code = substr($key, 0, $CVE_CODE_LEN);
  my $desc     = substr($key, $CVE_CODE_LEN);

 # print "$cve_code:::$desc\n"; die;
  
  return ($cve_code, $desc);
}


# ---------------------------------------------------------------------------
# Convert pivoted results list into database injection records.
# 
# Input:
#  $findings_hash_ref
#   hash ref: results hashed by CVE/description 
# 
#  $msg_list_ref
#   array ref: list to store any processing error messages
# 
# Result:
#  $records_list_ref
#   array ref: list of database injection records
# 
#  $status
#   scalar: processing status
#   $OK on success
# 
# ---------------------------------------------------------------------------
sub translate_scan_results {
  my ($findings_hash_ref, $msg_list_ref) = @_;
  
  my $records_list_ref = [];
  
  #
  # For each detected vulnerability
  #
  PIVOT_KEY:
  foreach my $vuln_key (keys %$findings_hash_ref) {
    my $finding_list_ref   = [];
    my $related_list_ref   = [];
    my $sol_list_ref       = [];
    my $impact_list_ref    = [];
    my $swprod_list_ref    = [];
    my $reference_list_ref = [];

    #
    # Retrieve the details for the given vulnerability
    #
    my $detected_vuln_obj = $findings_hash_ref->{$vuln_key};

    # 
    # Break key into cve, description
    #
    my ($cve, $description) = extract_pivot_key_data($vuln_key);
  
    #
    # Generate vulnerability record
    #
    my $vuln_obj_ref = {}; # hash reference

    #
    # Set fields extracted from hash key
    #
    $vuln_obj_ref->{$vuln_translator_utils::VULN_CVENAME}      = $vuln_translator_utils::DEFAULT_CVE;
    $vuln_obj_ref->{$vuln_translator_utils::VULN_DESC_PRIMARY} = $description;

    #
    # Set fields extracted from pivoted vulnerability data object
    #
    $vuln_obj_ref->{$vuln_translator_utils::VULN_DT_DISCV} = $detected_vuln_obj->[$PIVOT_RESULT_DATE];
    $vuln_obj_ref->{$vuln_translator_utils::VULN_VULN_SEV} = $detected_vuln_obj->[$PIVOT_RESULT_RISK];
    
    #
    # Use cve value to create a 'related' record if we have a good CVE
    #  reference value
    #
    if($cve =~ m{((CVE|CAN)-\d\d\d\d-\d\d\d\d)}) {
      my $rel_obj_ref = {};
      $rel_obj_ref->{$vuln_translator_utils::REL_CVE} = $cve;
      push(@$related_list_ref, $rel_obj_ref);
      }
      
    #
    # Generate solution
    #
    my $fix_str = $detected_vuln_obj->[$PIVOT_RESULT_FIX];
    if(length($fix_str) > 0) {
      my $sol_obj_ref = {};
      $sol_obj_ref->{$vuln_translator_utils::SOL_DESC} = $fix_str;
      push(@$sol_list_ref, $sol_obj_ref);
      }
    
    #
    # Generate reference(s)
    #
    my $ref_str = $detected_vuln_obj->[$PIVOT_RESULT_REF];
    if(length($ref_str) > 0) {
      my $ref_obj_ref = {};
      $ref_obj_ref->{$vuln_translator_utils::REF_URL} = $ref_str;
      $ref_obj_ref->{$vuln_translator_utils::REF_SOURCE} = determine_authority($ref_str);
      push(@$reference_list_ref, $ref_obj_ref);
      }
    
    my $ms_ref_str = $detected_vuln_obj->[$PIVOT_RESULT_MS_REF];
    if(length($ms_ref_str) > 0) {
      my $ref_obj_ref = {};
      $ref_obj_ref->{$vuln_translator_utils::REF_URL} = $ms_ref_str;
      $ref_obj_ref->{$vuln_translator_utils::REF_SOURCE} = determine_authority($ms_ref_str);
      push(@$reference_list_ref, $ref_obj_ref);
      }
    
    
    #
    # product???
    #
    
    #
    # For each individual finding
    #
    my $result_list_ref = $detected_vuln_obj->[$PIVOT_RESULT_FINDING_LIST];
    foreach my $raw_result_obj (@$result_list_ref) {
      #
      # Create finding
      #
      my $finding_obj_ref = {};
      $finding_obj_ref->{$vuln_translator_utils::FIND_IP}   = $raw_result_obj->{$RESULT_IP};
      $finding_obj_ref->{$vuln_translator_utils::FIND_DATE} = $raw_result_obj->{$RESULT_DATE};
      #
      # Track port id if it's available
      #
      if (defined($raw_result_obj->{$RESULT_PORT})) {
        $finding_obj_ref->{$vuln_translator_utils::FIND_PORT} = $raw_result_obj->{$RESULT_PORT};
        }
      else {
        $finding_obj_ref->{$vuln_translator_utils::FIND_PORT} = $vuln_translator_utils::UNKNOWN_PORT_ID;
        }

      push(@$finding_list_ref, $finding_obj_ref);
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
    push(@$records_list_ref, @$entry_record_list);
    }
  
  # test
  #foreach my $record_str (@$records_list_ref) {
  #  print "$record_str\n";
  #  }
  # endtest
  
  return ($records_list_ref, $OK);
}


# ---------------------------------------------------------------------------
# Attempt to determine source of reference data.
#
# Input:
#  $reference_str
#   string: full reference
#
# Return:
#  string: authority name from vuln_translator_utils if available
#          blank string otherwise
# ---------------------------------------------------------------------------
sub determine_authority {
  my ($reference_str) = @_;
  
  my $authority = '';

  #
  # Map raw data tags to recognized source authorities
  #
  my %authority_for = (
    'mitre' => $vuln_translator_utils::REF_SOURCE_MITRE,
    'microsoft' => $vuln_translator_utils::REF_SOURCE_MS,
    );
    
  #
  # Check reference string for each raw data tag indicator.
  # Return first match found.
  #
  foreach my $data_tag (keys (%authority_for)) {
    #print "$data_tag\n";
    if ($reference_str =~ m{$data_tag}i) {
      $authority = $authority_for{$data_tag};
      last;
      }
    }

  return ($authority);
}


#
# Map severity string to level between 0 and 100.
# Defaults to 50 if no level specified in record.
#
my %severity_for = (
  'High'        => $vuln_translator_utils::HIGH_SEVERITY,
  'Medium'      => $vuln_translator_utils::MEDIUM_SEVERITY,
  'Low'         => $vuln_translator_utils::LOW_SEVERITY,
  'Information' => $vuln_translator_utils::NO_SEVERITY,
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
