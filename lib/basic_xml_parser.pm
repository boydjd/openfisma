# ---------------------------------------------------------------------------
# REX "shallow" XML parse utilities.
#
# REX is a lean regular-expression based XML parsing mechanism that simply
# splits a string of XML into component text and markup sub-element strings.
# This module adds DOM-like functionality to the initial parse, providing 
# access to child elements, element attributes and text data.
#
# The "currency" of this module is the sub-element array reference, a list
# of strings representing the component text and markup bits of a full XML
# element. The initial parse returns a full list of the XML tokens found in
# the original data string; child elements are split out into their own
# sub-element arrays for further reference.
# A single sub-element array contains all text and markup from the opening
# top-level tag to the closing top-level tag, inclusive.
#
# More on REX here: http://www.cs.sfu.ca/~cameron/REX.html
# ---------------------------------------------------------------------------


package basic_xml_parser;

use strict;

# ---------------------------------------------------------------------------
# Regular expressions that set up the REX XML shallow parsing.
# Theory here: http://www.cs.sfu.ca/~cameron/REX.html
# ---------------------------------------------------------------------------
my $TextSE = "[^<]+";
my $UntilHyphen = "[^-]*-";
my $Until2Hyphens = "$UntilHyphen(?:[^-]$UntilHyphen)*-";
my $CommentCE = "$Until2Hyphens>?";
my $UntilRSBs = "[^\\]]*](?:[^\\]]+])*]+";
my $CDATA_CE = "$UntilRSBs(?:[^\\]>]$UntilRSBs)*>";
my $S = "[ \\n\\t\\r]+";
my $NameStrt = "[A-Za-z_:]|[^\\x00-\\x7F]";
my $NameChar = "[A-Za-z0-9_:.-]|[^\\x00-\\x7F]";
my $Name = "(?:$NameStrt)(?:$NameChar)*";
my $QuoteSE = "\"[^\"]*\"|'[^']*'";
my $DT_IdentSE = "$S$Name(?:$S(?:$Name|$QuoteSE))*";
my $MarkupDeclCE = "(?:[^\\]\"'><]+|$QuoteSE)*>";
my $S1 = "[\\n\\r\\t ]";
my $UntilQMs = "[^?]*\\?+";
my $PI_Tail = "\\?>|$S1$UntilQMs(?:[^>?]$UntilQMs)*>";
my $DT_ItemSE = "<(?:!(?:--$Until2Hyphens>|[^-]$MarkupDeclCE)|\\?$Name(?:$PI_Tail))|%$Name;|$S";
my $DocTypeCE = "$DT_IdentSE(?:$S)?(?:\\[(?:$DT_ItemSE)*](?:$S)?)?>?";
my $DeclCE = "--(?:$CommentCE)?|\\[CDATA\\[(?:$CDATA_CE)?|DOCTYPE(?:$DocTypeCE)?";
my $PI_CE = "$Name(?:$PI_Tail)?";
my $EndTagCE = "$Name(?:$S)?>?";
my $AttValSE = "\"[^<\"]*\"|'[^<']*'";
my $ElemTagCE = "$Name(?:$S$Name(?:$S)?=(?:$S)?(?:$AttValSE))*(?:$S)?/?>?";
my $MarkupSPE = "<(?:!(?:$DeclCE)?|\\?(?:$PI_CE)?|/(?:$EndTagCE)?|(?:$ElemTagCE)?)";
my $XML_SPE = "$TextSE|$MarkupSPE";


# ---------------------------------------------------------------------------
# Return codes.
# Values are exposed through subroutines of the same name. This allows the use
# of 'use strict' to enforce variable scope declaration within the module.
# ---------------------------------------------------------------------------
our $SUCCESS          =  1;
our $FAILURE          =  0;
our $NOT_AN_ARRAY_REF = -1;
our $NO_MATCH         = -2;
our $EMPTY_ARRAY      = -3;


# ---------------------------------------------------------------------------
# Parse a raw data XML data string into an array of component markup
# and text sub-element strings.
# 
# INPUT:
#  $raw_xml_data_string - the XML string to be parsed
#
# RETURN:
#  reference to an array of sub-element strings
#  status code
#   basic_xml_parser::SUCCESS on success (one or more XML sub-elements found)
#   basic_xml_parser::FAILURE on failure (no XML sub-elements found)
# ---------------------------------------------------------------------------
sub parse {
  my ($raw_xml_data_string) = @_;

  #
  # Evaluate the comprehensive XML analysis regular expression reiteratively
  # (g flag) into an array context. The return array contains a list of
  # matching strings - the component sub elements of the raw XML block.
  #
  my @xml_subelt_array = ($raw_xml_data_string =~ m{$XML_SPE}g);

  #
  # Return success if one or more xml elements are returned by the
  # regular expression.
  #
  my $status = (scalar(@xml_subelt_array) > 0) ? $SUCCESS : $FAILURE;

  return (\@xml_subelt_array, $status);
}

# ---------------------------------------------------------------------------
# Extract the immediate children of the passed-in parent XML element.
# Each element returned is a complete child element from the first
# nested level below the parent. Any grandchild elements will be bundled
# within their respective container elements.
#
# The ms flags at the end of the matches provide 'clean multiline'
#  matching - multiple-line elements are handled as blocks
#  and the '.' (dot wildcard) can match newline characters.
#
# ---------------------------------------------------------------------------
sub all_direct_children {
  my ($xml_subelt_array_ref) = @_;

  #
  # Return immediately if we're not dealing with XML subelement data.
  #
  if (!is_xml_subelt_list($xml_subelt_array_ref)) {
    return (undef, $NOT_AN_ARRAY_REF);
    }

  #
  # Peel off the parent container elements (the opening and closing
  #  components that enclose the children) to keep them out of the
  #  depth count.
  #
  if (scalar(@$xml_subelt_array_ref) > 2) {
    shift(@$xml_subelt_array_ref);
    pop(@$xml_subelt_array_ref);
    }

  #
  # Initialize return list
  #
  my @child_element_list = ();

  my $current_element_accumulator_ref;
  my $depth_count = 0;
  my $current_elt_name;
  
  foreach my $subelt (@$xml_subelt_array_ref) {
    #
    # If we're at the root level and looking for new children
    #
    if ($depth_count == 0) {
      #
      # If this is an opening element (self-contained or multicomponent)
      #
      if ($subelt =~ m{^\s?<\s?(.+)[\s/>]}ms) {
        $current_elt_name = $1;
        #print "$current_elt_name\n";
        #
        # Start new current element 
        #  and add the current string to the new element
        #
        $current_element_accumulator_ref = [];
        push(@$current_element_accumulator_ref, $subelt);
        
        #
        # If this is a self-contained child 
        #
        if($subelt =~ m{/\s?>\s?$}ms) {
          #print "$current_elt_name is a self-contained child\n";
          #
          # Add the current element to the output list
          #
          push(@child_element_list, $current_element_accumulator_ref);
          }
        #
        # Otherwise this is the start of a multicomponent element
        #
        else {
          #print "this is the start of a multicomponent object\n";
          #
          # Increment the depth count 
          #  and keep accumulating subelement components
          #
          $depth_count++;
          }
        }  # is opening element
      #
      # If anything other than starting elt
      #
      else {
        #
        # Ignore - this is data attached to the root element
        #
        }
      }   # depth_count == 0
    #
    # Else if we're in the middle of a multicomponent element
    #
    elsif ($depth_count > 0) {
      #
      # Add the current string to the current element
      #
      push(@$current_element_accumulator_ref, $subelt);
        
      #
      # If this is the closing component of a multi-component element
      #
      if ($subelt =~ m{<\s?/\s?(.*)\s?>}ms) {
        #print "detected a closing elt $1 '$subelt'\n";
        #
        # Decrement the depth counter
        #
        $depth_count--;
        }
      
      #
      # If this is starting a new (multicomponent grandchild) element
      #
      if ($subelt =~ m{^\s?<\s?(.+)\s?>}ms) {
        #print "found nested elt $1\n";
        #
        # Increment depth counter
        #
        $depth_count++;
        }
        
      #
      # If we've bubbled back to the root
      #
      if ($depth_count == 0) {
        #
        # Add current element to output element list
        #
        push(@child_element_list, $current_element_accumulator_ref);
        }
      }
    #
    # If depth count has gone negative there's something very wrong
    #
    else {
      die "ERROR basic_xml_parser::all_direct_children negative element depth at elt '$subelt'";
      }
    }
    
  my $status = $SUCCESS;
  return (\@child_element_list, $status);
}



# ---------------------------------------------------------------------------
# Extract child elements of a particular name from the top level of a
# given XML element.
# The children returned represent the first level of the named element
# detected - if elements can be nested
# (<elt id="child"><elt id="grandchild"/></elt>), the nested grandchildren will
# be returned embedded as raw sub-elements within the higher-level children,
# not split out as individual elements in the returned array.
#
# INPUT:
#  $xml_subelt_array_ref - array of XML sub-elements generated by parse()
#  $element_name - name of child element(s) to extract
#
# RETURN:
#  reference to an array of sub-element array references
#  status code
#   basic_xml_parser::SUCCESS on success (one or more child elements found)
#   basic_xml_parser::NOT_AN_ARRAY_REF if input not from basic_xml_parser::parse()
#   basic_xml_parser::NO_MATCH if no matching children found
# ---------------------------------------------------------------------------
sub children_by_name {
  my ($xml_subelt_array_ref, $element_name) = @_;

  #
  # Return immediately if we're not dealing with XML subelement data.
  #
  if (!is_xml_subelt_list($xml_subelt_array_ref)) {
    return (undef, $NOT_AN_ARRAY_REF);
    }

  #
  # Initialize return list
  #
  my @child_element_list = ();

  my $current_element_accumulator_ref;
  my $depth_count = 0;
  foreach my $subelt (@$xml_subelt_array_ref) {
    #
    # If opening element detected for name of interest
    #  and it's not nested within a parent element of the same name
    # ([ \/>] - reject 'refs' when looking for 'ref'
    #  only allow a space, closing / or closing > after name of interest.)
    # The ms flags at the end of the match provide 'clean multiline'
    #  matching - multiple-line elements are supported and the '.' (dot
    #  wildcard) can match newlines.
    #
    if ( ($subelt =~ m{^\s?<\s?$element_name[\s/>]}ms) &&
         ($depth_count < 1) ){
      #print "starting for $element_name - subpart $subelt\n";
      #
      # Initialize new current element array
      #
      $current_element_accumulator_ref = [];
      #
      # Add subelement string to element array
      #
      push(@$current_element_accumulator_ref, $subelt);
      #
      # If this is a self-closing element
      #
      if($subelt =~ m{/\s?>\s?$}ms) {
        #
        # Add current element array (of one) to element array list
        #
        push(@child_element_list, $current_element_accumulator_ref);
        }
      else {
        #
        # Increment depth count to accumulate subelts.
        # The depth counter is monitored to prevent nested elements of the
        # same name from terminating an element prematurely.
        # This will incorporate nested elements with the same name as the parent
        # into the top-level parent element.
        #
        $depth_count++;
        }
      }
    #
    # Else if a closing element is detected for name of interest
    #
    elsif ($subelt =~ m{<\s?/\s?$element_name\s?>}ms) {
      die "Closing element $element_name found without corresponding open element" if $depth_count < 1;
      #
      # Add current string to element array
      #
      push(@$current_element_accumulator_ref, $subelt);
      #
      # Decrement depth count
      #
      $depth_count--;
      #
      # If emerging from an element of interest
      #
      if ($depth_count == 0) {
        #
        # Add element array to element array list
        #
        #print "ending for $eltname - subpart $subelt\n";
        push(@child_element_list, $current_element_accumulator_ref);
        }
      }
    #
    # Otherwise, if we're in the middle of tracking an element
    #
    elsif ($depth_count > 0) {
      #
      # Add the current string to the current element list
      #
      push(@$current_element_accumulator_ref, $subelt);
      }
    }

  my $status = (scalar(@child_element_list) > 0) ? $SUCCESS : $NO_MATCH;

  return(\@child_element_list, $status);
}

# ---------------------------------------------------------------------------
# Extract first detected child element of a particular name
# from the top level of a given XML element.
# The children returned represent the first level of the named element
# detected - if elements can be nested
# (<elt id="child"><elt id="grandchild"/></elt>), the nested grandchildren will
# be returned embedded as raw sub-elements within the higher-level children,
# not split out as individual elements in the returned array.
#
# INPUT:
#  $xml_subelt_array_ref - array of XML sub-elements generated by parse()
#  $element_name - name of child element to extract
#
# RETURN:
#  reference to an array of sub-element array references
#  status code
#   basic_xml_parser::SUCCESS on success (child element found)
#   basic_xml_parser::NOT_AN_ARRAY_REF if input not from basic_xml_parser::parse()
#   basic_xml_parser::NO_MATCH if no matching children found
# ---------------------------------------------------------------------------
sub first_child_by_name {
  my ($xml_subelt_array_ref, $element_name) = @_;

  #
  # Use general utility to get all children of interest first
  #
  my ($child_array_ref, $status) = children_by_name($xml_subelt_array_ref, $element_name);

  if ($status != $SUCCESS) {
    #
    # Return an empty list of subelements
    #
    return([], $status);
    }

  #
  # children_by_name() only returns SUCCESS if one or more children found,
  #  so there's at least one to index.
  #
  return($child_array_ref->[0], $SUCCESS);
}


# ---------------------------------------------------------------------------
# Return the name of the root element passed in.
#
# INPUT:
#  $xml_subelt_array_ref - array of XML sub-elements generated by parse()
#
# RETURN:
#  name of root element
#  status
#   basic_xml_parser::SUCCESS on success
#   basic_xml_parser::NOT_AN_ARRAY_REF if input not from basic_xml_parser::parse()
#   basic_xml_parser::EMPTY_ARRAY if no subelements in xml input
# ---------------------------------------------------------------------------
sub element_name {
  my ($xml_subelt_array_ref) = @_;
  
  my $name_string = "";
  
  #
  # Return immediately if we're not dealing with XML subelement data.
  #
  if (!is_xml_subelt_list($xml_subelt_array_ref)) {
    return ($name_string, $NOT_AN_ARRAY_REF);
    }
  
  if (scalar(@$xml_subelt_array_ref) < 1) {
    return ($name_string, $EMPTY_ARRAY);
    }
  
  #
  # Access the first element of the passed-in subelement list.
  #  This will be either <openElt attr="val">, <openElt>
  #  or <closedElt attr="val"/>, <closedElt/>
  #
  my $first_elt = $xml_subelt_array_ref->[0];

  $first_elt =~ m{<\s*([^ \/>]*)};
  $name_string = $1;

  my $status = (length($name_string) > 0) ? $SUCCESS : $FAILURE;

  return ($name_string, $status);
}


# ---------------------------------------------------------------------------
# Extract all attributes from the top-level element of a given XML sub-element
# array.
# Attributes are returned in a hash of form attr_name => value.
# If the top-level element has no attributes, an empty hash is returned.
#
# INPUT:
#  $xml_subelt_array_ref - array of XML sub-elements generated by parse()
#
# RETURN:
#  reference to a hash of attribute_name => value pairs
#  status code
#   basic_xml_parser::SUCCESS if there was a top-level element to analyze
#   basic_xml_parser::NOT_AN_ARRAY_REF if input not from basic_xml_parser::parse()
#   basic_xml_parser::NO_MATCH if there was no top-level element to analyze
# ---------------------------------------------------------------------------
sub shallow_attributes {
  my ($xml_subelt_array_ref) = @_;

  #
  # Return immediately if we're not dealing with XML subelement data.
  #
  if (!is_xml_subelt_list($xml_subelt_array_ref)) {
    return (undef, $NOT_AN_ARRAY_REF);
    }

  my %attribute_hash = ();

  #
  # Retrieve attr_name = value pairs
  #  where values are delimited by ' or " chars,
  #  attr_names are barewords (\w*)
  #  and attr_name is separated from value by an = sign and optional whitespace (\s?).
  #
  my $name_value_regexp = "\\w*\\s?=\\s?$AttValSE";

  #
  # Test against the first (opening) subelement string
  #
  if(scalar(@$xml_subelt_array_ref) < 1) {
    #return (undef, $NO_MATCH);
    #
    # No match - return an empty hash reference
    #
    return ({}, $NO_MATCH);
    }
  my $main_subelt = $xml_subelt_array_ref->[0];

  my @name_value_pair_strings = ($main_subelt =~ m{$name_value_regexp}g);

  #
  # Split up the attr_name = value pair strings.
  # Store in return hash as attr_name => value.
  #
  # Need to protect against = chars in the data, otherwise name will contain
  #   value data up to the last = sign.
  #  [^\s=] stops the 'name' match at the first whitespace or = sign
  #
  my $nameval_isolator_regexp = "\\s?([^\\s=]*)\\s?=\\s?(.*)\\s?";
  foreach my $name_value_string (@name_value_pair_strings) {
    if (my ($name, $value) = $name_value_string =~ m{$nameval_isolator_regexp}) {
      #print "($name, $value)\n";
      $attribute_hash{$name} = strip_endchars($value);
      }
    else {
      die "Unable to extract name/value from $name_value_string";
      }
    }

  return (\%attribute_hash, $SUCCESS);
}


# ---------------------------------------------------------------------------
# Get the value of the named attribute from the given sub-element array.
#
# INPUT:
#  $xml_subelt_array_ref - array of XML sub-elements generated by parse()
#  $attr_name - attribute of interest
#
# RETURN:
#  string value of attribute value
#  status code
#   basic_xml_parser::SUCCESS if attribute was found
#   basic_xml_parser::NOT_AN_ARRAY_REF if input not from basic_xml_parser::parse()
#   basic_xml_parser::NO_MATCH if there was no top-level element to analyze
#                       or no attribute of that name was found
# ---------------------------------------------------------------------------
sub attribute_by_name {
  my ($xml_subelt_array_ref, $attr_name) = @_;

  my $attr_value = "";

  #
  # Use general utility to get all attributes first
  #
  my ($attr_hash_ref, $status) = shallow_attributes($xml_subelt_array_ref);

  if ($status != $SUCCESS) {
    return($attr_value, $status);
    }

  #
  # Return named attribute value if found, NO_MATCH otherwise.
  #
  $attr_value = $attr_hash_ref->{$attr_name};

  $status = (defined($attr_value)) ? $SUCCESS : $NO_MATCH;

  return($attr_value, $status);
}

# ---------------------------------------------------------------------------
# Get all text data from a given sub-element array.
# This call concatenates all text data found within a given element - if nested
# elements contain text then that will be appended to the return string.
#
# This is recommended for use on isolated elements.
#
# INPUT:
#  $xml_subelt_array_ref - array of XML sub-elements generated by parse()
#
# RETURN:
#  string containing any detected text data (empty if no text data found)
#  status code
#   basic_xml_parser::SUCCESS if input from basic_xml_parser::parse()
#   basic_xml_parser::NOT_AN_ARRAY_REF if input not from basic_xml_parser::parse()
# ---------------------------------------------------------------------------
sub deep_data {
  my ($xml_subelt_array_ref) = @_;

  #
  # Return immediately if we're not dealing with XML subelement data.
  #
  if (!is_xml_subelt_list($xml_subelt_array_ref)) {
    return (undef, $NOT_AN_ARRAY_REF);
    }

  my $data_string = "";

  #
  # Append all sub-elements that are not enclosed in angle brackets
  #
  foreach my $subelt (@$xml_subelt_array_ref) {
    #print "'$sub_elt'";
    #if (! $sub_elt =~ /[<>]/) {
    if ($subelt !~ m{[<>]}) {
      $data_string .= $subelt;
      }
    }

  return(convert_special_chars($data_string), $SUCCESS);
}


# ---------------------------------------------------------------------------
# Extract text data from first detected child element of a particular name
# from the given XML element.
#
# INPUT:
#  $xml_subelt_array_ref - array of XML sub-elements generated by parse()
#  $child_name - name of child element containing data of interest
#
# RETURN:
#  reference to an array of sub-element array references
#  status code
#   basic_xml_parser::SUCCESS on success (child element found)
#   basic_xml_parser::NOT_AN_ARRAY_REF if input not from basic_xml_parser::parse()
#   basic_xml_parser::NO_MATCH if no matching children found
# ---------------------------------------------------------------------------
sub get_first_named_child_data {
  my ($xml_subelt_array_ref, $child_name) = @_;

  #foreach my $str (@$xml_subelt_array_ref) { print " $str\n"; }

  my $this_sub = 'get_first_named_child_data';

  my $child_elt_ref;
  my $call_status;

  ($child_elt_ref, $call_status) = first_child_by_name($xml_subelt_array_ref, $child_name);
  if ($call_status != $SUCCESS) {
    return(undef, $call_status);
    }

  my $data;
  ($data, $call_status) = basic_xml_parser::deep_data($child_elt_ref);

  return($data, $call_status);
}


# ---------------------------------------------------------------------------
# Check object for likelihood of it being a reference to
# an array of xml sub-elements.
#
# INPUT:
#  $object_in_question - variable to test
#
# RETURN:
#  true if object is array reference, false otherwise
# ---------------------------------------------------------------------------
sub is_xml_subelt_list {
  my ($object_in_question) = @_;
  #
  # Return true if passed-in variable is a reference to an array,
  # false otherwise.
  #
  return(ref($object_in_question) eq 'ARRAY');
}


# ---------------------------------------------------------------------------
# Strip first and last characters from a string.
#
# INPUT:
#  string
#
# RETURN:
#  shorter string
# ---------------------------------------------------------------------------
sub strip_endchars {
  my ($input_string) = @_;

  return "" if (length($input_string) < 2);

  my $output_string = substr($input_string, 1, (length($input_string) - 2));

  return($output_string);
}


# ---------------------------------------------------------------------------
# Convert XML special character codes to text equivalents.
#
#
# INPUT:
#  string
#
# RETURN:
#  converted string
# ---------------------------------------------------------------------------
sub convert_special_chars {
  my ($input_string) = @_;

  my $output_string = $input_string;
  
  # Check for any doubled-up ampersands - these show up in NVD
  $output_string =~ s/&amp;/&/g;

  my %character_for = (
    '&quot;' => '"',
    '&lt;'   => '<',
    '&gt;'   => '>',
    '&amp;'  => '&',
    '&apos;' => '\'',
    );
    
  foreach my $key (keys(%character_for)) {
    $output_string =~ s/$key/$character_for{$key}/g;
    }

  return($output_string);
}
