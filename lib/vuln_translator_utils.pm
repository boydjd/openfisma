# ---------------------------------------------------------------------------
# Vulnerability translation helper utilities.
#
# This module gathers functions common to the various source-to-vulnerability
# translators.
#
#
# Output data record format:
#
# vulnerability|cve_name|desc_primary|desc_secondary|
# 	date_discovered|date_published|date_modified|vuln_severity|
# 	loss_availability|loss_confidentiality|loss_integrity|
# 	loss_security_admin|loss_security_user|loss_security_other|
# 	type_access|type_input|type_input_bound|type_input_buffer|
# 	type_design|type_exception|type_environment|type_config|
# 	type_race|type_other|range_local|range_remote|range_user
#
# finding|source_id|scan_tool|system_id|network_id|ip|port|date|instance_data
#
# related|cve-nnnn-nnnn
#
# reference|name|source|url|is_advisory|has_tool_sig|has_patch
#
# solution|desc|source
#
# impact|desc|source
#
# product|nvd_created|prod_meta|vendor|name|version|description
# ---------------------------------------------------------------------------

package vuln_translator_utils;

use basic_xml_parser;

use strict;


#
# Processing status codes
#
our $OK    = 1;
our $ERROR = 2;


#
# Value to set output fields when only the existence of an empty XML element
# is to be recorded.
#
our $ELEMENT_EXISTENCE_VALUE    = '1';
our $ELEMENT_NONEXISTENCE_VALUE = '0';

#
# Value to use for element text data when element does not appear in XML input
#
our $NONEXISTENT_ELEMENT_TEXT_VALUE = '0';


#
# Value to use when port cannot be resolved
#
our $UNKNOWN_PORT_ID = 0;


#
# Default date
#
our $DEFAULT_DATE = '0000-00-00';


#
# Default CVE/CAN field
#
our $DEFAULT_CVE = '';


#
# Severity value mappings (out of 100)
#
our $NO_SEVERITY      =  0;
our $LOW_SEVERITY     = 20;
our $MEDIUM_SEVERITY  = 55;
our $HIGH_SEVERITY    = 85;
our $DEFAULT_SEVERITY = 50;


#
# Mapping of log levels for error/status reporting
#
our $SYS_MSG  = 0;
our $ERR_MSG  = 1;
our $WARN_MSG = 2;
our $INFO_MSG = 3;
our $DBG_MSG  = 4;

# ---------------------------------------------------------------------------
# BEGIN VULNERABILITY RECORD FIELDS AND INITIALIZATION CODE.
#
# The code in this section sets up the vulnerability record hash object
# keys and initializes new record objects.
#
# Hash object keys are unique integers.
#
# The *_PRINT_ELEMENTS arrays solidify the order in which hash fields 
#  will be written to output.
#
# The initialize_*_object_fields calls set the initial values of each
#  hash key to an alert string that can be used to warn the developer
#  of record object fields not handled by the translator code.
#
# The record_absent_*_elt calls set the specified fields to
#  $NONEXISTENT_ELEMENT_TEXT_VALUE where record object fields may not be
#  present in the data schema.
#
# ---------------------------------------------------------------------------

#
# Vulnerability data object fields.
#
our $VULN_CVENAME          = 0;
our $VULN_DT_DISCV         = 1;
our $VULN_DT_PUB           = 2;
our $VULN_DT_MOD           = 3;
our $VULN_VULN_SEV         = 4;
our $VULN_DESC_PRIMARY     = 5;
our $VULN_DESC_SECONDARY   = 6;
#our $VULN_DESC_VVD         = 7;
our $VULN_LOSS_AVAIL       = 8;
our $VULN_LOSS_CONFID      = 9;
our $VULN_LOSS_INTEG       = 10;
our $VULN_LOSS_SEC_ADM     = 11;
our $VULN_LOSS_SEC_USER    = 12;
our $VULN_LOSS_SEC_OTHER   = 13;
our $VULN_TYPE_ACCESS      = 14;
our $VULN_TYPE_INPUT       = 15;
our $VULN_TYPE_INPUT_BOUND = 16;
our $VULN_TYPE_INPUT_BUFF  = 17;
our $VULN_TYPE_DESIGN      = 18;
our $VULN_TYPE_EXCEPT      = 19;
our $VULN_TYPE_ENV         = 20;
our $VULN_TYPE_CONFIG      = 21;
our $VULN_TYPE_RACE        = 22;
our $VULN_TYPE_OTHER       = 23;
our $VULN_RANGE_LOCAL      = 24;
our $VULN_RANGE_REMOTE     = 25;
our $VULN_RANGE_USER       = 26;

#
# Data elements will be printed out in this order at the end of
# entry analysis.
#
my @VULN_PRINT_ELEMENTS = (
  $VULN_CVENAME,
  $VULN_DESC_PRIMARY,
  $VULN_DESC_SECONDARY,
  $VULN_DT_DISCV,
  $VULN_DT_PUB,
  $VULN_DT_MOD,
  $VULN_VULN_SEV,
  $VULN_LOSS_AVAIL,
  $VULN_LOSS_CONFID,
  $VULN_LOSS_INTEG,
  $VULN_LOSS_SEC_ADM,
  $VULN_LOSS_SEC_USER,
  $VULN_LOSS_SEC_OTHER,
  $VULN_TYPE_ACCESS,
  $VULN_TYPE_INPUT,
  $VULN_TYPE_INPUT_BOUND,
  $VULN_TYPE_INPUT_BUFF,
  $VULN_TYPE_DESIGN,
  $VULN_TYPE_EXCEPT,
  $VULN_TYPE_ENV,
  $VULN_TYPE_CONFIG,
  $VULN_TYPE_RACE,
  $VULN_TYPE_OTHER,
  $VULN_RANGE_LOCAL,
  $VULN_RANGE_REMOTE,
  $VULN_RANGE_USER,
  );

#
# For testing - initialize fields to recognizable default data
#  to detect if there are gaps in the translation.
#
sub initialize_vulnerability_object_fields {
  my ($obj_ref) = @_;

  foreach my $key (@VULN_PRINT_ELEMENTS) {
    $obj_ref->{$key} = sprintf("######vuln:%02d######", $key);
    }
}



#
# Software product data fields
#
our $SWPROD_NVD_CREATED = 0;
our $SWPROD_META        = 1;
our $SWPROD_VENDOR      = 2;
our $SWPROD_NAME        = 3;
our $SWPROD_VERSION     = 4;
our $SWPROD_DESC        = 5;

#
# Software product elements will be printed out in this order at the end of
# entry analysis.
#
my @SWPROD_PRINT_ELEMENTS = (
  $SWPROD_NVD_CREATED,
  $SWPROD_META,
  $SWPROD_VENDOR,
  $SWPROD_NAME,
  $SWPROD_VERSION,
  $SWPROD_DESC,
  );

#
# For testing - initialize fields to recognizable default data
#  to detect if there are gaps in the translation.
#
sub initialize_swprod_object_fields {
  my ($obj_ref) = @_;

  foreach my $key (@SWPROD_PRINT_ELEMENTS) {
    $obj_ref->{$key} = sprintf("######swprod:%02d######", $key);
    }
}

#
# Finding data fields
#
our $FIND_SOURCEID  = 0;
our $FIND_SCANTOOL  = 1;
our $FIND_SYSTEMID  = 2;
our $FIND_NETID     = 3;
our $FIND_IP        = 4;
our $FIND_PORT      = 5;
our $FIND_DATE      = 6;
our $FIND_INST_DATA = 7;

#
# Finding elements will be printed out in this order at the end of
# entry analysis.
#
my @FINDING_PRINT_ELEMENTS = (
#  $FIND_SOURCEID,  # pulled 19 Jan 2006
#  $FIND_SCANTOOL,  # pulled 19 Jan 2006
#  $FIND_SYSTEMID,  # pulled 19 Jan 2006
#  $FIND_NETID,     # pulled 19 Jan 2006
  $FIND_IP,
  $FIND_PORT,
  $FIND_DATE,
  $FIND_INST_DATA,
  );

#
# For testing - initialize fields to recognizable default data
#  to detect if there are gaps in the translation.
#
sub initialize_finding_object_fields {
  my ($obj_ref) = @_;

  foreach my $key (@FINDING_PRINT_ELEMENTS) {
    $obj_ref->{$key} = sprintf("######finding:%02d######", $key);
    }
}

#
# Related data fields
#
our $REL_CVE = 0;

#
# Related elements will be printed out in this order at the end of
# entry analysis.
#
my @REL_PRINT_ELEMENTS = (
  $REL_CVE,
  );

#
# For testing - initialize fields to recognizable default data
#  to detect if there are gaps in the translation.
#
sub initialize_rel_object_fields {
  my ($obj_ref) = @_;

  foreach my $key (@REL_PRINT_ELEMENTS) {
    $obj_ref->{$key} = sprintf("######rel:%02d######", $key);
    }
}

#
# Solution data fields
#
our $SOL_DESC   = 0;
our $SOL_SOURCE = 1; # The source is the authority behind the data e.g.: Mitre

#
# Solution elements will be printed out in this order at the end of
# entry analysis.
#
my @SOL_PRINT_ELEMENTS = (
  $SOL_DESC,
  $SOL_SOURCE,
  );

#
# For testing - initialize fields to recognizable default data
#  to detect if there are gaps in the translation.
#
sub initialize_sol_object_fields {
  my ($obj_ref) = @_;

  foreach my $key (@SOL_PRINT_ELEMENTS) {
    $obj_ref->{$key} = sprintf("######sol:%02d######", $key);
    }
}

#
# Impact data fields
#
our $IMPACT_DESC   = 0;
our $IMPACT_SOURCE = 1; # The source is the authority behind the data e.g.: Mitre

#
# Impact elements will be printed out in this order at the end of
# entry analysis.
#
my @IMPACT_PRINT_ELEMENTS = (
  $IMPACT_DESC,
  $IMPACT_SOURCE,
  );

#
# For testing - initialize fields to recognizable default data
#  to detect if there are gaps in the translation.
#
sub initialize_impact_object_fields {
  my ($obj_ref) = @_;

  foreach my $key (@IMPACT_PRINT_ELEMENTS) {
    $obj_ref->{$key} = sprintf("######impact:%02d######", $key);
    }
}


#
# Reference source authorities
#
our $REF_SOURCE_MITRE = "Mitre";
our $REF_SOURCE_MS    = "Microsoft";


#
# Reference element data fields
#
our $REF_NAME      = 0;
our $REF_SOURCE    = 1; # The source is the authority behind the data e.g.: Mitre
our $REF_URL       = 2;
our $REF_IS_ADV    = 3;
our $REF_HAS_SIG   = 4;
our $REF_HAS_PATCH = 5;

#
# Reference elements will be printed out in this order at the end of
# entry analysis.
#
my @REF_PRINT_ELEMENTS = (
  $REF_NAME,
  $REF_SOURCE,
  $REF_URL,
  $REF_IS_ADV,
  $REF_HAS_SIG,
  $REF_HAS_PATCH,
  );

#
# For testing - initialize fields to recognizable default data
#  to detect if there are gaps in the translation.
#
sub initialize_ref_object_fields {
  my ($obj_ref) = @_;

  foreach my $key (@REF_PRINT_ELEMENTS) {
    $obj_ref->{$key} = sprintf("######ref:%02d######", $key);
    }
}

sub record_absent_vulnerability_elt {
  my ($vuln_obj_ref) = @_;

  #
  # Initialize all print fields to empty string
  #
  foreach my $field (@VULN_PRINT_ELEMENTS) {
    $vuln_obj_ref->{$field} = $NONEXISTENT_ELEMENT_TEXT_VALUE;
    }
}

sub record_absent_finding_elt {
  my ($finding_obj_ref) = @_;

  #
  # Initialize all print fields to empty string
  #
  foreach my $field (@FINDING_PRINT_ELEMENTS) {
    $finding_obj_ref->{$field} = $NONEXISTENT_ELEMENT_TEXT_VALUE;
    }
}

sub record_absent_loss_elt {
  my ($vuln_obj_ref) = @_;

  my @vuln_fields = (
    $VULN_LOSS_AVAIL,
    $VULN_LOSS_CONFID,
    $VULN_LOSS_INTEG,
    $VULN_LOSS_SEC_ADM,
    $VULN_LOSS_SEC_USER,
    $VULN_LOSS_SEC_OTHER,
    );

  foreach my $field (@vuln_fields) {
    $vuln_obj_ref->{$field} = $ELEMENT_NONEXISTENCE_VALUE;
    }
}


sub record_absent_type_elt {
  my ($vuln_obj_ref) = @_;

  my @vuln_type_fields = (
    $VULN_TYPE_INPUT,
    $VULN_TYPE_INPUT_BOUND,
    $VULN_TYPE_INPUT_BUFF,
    $VULN_TYPE_ACCESS,
    $VULN_TYPE_DESIGN,
    $VULN_TYPE_EXCEPT,
    $VULN_TYPE_ENV,
    $VULN_TYPE_CONFIG,
    $VULN_TYPE_RACE,
    $VULN_TYPE_OTHER,
    );

  foreach my $field (@vuln_type_fields) {
    $vuln_obj_ref->{$field} = $ELEMENT_NONEXISTENCE_VALUE;
    }
}



sub record_absent_range_elt {
  my ($vuln_obj_ref) = @_;

  my @vuln_fields = (
    $VULN_RANGE_LOCAL,
    $VULN_RANGE_REMOTE,
    $VULN_RANGE_USER,
    );

  foreach my $field (@vuln_fields) {
    $vuln_obj_ref->{$field} = $ELEMENT_NONEXISTENCE_VALUE;
    }
}

# ---------------------------------------------------------------------------
# END VULNERABILITY RECORD FIELDS AND INITIALIZATION CODE.
# ---------------------------------------------------------------------------




# ---------------------------------------------------------------------------
# Search an XML element for a named child element. Return child element
#  (if it exists) and status code.
#
# Caller must check the returned element for existence - the call only
#  returns an error if there was a problem processing the XML.
#
# Input:
#  $parent_elt_ref
#   array ref: the XML element data string list
#
#  $child_elt_name
#   string: name of element to retrieve
#
#  $error_list_ref
#   array ref: processing error strings
#
#
# Return:
#  ($child_elt_ref, $OK) if child found
#  (undef, $OK) if child not found
#  (undef, status code) if error in REX operation
#
# ---------------------------------------------------------------------------
sub get_child_element {
  my ($parent_elt_ref, $child_elt_name, $error_list_ref) = @_;

  my ($child_elt_ref, $rex_status) = basic_xml_parser::first_child_by_name($parent_elt_ref, $child_elt_name);
  if ($rex_status != $basic_xml_parser::SUCCESS &&
      $rex_status != $basic_xml_parser::NO_MATCH) {
    push(@$error_list_ref, "get_child_element - error getting '$child_elt_name' child element");
    return (undef, $ERROR);
    }

  #
  # If analysis ok but no child elt found, undefine the return element.
  #
  if ($rex_status != $basic_xml_parser::SUCCESS) {
    $child_elt_ref = undef;
    }

  return($child_elt_ref, $OK);
}


# ---------------------------------------------------------------------------
# Retrieve attributes of interest from XML element.
# On existence of attribute, apply attribute value to associated
#  field in target hash object.
#
# Target hash object keys are mapped to XML element attribute names.
#
#
# Input:
#  $source_xml_ref
#   array ref: xml subelement strings from basic_xml_parser::parse
#
#  $destination_obj_ref
#   hash ref: the object to print at the end of xml extraction
#             xml attribute values are written to destination hash key entries
#
#  $attr_lookups_ref
#   hash ref: destination_obj key => source xml attr name
#
#  $nonexistence_value
#   string: value to set if attribute does not exist
#
#  $error_list_ref
#   array ref: processing error strings
#
#
# Return:
#  $OK on success
#  $ERROR on failure
#
# ---------------------------------------------------------------------------
sub map_attrs_to_object {
  my ($source_xml_ref, $destination_obj_ref, $attr_lookups_ref, $nonexistence_value, $error_list_ref) = @_;

  #
  # Get top-level entry attributes
  #  run through object/attribute name keys
  #
  foreach my $dest_key (keys(%$attr_lookups_ref)) {
    my ($attr_val, $rex_status) = basic_xml_parser::attribute_by_name($source_xml_ref, $attr_lookups_ref->{$dest_key});
    if ($rex_status != $basic_xml_parser::SUCCESS &&
        $rex_status != $basic_xml_parser::NO_MATCH) {
      push(@$error_list_ref, "map_attrs_to_object - error getting attribute $attr_lookups_ref->{$dest_key}");
      return $ERROR;
      }

    #
    # Set attribute value in vulnerability object if it appears in the element,
    #  otherwise record the nonexistence value
    #
    $destination_obj_ref->{$dest_key} = ($rex_status == $basic_xml_parser::SUCCESS) ? $attr_val : $nonexistence_value;
    }

  return $OK;
}

# ---------------------------------------------------------------------------
# Retrieve child elements of interest from parent XML element.
# On existence of child,
#  set associated destination hash key to child text data.
#
# Target hash object keys are mapped to XML child element names.
#
#
# Input:
#  $source_xml_ref
#   array ref: xml subelement strings from basic_xml_parser::parse
#
#  $destination_obj_ref
#   hash ref: the object to print at the end of xml extraction
#             xml attribute values are written to destination hash key entries
#
#  $child_elt_lookups_ref
#   hash ref: destination_obj key => source xml child element name
#
#  $nonexistence_value
#   string: value to set if data is blank
#
#  $error_list_ref
#   array ref: processing error strings
#
#
# Return:
#  $OK on success
#  $ERROR on failure
#
# ---------------------------------------------------------------------------
sub map_child_data_to_object {
  my ($source_xml_ref, $destination_obj_ref, $child_elt_lookups_ref, $nonexistence_value, $error_list_ref) = @_;

  #
  # Get child element data
  #  run through object/attribute name keys
  #
  foreach my $dest_key (keys(%$child_elt_lookups_ref)) {
    my ($child_elt_ref, $rex_status) = basic_xml_parser::first_child_by_name($source_xml_ref, $child_elt_lookups_ref->{$dest_key});
    if ($rex_status != $basic_xml_parser::SUCCESS &&
        $rex_status != $basic_xml_parser::NO_MATCH) {
      push(@$error_list_ref, "map_child_data_to_object - error getting child $child_elt_lookups_ref->{$dest_key}");
      return $ERROR;
      }

    my ($child_text_data, $rex_data_status) = basic_xml_parser::deep_data($child_elt_ref);

    #
    # Set field value in vulnerability object
    #
    $destination_obj_ref->{$dest_key} = (length($child_text_data) > 0) ? $child_text_data : $nonexistence_value;
    }

  return $OK;
}


# ---------------------------------------------------------------------------
# Retrieve child elements of interest from parent XML element.
# On existence of child,
#  set associated target hash object field to $true_tag
# On non-existence, set field to $false_tag.
#
# Target hash object keys are mapped to XML child element names.
#
#
# Input:
#  $source_xml_ref
#   array ref: xml subelement strings from basic_xml_parser::parse
#
#  $destination_obj_ref
#   hash ref: the object to print at the end of xml extraction
#             xml attribute values are written to destination hash key entries
#
#  $child_elt_lookups_ref
#   hash ref: destination_obj key => source xml child element name
#
#  $true_tag
#   scalar: value to set destination object hash field if child exists
#
#  $false_tag
#   scalar: value to set destination object hash field if child does not exist
#
#  $error_list_ref
#   array ref: processing error strings
#
#
# Return:
#  $OK on success
#  $ERROR on failure
#
# ---------------------------------------------------------------------------
sub map_value_to_child_existence {
  my ($source_xml_ref, $destination_obj_ref, $child_elt_lookups_ref, $true_tag, $false_tag, $error_list_ref) = @_;

  #
  # Check for simple existence of these elts
  #
  foreach my $dest_key (keys(%$child_elt_lookups_ref)) {
    my ($child_elt_ref, $rex_status) = basic_xml_parser::first_child_by_name($source_xml_ref, $child_elt_lookups_ref->{$dest_key});
    if ($rex_status != $basic_xml_parser::SUCCESS &&
        $rex_status != $basic_xml_parser::NO_MATCH) {
      push(@$error_list_ref, "map_value_to_child_existence - error getting child $child_elt_lookups_ref->{$dest_key}");
      return $ERROR;
      }

    #
    # Mark vulnerability object key = 1 if the (empty) child element exists
    #
    $destination_obj_ref->{$dest_key} = ($rex_status == $basic_xml_parser::SUCCESS) ? $true_tag : $false_tag;
    }

  return $OK;
}

# ---------------------------------------------------------------------------
# Concatenate XML element substrings as one single string.
#
#
# Input:
#  $xml_subelt_list_ref
#   array ref: xml subelement strings from basic_xml_parser::parse
#
#
# Return:
#  $xml_string
#   string: concatenation of xml subelement strings
#
# ---------------------------------------------------------------------------
sub format_xml_as_string {
  my ($xml_subelt_list_ref) = @_;

  my $xml_string = '';

  foreach my $subelt (@$xml_subelt_list_ref) {
    $xml_string .= $subelt;
    }

  return $xml_string;
}


# ---------------------------------------------------------------------------
# Generate single string that concatenates vulnerability record elements.
#
#
# Input:
#
#  $vuln_obj_ref
#   hash ref: vulnerability record object
#
#  $finding_obj_ref
#   hash ref: vulnerability record object
#   may be undef
#
#  $sol_list_ref
#   array ref: list of solution hash ref objects
#
#  $impact_list_ref
#   array ref: list of impact hash ref objects
#
#  $swproduct_list_ref
#   array ref: list of software product hash ref objects
#
#  $reference_list_ref
#   array ref: list of reference hash ref objects
#
#  $error_list_ref
#   array ref: array of string to hold any processing error messages
#
#
# Return:
#  $translated_entry_string
#   string: single concatenation of vulnerability records contained in input
#           elements.
#
# ---------------------------------------------------------------------------
sub format_translated_entry_string {
  my ($vuln_obj_ref, $finding_list_ref, $related_list_ref, $sol_list_ref, $impact_list_ref, $swproduct_list_ref, $reference_list_ref, $error_list_ref) = @_;

  #
  # This is handy for testing
  #
  my $PRINT_LINE_SEPARATORS = 1;

  my $translated_entry_string = '';

  my $SEPARATOR = '<>';

  my $VULN_TAG = 'vulnerability';
  my $FIND_TAG = 'finding';
  my $RELATED_TAG = 'related';
  my $SOL_TAG  = 'solution';
  my $IMP_TAG  = 'impact';
  my $SOFT_TAG = 'product';
  my $REF_TAG  = 'reference';

  #
  # Print out vulnerability stats
  #
  condition_vulnerability_fields($vuln_obj_ref);
  $translated_entry_string .= "$VULN_TAG";
  foreach my $vuln_key (@VULN_PRINT_ELEMENTS) {
    my $field_value = condition_data_field($vuln_obj_ref->{$vuln_key});
    $translated_entry_string .= "${SEPARATOR}$field_value";
    }

  if ($PRINT_LINE_SEPARATORS) { $translated_entry_string .= "\n" };

  #
  # Print finding records
  #
  foreach my $finding_obj_ref (@$finding_list_ref) {
    $translated_entry_string .= "$FIND_TAG";
    foreach my $find_key (@FINDING_PRINT_ELEMENTS) {
      my $field_value = condition_data_field($finding_obj_ref->{$find_key});
      $translated_entry_string .= "${SEPARATOR}$field_value";
      }
    }

  #
  # Print related records
  #
  foreach my $related_obj_ref (@$related_list_ref) {
    $translated_entry_string .= "$RELATED_TAG";
    $related_obj_ref->{$REL_CVE} =~ s{CAN}{CVE}i;
    foreach my $find_key (@REL_PRINT_ELEMENTS) {
      my $field_value = condition_data_field($related_obj_ref->{$find_key});
      $translated_entry_string .= "${SEPARATOR}$field_value";
      }
    }

  #
  # Print any solution records
  #
  foreach my $sol_obj_ref (@$sol_list_ref) {
    $translated_entry_string .= "$SOL_TAG";
    foreach my $sol_key (@SOL_PRINT_ELEMENTS) {
      my $field_value = condition_data_field($sol_obj_ref->{$sol_key});
      $translated_entry_string .= "${SEPARATOR}$field_value";
      }
    if ($PRINT_LINE_SEPARATORS) { $translated_entry_string .= "\n" };
    }


  #
  # Print any impact records
  #
  foreach my $impact_obj_ref (@$impact_list_ref) {
    $translated_entry_string .= "$IMP_TAG";
    foreach my $impact_key (@IMPACT_PRINT_ELEMENTS) {
      my $field_value = condition_data_field($impact_obj_ref->{$impact_key});
      $translated_entry_string .= "${SEPARATOR}$field_value";
      }
    if ($PRINT_LINE_SEPARATORS) { $translated_entry_string .= "\n" };
    }

  #
  # Print any software records
  #
  foreach my $swprod_obj_ref (@$swproduct_list_ref) {
    $translated_entry_string .= "$SOFT_TAG";
    foreach my $swprod_key (@SWPROD_PRINT_ELEMENTS) {
      my $field_value = condition_data_field($swprod_obj_ref->{$swprod_key});
      $translated_entry_string .= "${SEPARATOR}$field_value";
      }
    if ($PRINT_LINE_SEPARATORS) { $translated_entry_string .= "\n" };
    }

  #
  # Print any reference records
  #
  foreach my $reference_obj_ref (@$reference_list_ref) {
    $translated_entry_string .= "$REF_TAG";
    foreach my $ref_key (@REF_PRINT_ELEMENTS) {
      my $field_value = condition_data_field($reference_obj_ref->{$ref_key});
      $translated_entry_string .= "${SEPARATOR}$field_value";
      }
    if ($PRINT_LINE_SEPARATORS) { $translated_entry_string .= "\n" };
    }

  return $translated_entry_string;
}

# ---------------------------------------------------------------------------
# Return reference to array of vulnerability records
#  Each record in format: TAG(<separator>element)+
#
# Data values are converted to $NONEXISTENT_ELEMENT_TEXT_VALUE if blank.
#  There's always the possibility of XML data being blank.
#
# Input:
#
#  $vuln_obj_ref
#   hash ref: vulnerability record object
#
#  $finding_obj_ref
#   hash ref: vulnerability record object
#   may be undef
#
#  $sol_list_ref
#   array ref: list of solution hash ref objects
#
#  $impact_list_ref
#   array ref: list of impact hash ref objects
#
#  $swproduct_list_ref
#   array ref: list of software product hash ref objects
#
#  $reference_list_ref
#   array ref: list of reference hash ref objects
#
#  $error_list_ref
#   array ref: array of string to hold any processing error messages
#
#
# Return:
#  @record_array
#   array ref: list of strings, each the concatenation of data for one
#              record object (e.g.: vulnerability, reference, etc.)
#
# ---------------------------------------------------------------------------
sub format_translated_entry_list {
  my ($vuln_obj_ref, $finding_list_ref, $related_list_ref, $sol_list_ref, $impact_list_ref, $swproduct_list_ref, $reference_list_ref, $error_list_ref) = @_;
  my $this_sub = 'format_translated_entry_list';

  my @record_array = ();

  my $record_string;

  my $SEPARATOR = '<>';

  my $VULN_TAG    = 'vulnerability';
  my $FIND_TAG    = 'finding';
  my $RELATED_TAG = 'related';
  my $SOL_TAG     = 'solution';
  my $IMP_TAG     = 'impact';
  my $SOFT_TAG    = 'product';
  my $REF_TAG     = 'reference';

  #
  # Print these out by 'finding'
  #
  if(scalar(@$finding_list_ref) < 1) {
    push(@$error_list_ref, "$this_sub - no finding records found for given vulnerability");
    return(\@record_array);
    }

  foreach my $finding_obj_ref (@$finding_list_ref) {
    #
    # Print finding itself
    #
    $record_string = "$FIND_TAG";
    foreach my $find_key (@FINDING_PRINT_ELEMENTS) {
      my $field_value = condition_data_field($finding_obj_ref->{$find_key});
      $record_string .= "${SEPARATOR}$field_value";
      }
    push(@record_array, $record_string);

   
    condition_vulnerability_fields($vuln_obj_ref);
    
    $record_string = "$VULN_TAG";
    foreach my $vuln_key (@VULN_PRINT_ELEMENTS) {
      my $field_value = condition_data_field($vuln_obj_ref->{$vuln_key});
      $record_string .= "${SEPARATOR}$field_value";
      }
    push(@record_array, $record_string);


    #
    # Print related records
    #
    foreach my $related_obj_ref (@$related_list_ref) {
      $record_string = "$RELATED_TAG";
      $related_obj_ref->{$REL_CVE} =~ s{CAN}{CVE}i;
      foreach my $find_key (@REL_PRINT_ELEMENTS) {
        my $field_value = condition_data_field($related_obj_ref->{$find_key});
        $record_string .= "${SEPARATOR}$field_value";
        }
      push(@record_array, $record_string);
      }

    #
    # Print any solution records
    #
    foreach my $sol_obj_ref (@$sol_list_ref) {
      $record_string = "$SOL_TAG";
      foreach my $sol_key (@SOL_PRINT_ELEMENTS) {
        my $field_value = condition_data_field($sol_obj_ref->{$sol_key});
        $record_string .= "${SEPARATOR}$field_value";
        }
      push(@record_array, $record_string);
      }


    #
    # Print any impact records
    #
    foreach my $impact_obj_ref (@$impact_list_ref) {
      $record_string = "$IMP_TAG";
      foreach my $impact_key (@IMPACT_PRINT_ELEMENTS) {
        my $field_value = condition_data_field($impact_obj_ref->{$impact_key});
        $record_string .= "${SEPARATOR}$field_value";
        }
      push(@record_array, $record_string);
      }

    #
    # Print any software records
    #
    foreach my $swprod_obj_ref (@$swproduct_list_ref) {
      $record_string = "$SOFT_TAG";
      foreach my $swprod_key (@SWPROD_PRINT_ELEMENTS) {
        my $field_value = condition_data_field($swprod_obj_ref->{$swprod_key});
        $record_string .= "${SEPARATOR}$field_value";
        }
      push(@record_array, $record_string);
      }

    #
    # Print any reference records
    #
    foreach my $reference_obj_ref (@$reference_list_ref) {
      $record_string = "$REF_TAG";
      foreach my $ref_key (@REF_PRINT_ELEMENTS) {
        my $field_value = condition_data_field($reference_obj_ref->{$ref_key});
        $record_string .= "${SEPARATOR}$field_value";
        }
      push(@record_array, $record_string);
      }
    }

  return \@record_array;
}



# ---------------------------------------------------------------------------
# Generate DB injection records for scans without findings (ex.: NVD).
#
# Return reference to array of vulnerability records
#  Each record in format: TAG(<separator>element)+
#
# Data values are converted to $NONEXISTENT_ELEMENT_TEXT_VALUE if blank.
#  There's always the possibility of XML data being blank.
#
# Input:
#
#  $vuln_obj_ref
#   hash ref: vulnerability record object
#
#  $sol_list_ref
#   array ref: list of solution hash ref objects
#
#  $impact_list_ref
#   array ref: list of impact hash ref objects
#
#  $swproduct_list_ref
#   array ref: list of software product hash ref objects
#
#  $reference_list_ref
#   array ref: list of reference hash ref objects
#
#  $error_list_ref
#   array ref: array of string to hold any processing error messages
#
#
# Return:
#  @record_array
#   array ref: list of strings, each the concatenation of data for one
#              record object (e.g.: vulnerability, reference, etc.)
#
# ---------------------------------------------------------------------------
sub format_nonfinding_entry_list {
  my ($vuln_obj_ref, $related_list_ref, $sol_list_ref, $impact_list_ref, $swproduct_list_ref, $reference_list_ref, $error_list_ref) = @_;

  my @record_array = ();

  my $record_string;

  my $SEPARATOR = '<>';

  my $VULN_TAG    = 'vulnerability';
  my $FIND_TAG    = 'finding';
  my $RELATED_TAG = 'related';
  my $SOL_TAG     = 'solution';
  my $IMP_TAG     = 'impact';
  my $SOFT_TAG    = 'product';
  my $REF_TAG     = 'reference';

  #
  # Print out vulnerability stats
  #
  condition_vulnerability_fields($vuln_obj_ref);

  $record_string = "$VULN_TAG";
  foreach my $vuln_key (@VULN_PRINT_ELEMENTS) {
    my $field_value = condition_data_field($vuln_obj_ref->{$vuln_key});
    $record_string .= "${SEPARATOR}$field_value";
    }
  push(@record_array, $record_string);

  #
  # Print related records
  #
  foreach my $related_obj_ref (@$related_list_ref) {
    $record_string = "$RELATED_TAG";
      $related_obj_ref->{$REL_CVE} =~ s{CAN}{CVE}i;
      foreach my $find_key (@REL_PRINT_ELEMENTS) {
      my $field_value = condition_data_field($related_obj_ref->{$find_key});
      $record_string .= "${SEPARATOR}$field_value";
      }
    push(@record_array, $record_string);
    }


  #
  # Print any impact records
  #
  foreach my $impact_obj_ref (@$impact_list_ref) {
    $record_string = "$IMP_TAG";
    foreach my $impact_key (@IMPACT_PRINT_ELEMENTS) {
      my $field_value = condition_data_field($impact_obj_ref->{$impact_key});
      $record_string .= "${SEPARATOR}$field_value";
      }
    push(@record_array, $record_string);
    }

  #
  # Print any reference records
  #
  foreach my $reference_obj_ref (@$reference_list_ref) {
    $record_string = "$REF_TAG";
    foreach my $ref_key (@REF_PRINT_ELEMENTS) {
      my $field_value = condition_data_field($reference_obj_ref->{$ref_key});
      $record_string .= "${SEPARATOR}$field_value";
      }
    push(@record_array, $record_string);
    }

  #
  # Print any solution records
  #
  foreach my $sol_obj_ref (@$sol_list_ref) {
    $record_string = "$SOL_TAG";
    foreach my $sol_key (@SOL_PRINT_ELEMENTS) {
      my $field_value = condition_data_field($sol_obj_ref->{$sol_key});
      $record_string .= "${SEPARATOR}$field_value";
      }
    push(@record_array, $record_string);
    }

  #
  # Print any software records
  #
  foreach my $swprod_obj_ref (@$swproduct_list_ref) {
    $record_string = "$SOFT_TAG";
    foreach my $swprod_key (@SWPROD_PRINT_ELEMENTS) {
      my $field_value = condition_data_field($swprod_obj_ref->{$swprod_key});
      $record_string .= "${SEPARATOR}$field_value";
      }
    push(@record_array, $record_string);
    }

  return \@record_array;
}




# ---------------------------------------------------------------------------
# Condition blank data found in raw XML.
# Remove single quotes.
# And remove any newlines - replace with spaces.
#
# Input:
#  string: data field value
#
# Return:
#  string: data fit for insertion
#
# ---------------------------------------------------------------------------
sub condition_data_field {
  my ($field_value) = @_;
  
  #
  # Remove newlines
  #
  $field_value =~ s{\n}{ }g;

  #
  # Remove leading/trailing space padding
  #
  $field_value =~ s{^\s+}{};
  $field_value =~ s{\s+$}{};
  
  #
  # Remove single quotes - no good for SQL
  #
  $field_value =~ s{'}{}g;

  return (length($field_value) > 0) ? $field_value : $NONEXISTENT_ELEMENT_TEXT_VALUE;
}


# ---------------------------------------------------------------------------
# Test utility - alert developer to unaddressed vulnerability fields.
#  Prints to screen default initialization fields detected in final output.
#  All vulnerability fields should be set at some point in processing.
#  Each unique field is printed once.
#  ######vuln:24###### would indicate that $VULN_RANGE_LOCAL (key 24) was
#  never handled by the translator code.
#
# Input:
#  string - final vulnerability output from format_translated_entry_string()
#
# Return:
#  none
#
# ---------------------------------------------------------------------------
sub check_translation_string {
  my ($translation_out_str) = @_;

  #
  # Check for unmodified fields
  #
  my @unmodified_field_matches = ($translation_out_str =~ m{######([^#]*)######}g);

  #
  # Just report on each unique match
  #
  my %unique_matches = ();
  foreach my $match (@unmodified_field_matches) {
    $unique_matches{$match}++;
    }

  print "UNMODIFIED FIELDS:\n";
  foreach my $unique_key (sort(keys(%unique_matches))) {
    print " $unique_key - $unique_matches{$unique_key}\n";
    }

}

# ---------------------------------------------------------------------------
# Pass output from format_translated_entry_list 
#  up to check_translation_string()
#
# Input:
#  string - final vulnerability output from format_translated_entry_list()
#
# Return:
#  none
#
# ---------------------------------------------------------------------------
sub check_translation_list {
  my ($translation_list_ref) = @_;

  check_translation_string(join("\n", @$translation_list_ref));
}


# ---------------------------------------------------------------------------
# Convert URL encoding to ASCII.
# Ex.: http%3A%2F%2Fwww%2Esecurityfocus%2Ecom%2Fbid%2F12483
#  to: http://www.securityfocus.com/bid/12483
#
# Input:
#  string: url-encoded text
#
# Return:
#  string: ASCII equivalent
# ---------------------------------------------------------------------------
sub convert_url_chars {
  my ($url_str) = @_;
  
  #
  # Replace %hh hex chars with their corresponding ascii
  #
  my @hexes = ($url_str =~ m{(%[a-fA-F0-9]{2})}g);
  foreach my $hex (@hexes) {
    my $hex_digits = $hex;
    $hex_digits =~ s{%}{};
    my $repl_chr = chr(hex($hex_digits));
    $url_str =~ s{$hex}{$repl_chr}g;
    }
  
  #
  # Replace any single-char alternates
  #
  my %ascii_for = (
    '\+' => ' ',      # '+' is a special character in s{}{} operation so it needs to be escaped
    );
    
  foreach my $url_char (keys %ascii_for) {
    $url_str =~ s{$url_char}{$ascii_for{$url_char}}g;
    }
 
 return ($url_str);
}


# ---------------------------------------------------------------------------
# Map CVSS vector field values to vulnerability object fields.
# The CVSS vector fields affect the true/false loss and range fields
# like loss_availability, loss_integrity, range_local.
# A non-none value (P-Partial, C-Complete) for a loss maps to 'true'
# for that loss field.
#
# Uses the cvss vector definition found here:
#  http://nvd.nist.gov/cvss.cfm?vectorinfo
#
# Input:
#  $vuln_obj_ref
#    hash ref: vulnerability object to update
#
#  $cvss_vector_str
#   string: CVSS vector of form: AV:R/AC:L/Au:R/C:C/I:N/A:P/B:N
#
# Return:
#  status: $OK on success
# ---------------------------------------------------------------------------
sub set_cvss_vector_fields {
  my ($vuln_obj_ref, $cvss_vector_str) = @_;

  #
  # Pull cvss vector apart at the '/' characters.
  #
  my @fields = split(/\//, $cvss_vector_str);
  
  foreach my $field (@fields) {
    my ($metric, $value) = split(/:/, $field);
    
    my $vuln_field;
    undef($vuln_field);
    if ($metric eq 'AV') {
      my %true_field_for = ('L' => $VULN_RANGE_LOCAL, 'R' => $VULN_RANGE_REMOTE);
      $vuln_field = $true_field_for{$value};
      }
    elsif ($metric eq 'C') {
      my %true_field_for = ('P' => $VULN_LOSS_CONFID, 'C' => $VULN_LOSS_CONFID);
      $vuln_field = $true_field_for{$value};
      }
    elsif ($metric eq 'I') {
      my %true_field_for = ('P' => $VULN_LOSS_INTEG,  'C' => $VULN_LOSS_INTEG);
      $vuln_field = $true_field_for{$value};
      }
    elsif ($metric eq 'A') {
      my %true_field_for = ('P' => $VULN_LOSS_AVAIL,  'C' => $VULN_LOSS_AVAIL);
      $vuln_field = $true_field_for{$value};
      }
      
    if (defined($vuln_field)) {
      $vuln_obj_ref->{$vuln_field} = $ELEMENT_EXISTENCE_VALUE;
      }
    }
  
  return ($OK);
}


sub condition_vulnerability_fields {
  my ($vuln_obj_ref) = @_;
  
  #
  # Make sure the CVE/CAN field is worth something
  #
  if(!($vuln_obj_ref->{$VULN_CVENAME} =~ m{(CAN|CVE)-\d\d\d\d-\d\d\d\d}i)) {
    $vuln_obj_ref->{$VULN_CVENAME} = $DEFAULT_CVE;
    }
    
  #
  # Replace CAN with CVE now that candidates have been promoted
  #
  $vuln_obj_ref->{$VULN_CVENAME} =~ s{CAN}{CVE}i;
  
  #
  # Replace blank date fields with default date string
  #
  my @date_fields = (
    $VULN_DT_DISCV,
    $VULN_DT_PUB,
    $VULN_DT_MOD
    );
  
  #
  # Explicitly blank out any bogus dates
  #
  foreach my $date_field (@date_fields) {
    if ($vuln_obj_ref->{$date_field} !~ m{\d\d\d\d-\d\d-\d\d}) {
      $vuln_obj_ref->{$date_field} = $DEFAULT_DATE;
      }
    }
  }
 
1;