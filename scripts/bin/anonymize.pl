#!/usr/bin/perl
################################################################################
#
# anonymize.pl
#
# Copyright (c) 2008 Endeavor Systems, Inc.
#
# This file is part of OpenFISMA.
#
# OpenFISMA is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# OpenFISMA is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with OpenFISMA.  If not, see {@link http://www.gnu.org/licenses/}.
#
################################################################################
#
# This is a script to anonymize OpenFISMA data. It operates on an
# existing schema, updating each row of each table one at a time
# as specified in the anonymization documentation.
#
# This can be used to migrate data from a production environment
# into a development or testing environment for debugging purposes
# without revealing any sensitive information.
#
# TODO: there is no special handling for UNIQUE KEY fields...although
# improbable for most OpenFISMA tables, there is a significant chance
# of key collision if used on large tables with small keyspaces.
#
# Author:    Mark E. Haase <mhaase@endeavorsystems.com>
# Copyright: (c) 2008 Endeavor Systems, Inc. (http://www.endeavorsystems.com)
# License:   http://www.openfisma.org/mw/index.php?title=License
# Version:   $Id$
#
################################################################################

use strict;
use Cwd qw/realpath/;
use Data::Dumper;
use DBI;
use File::Basename;
use File::Spec::Functions;
use Switch;

######################################################################
# Main entry point
######################################################################

# Read & parse the configuration file: $config is a hash reference
my $config = &getConfig();

# Connect to DB and verify that the target schema exists
&log('Connecting to database '.$config->{'dbHost'}.':'.$config->{'dbSchema'});
my $db = DBI->connect('DBI:mysql:'.$config->{'dbSchema'}.':'.$config->{'dbHost'}, $config->{'dbUser'}, $config->{'dbPassword'});

# Define the anonymization rules for each table and column.
# These rules are defined in TODO requirement doc path
# NOTE: You can not anonymize any part of a primary key.
# Even if you define such a rule here, the anonymizer will
# ignore it.
my %rules = ();
$rules{'ASSETS'}{'asset_name'}                 = 'string';
$rules{'ASSET_ADDRESSES'}{'address_ip'}        = 'string';
$rules{'ASSET_ADDRESSES'}{'address_port'}      = 'string';
$rules{'AUDIT_LOG'}{'description'}             = 'string';
$rules{'FINDINGS'}{'finding_data'}             = 'string';
$rules{'FINDING_SOURCES'}{'source_name'}       = 'string';
$rules{'FINDING_SOURCES'}{'source_nickname'}   = 'string';
$rules{'FINDING_SOURCES'}{'source_desc'}       = 'string';
$rules{'NETWORKS'}{'network_name'}             = 'metadata|Network ';
$rules{'NETWORKS'}{'network_nickname'}         = 'metadata|NET';
$rules{'NETWORKS'}{'network_desc'}             = 'metadata|Network Description #';
$rules{'POAMS'}{'legacy_poam_id'}              = 'null';
$rules{'POAMS'}{'poam_previous_audits'}        = 'null';
$rules{'POAMS'}{'poam_action_suggested'}       = 'string';
$rules{'POAMS'}{'poam_action_planned'}         = 'string';
$rules{'POAMS'}{'poam_cmeasure'}               = 'string';
$rules{'POAMS'}{'poam_cmeasure_justification'} = 'string';
$rules{'POAMS'}{'poam_action_resources'}       = 'string';
$rules{'POAMS'}{'poam_threat_source'}          = 'string';
$rules{'POAMS'}{'poam_threat_justification'}   = 'string';
$rules{'POAM_COMMENTS'}{'comment_body'}        = 'string';
$rules{'POAM_EVIDENCE'}{'ev_submission'}       = 'replace|evidence/sample.zip';
$rules{'SYSTEMS'}{'system_name'}               = 'string';
$rules{'SYSTEMS'}{'system_nickname'}           = 'string';
$rules{'SYSTEMS'}{'system_desc'}               = 'string';
$rules{'SYSTEMS'}{'system_criticality_justification'} = 'string';
$rules{'SYSTEMS'}{'system_sensitivity_justification'} = 'string';
$rules{'SYSTEM_GROUPS'}{'sysgroup_name'}       = 'string';
$rules{'SYSTEM_GROUPS'}{'sysgroup_nickname'}   = 'string';
$rules{'USERS'}{'user_name'}                   = 'string';
$rules{'USERS'}{'user_title'}                  = 'string';
$rules{'USERS'}{'user_name_last'}              = 'string';
$rules{'USERS'}{'user_name_first'}             = 'string';
$rules{'USERS'}{'user_name_middle'}            = 'string';
$rules{'USERS'}{'user_phone_office'}           = 'string';
$rules{'USERS'}{'user_phone_mobile'}           = 'string';
$rules{'USERS'}{'user_email'}                  = 'string';
$rules{'USERS'}{'user_password'}               = 'replace|'; # Replace with null string ('')
$rules{'USERS'}{'user_old_password1'}          = 'replace|';
$rules{'USERS'}{'user_old_password2'}          = 'replace|';
$rules{'USERS'}{'user_old_password3'}          = 'replace|';
$rules{'USERS'}{'user_history_password'}       = 'replace|';
$rules{'VULN_REFERENCES'}{'ref_name'}          = 'string';
$rules{'VULN_REFERENCES'}{'ref_source'}        = 'string';
$rules{'VULN_REFERENCES'}{'ref_url'}           = 'string';
$rules{'VULN_SOLUTIONS'}{'sol_desc'}           = 'string';

&debugLog('Dumping anonymization rules:');
&debugLog((Dumper(\%rules)));

# Run the rules for each table and column
&log('Beginning anonymization');
while((my $table, my $columns) = each(%rules)) {
  # Fetch the primary key for this table
  my %primaryKey;
  my $query = "DESCRIBE $table";
  my $pkq = $db->prepare($query) or &error("Could not prepare query \"$query\"");
  $pkq->execute() or &error("Could not execute query \"$query\"");
  while (my $tableDescription = $pkq->fetchrow_hashref()) {
    if ($tableDescription->{'Key'} eq 'PRI') {
      $primaryKey{$tableDescription->{'Field'}} = ''; # Using hash as a set, i.e. all values are empty string
    }
  } 
  if (scalar keys %primaryKey == 0) {
    &error("The table $table does not have a primary key.");
  }
  $pkq->finish();
  
  # Get all of the rows for this table
  # TODO could reduce the data fetched by explicitly naming columns, instead of '*'
  $query = "SELECT * FROM $table";
  $pkq = $db->prepare($query) or &error("Could not prepare query \"$query\"");
  $pkq->execute() or &error("Could not execute query \"$query\"");
  
  # Anonymize each row, one at a time
  &log("Starting table $table");
  my $rows = 0;
  while (my $tableData = $pkq->fetchrow_hashref()) {
    # Execute all rules on the current row
    while ((my $column, my $rule) = each(%$columns)) {
      # Make sure we're not trying to execute a rule on a primary key column
      if (defined $primaryKey{$column}) {
        &log("WARNING: rules can not be defined for primary key fields -- skipping rule \"$rule\" on $table.$column");
        next;
      }
      switch($rule) {
        case 'string'    {&string($tableData, $column)}
        case 'null'      {&null($tableData, $column)}
        case /^metadata/ {&metadata($tableData, $column, $rule, $rows)}
        case /^replace/  {&replace($tableData, $column, $rule)}
        else             {&debugLog("No matching rule for \"$rule\" on $table.$column")}
      }
    }
    
    # Update current row
    # TODO could reduce the data sent by only updating columns which were anonymized
    $query = "UPDATE $table SET ";
    while ((my $column, my $value) = each(%$tableData)) {
      next if defined $primaryKey{$column};
      if (defined $value) {
        # Escape quotation marks and backslashes before persisting value
        $value =~ s/(['"\\])/\\\1/g;
        $query .= "$column='$value', ";
      } else {
        $query .= "$column=NULL, ";
      }
    } 
    $query = substr $query, 0, -2;
    $query .= ' WHERE ';
    foreach (keys %primaryKey) {
      $query .= "$_='$tableData->{$_}' AND ";
    }
    $query = substr $query, 0, -4;
    my $upd = $db->prepare($query) or &error("Could not prepare query \"$query\"");
    $upd->execute() or &error("Could not execute query \"$query\"");
    $rows++;
  }
  &log("Finished table $table ($rows rows)");
}

# Done
$db->disconnect;
&log('Anonymization complete');

######################################################################
# Subroutines
######################################################################

# Print log messages in a standard format, including a timestamp.
sub log {
  my ($second, $minute, $hour, $mday, $month, $year, $wday, $yday, $isdst) = localtime();
  $year -= 100; # 2 digit year format
  $month  += 1;
  my $time = sprintf('[%02d:%02d:%02d %02d/%02d/%02d]',$hour,$minute,$second,$month,$mday,$year);
  print "$time @_\n";
}

# Conditional logging based on whether the debug parameter is set to 'true'
sub debugLog {
  if ($config->{'debug'} eq 'true') {
    &log(@_);
  } 
}

# Prints a log message than exits with an error code
sub error {
  &log("ERROR: @_");
  exit 1;
}

# Loads the configuration from key=value pairs stored in the config file,
# and returns a hashref
sub getConfig {
  my %config;
  my $line = 0;
  my $configPath = catfile(dirname(realpath($0)),'anonymize.cfg');
  &log("Using config file $configPath");
  open(CONFIG, $configPath) or &error("No configuration file found! (Create \"anonymize.cfg\" in the same directory as this script.)");
  while (<CONFIG>) {
    $line++;
    next if /^#/; # Ignore comment lines
    next if /^\s+$/; # Ignore blank lines
    
    if (m/^\s*(\S+)\s*=\s*(\S+)\s*/) { # Extract the key=value pair into $1 and $2
      $config{$1} = $2;
    } else {
      my $syntax = chomp;
      &error("Syntax error in configuration file on line $line: $syntax");
    }
  }
  
  # Can't use debugLog here because the config isn't initalized yet
  if ($config{'debug'} eq 'true') {
    &log('Dumping configuration');
    &log(Dumper(\%config));
  }
  return \%config;
}

# Randomizes a string by doing the following: replace any number character with a random number character,
# replace any letter character with a letter character of the same case (upper or lower), and leave all
# other characters the same. This randomization preserves word boundaries and punctuation.
#
# ASCII encoding is assumed. Any higher order characters (>127) will not be randomized.
sub string {
 (my $tableData, my $column) = @_;
  my @stringData = unpack 'C*', $tableData->{$column}; # Convert string (ASCII encoding) to array of integer values
  my $size = scalar @stringData;
  for (my $i = 0; $i <= $size; $i++) {
    if    ($stringData[$i] >= 48 && $stringData[$i] <= 57)  {$stringData[$i] = int(rand(10)) + 48} # replace numbers with numbers
    elsif ($stringData[$i] >= 65 && $stringData[$i] <= 90)  {$stringData[$i] = int(rand(26)) + 65} # replace upper case with upper case
    elsif ($stringData[$i] >= 97 && $stringData[$i] <= 122) {$stringData[$i] = int(rand(26)) + 97} # replace lower case with lower case
    # All other characters (white space, punctuation, control characters, etc.) remain the same.
  }
  $tableData->{$column} = pack 'C*', @stringData; # Convert array of integers to string (ASCII encoding)
}

# Sets a column to null.
sub null {
 (my $tableData, my $column) = @_;
  $tableData->{$column} = undef;
}

# Sets the column to the text specified with the row number appended
sub metadata {
 (my $tableData, my $column, my $text, my $row) = @_;
  $text =~ m/^metadata\|(.*)/;
  $tableData->{$column} = "$1$row";
}

# Sets the column to the text specified
sub replace {
 (my $tableData, my $column, my $text) = @_;
  $text =~ m/^replace\|(.*)/;
  $tableData->{$column} = "$1";
}
