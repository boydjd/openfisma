#!/usr/bin/perl
################################################################################
#
# migrate.pl
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
# This script migrates the schema named in the schema.cfg file (in the
# same directory as this script) from its existing version to the
# version designated on the command line (or the most recent version
# if not specified).
#
# For information about the OpenFISMA migrations policy:
# http://docs.google.com/Doc?id=dgznrgjw_15f7wt6cg3
#
# WARNING: This script passes your schema password in plaintext on
# the command line, which makes it visible to any other users logged
# into your system. This script should only be used on private
# systems.
#
# TODO Devise a way to safely pass the password. Probably put it in
# the user's my.cnf file.
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
use File::Copy;
use File::Spec::Functions;
use Getopt::Std;

require fisma;

######################################################################
# Main entry point
######################################################################

# Read & parse the configuration file: $config is a hash reference
our $config = &getConfig(catfile(dirname(realpath($0)),'schema.cfg'));

# Command line options
my %options;
getopts('v:', \%options);
my $targetVersion = $options{'v'};

# Get current schema version
&log("Connecting to database $config->{'dbHost'}:$config->{'dbSchema'}");
my $db = DBI->connect("DBI:mysql:$config->{'dbSchema'}:$config->{'dbHost'}", $config->{'dbUser'}, $config->{'dbPassword'})
  or &error("Unable to connect to the database");
my $query = "SELECT MAX(schema_version) schema_version FROM schema_version LIMIT 1";
my $dbq = $db->prepare($query) or &error("Could not prepare query \"$query\"");
$dbq->execute() or &error("Could not execute query \"$query\"");
my $database = $dbq->fetchrow_hashref();
if (not defined $database->{'schema_version'}) {&error("No schema version found in \"$config->{'dbSchema'}\" schema")}
my $schemaVersion = $database->{'schema_version'};
&log("Current schema version is $schemaVersion");

# Find the maximum version available in the base.sql file
my $schemaBaseFile = catfile($config->{'migrationsDir'}, 'base.sql');
my $maxVersion = 0;
open (BASE, $schemaBaseFile);
while (<BASE>) {
  chomp;
  if (/^INSERT INTO schema_version \(schema_version\) VALUES \((\d+)\);$/) {
    $maxVersion = $1;
  }
}
close BASE;
if (not defined $targetVersion and not defined $maxVersion) {
  &error("Unable to set target version: not specified on command line and not found in base.sql");
} else {
  if (not defined $targetVersion) {$targetVersion = $maxVersion}
  if ($targetVersion > $maxVersion) {&error("Target version ($targetVersion) is beyond the current max version ($maxVersion)");}
  &log("Target schema version is $targetVersion");
  if ($schemaVersion == $targetVersion) {&error("The schema is already at the designated version")};
}

# Figure out which patches to apply
my @migrations = ();
if ($schemaVersion < $targetVersion) {
  for (my $i=$schemaVersion+1; $i <= $targetVersion; $i++) {
    push @migrations, "$i.up";
  }
} elsif ($schemaVersion > $targetVersion) {
  for (my $i=$schemaVersion; $i > $targetVersion; $i--) {
    push @migrations, "$i.dn";
  }
}
&log('The following patches will be applied: '.join(', ', @migrations));

# Verify that all required patches are existing and see if they have NRs ("non reversible" migration tags)
foreach (@migrations) {
  # If upgrading, then warn on NR, if downgrading, then error on NRs
  $_ =~ /^(\d+)\.\w\w$/;
  if (-f catfile($config->{'migrationsDir'}, "$1.NR")) {
    if ($schemaVersion < $targetVersion) {
      &log("WARNING: Migration version ($1) is tagged as non-reversible. 
            If you upgrade to this version or beyond, you will not be able to 
            regress past this version afterwards.");
      &error("User canceled operation") 
        unless &prompt('Do you want to continue?', 'y', 'n') eq 'y';
    } else {
      if ($1 != $targetVersion) {
        &error("Migration version ($1) is tagged as non-reversible. You can not
                 regress beyond this version.");
      }
    }
  }
  
  # Check to make sure the migration script exists
  if (not -f catfile($config->{'migrationsDir'}, "$_.sql"))
    {&error("The migration file $_.sql does not exist. Update your working copy.")}
}

# Execute the planned migrations
foreach (@migrations) {
  # Run the migration script
  &log("Executing $_.sql");
  &mysqlSource(catfile($config->{'migrationsDir'},"$_.sql"));
  
  # Update the schema version. If we fail midway through the migrations,
  # the schema version number will still be correct as long as we update
  # it on every iteration.
  my $version;
  if (/^(\d+)\.dn$/) {
    $version = $1 - 1;
  } elsif (/^(\d+)\.up$/) {
    $version = $1;
  }
  $query = "UPDATE schema_version SET schema_version = $version";
  $dbq = $db->prepare($query) or &error("Could not prepare query \"$query\"");
  $dbq->execute() or &error("Could not execute query \"$query\"");
}

&log("Your migrations are successful");

######################################################################
# Subroutines
######################################################################

# Dump schema into file
sub dumpSchema {
 (my $outputFile) = @_;
  open (DUMPFILE, ">$outputFile");
  my $command = "$config->{'mysqldumpCommand'} -h $config->{'dbHost'} 
                                               -u $config->{'dbUser'} 
                                               -p$config->{'dbPassword'}
                                               --compact
                                               $config->{'tempSchema'}";
  $command =~ s/\s+/ /g; # Reformat the command onto one line
  &debugLog($command);
  my $schemaDump = `$command`;
  if ($? != 0) {
    &error("Command did not execute successfully. See previous output for more info.");
  }
  print DUMPFILE <<NOTICE;
--------------------------------------------------------------------
-- WARNING This file is created automatically and should not be
-- edited by hand.
--------------------------------------------------------------------
NOTICE
  print DUMPFILE $schemaDump;
  close DUMPFILE;
}

# Source the specified file into the temp schema
sub mysqlSource {
 (my $source) = @_;
  my $command = "$config->{'mysqlCommand'} -h $config->{'dbHost'} 
                                           -u $config->{'dbUser'} 
                                           -p$config->{'dbPassword'}
                                           $config->{'dbSchema'}
                                           < $source";
  $command =~ s/\s+/ /g; # Reformat the command onto one line
  &debugLog($command);
  my $result = `$command`;
  if ($? != 0) {
    &error("Command did not execute successfully. See previous output for more info.");
  }
}
