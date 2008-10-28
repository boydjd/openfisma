#!/usr/bin/perl
################################################################################
#
# testmigrate.pl
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
# along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
#
################################################################################
#
# This script is the developer's tool for OpenFISMA migrations. It is used to
# test patches before committing them, and used to update the base.sql with the
# latest application schema and all metadata.
#
# For information about the OpenFISMA migrations policy:
# http://docs.google.com/Doc?id=dgznrgjw_15f7wt6cg3
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

require fisma;

######################################################################
# Main entry point
######################################################################

# Read & parse the configuration file: $config is a hash reference
our $config = &getConfig(catfile(dirname(realpath($0)),'schema.cfg'));

# Drop temporary schema if it exists
&log('Connecting to database '.$config->{'dbHost'});
my $db = DBI->connect("DBI:mysql:host=$config->{'dbHost'}", $config->{'dbUser'}, $config->{'dbPassword'})
  or &error("Unable to connect to the database");
my $query = "SHOW DATABASES LIKE '$config->{'tempSchema'}'";
my $dbq = $db->prepare($query) or &error("Could not prepare query \"$query\"");
$dbq->execute() or &error("Could not execute query \"$query\"");
while (my $database = $dbq->fetchrow_arrayref()) {
  if ($database->[0] eq $config->{'tempSchema'}) {
    &log("Dropping existing database \"$config->{'tempSchema'}\"");
    $query = "DROP DATABASE $config->{'tempSchema'}";
    my $dropq = $db->prepare($query) or &error("Could not prepare query \"$query\"");
    $dropq->execute() or &error("Could not execute query \"$query\"");
  }
}

# Create new temporary schema and connect to it
&log("Creating new database \"$config->{'tempSchema'}\"");
$query = "CREATE DATABASE $config->{'tempSchema'}";
my $dbq = $db->prepare($query) or &error("Could not prepare query \"$query\"");
$dbq->execute() or &error("Could not execute query \"$query\"");
$db->disconnect;
$db = DBI->connect("DBI:mysql:$config->{'tempSchema'}:$config->{'dbHost'}", $config->{'dbUser'}, $config->{'dbPassword'})
  or &error("Unable to connect to the database");

# Load base schema from base.sql into the temporary schema
&log("Loading base schema into temporary schema");
my $baseSchemaFile = catfile($config->{'migrationsDir'}, 'base.sql');
my $command = "$config->{'mysqlCommand'} -h $config->{'dbHost'} 
                                         -u $config->{'dbUser'} 
                                         -p$config->{'dbPassword'} 
                                         $config->{'tempSchema'}
                                         < $baseSchemaFile";
$command =~ s/\s+/ /g; # Reformat the command onto one line
&debugLog($command);
my $output = `$command`;
if ($? != 0) {
  &error("Command did not execute successfully. See previous output for more info.");
}

# Get current schema version
my $query = "SELECT MAX(schema_version) schema_version FROM schema_version LIMIT 1";
my $dbq = $db->prepare($query) or &error("Could not prepare query \"$query\"");
$dbq->execute() or &error("Could not execute query \"$query\"");
my $database = $dbq->fetchrow_hashref();
if (not defined $database->{'schema_version'}) {&error("No schema version found in \"$config->{'dbSchema'}\" schema")}
my $schemaVersion = $database->{'schema_version'};
&log("Current schema version is $schemaVersion");

# Figure out which versions exist in the migrations directory that are greater
# than the current schema version
if (! -d $config->{'migrationsDir'}) {&error("The migrations directory $config->{'migrationsDir'} does not exist")}
opendir (MIGRATIONS, $config->{'migrationsDir'});
my @files = readdir(MIGRATIONS);
my @migrations = ();
foreach (@files) {
  my $migrationVersion;
  if (/(\d+)\.up\.sql/) {
    $migrationVersion = $1;
    if (! -f catfile($config->{'migrationsDir'},"$migrationVersion.dn.sql")) 
      {&error("There is no downward migration for $migrationVersion but there is an upward migration.")}
    if ($migrationVersion > $schemaVersion) {
      unshift @migrations, $migrationVersion;
    }
  } elsif (/(\d+)\.dn\.sql/) {
    $migrationVersion = $1;
    if (! -f catfile($config->{'migrationsDir'},"$migrationVersion.up.sql")) 
      {&error("There is no upward migration for $migrationVersion but there is a downward migration.")}
  }
}
closedir MIGRATIONS;
if (scalar @migrations == 0) {
  &error("There are no migrations beyond the current schema version ($schemaVersion)");
}
@migrations = sort @migrations;
# Verify that the migrations don't have any gaps in them
if (scalar @migrations > 1) {
  for (my $i=1; $i<(scalar @migrations); $i++) {
    if (@migrations[$i-1] != @migrations[$i] - 1) { 
      &error("There is a gap between migration versions @migrations[$i-1] and @migrations[$i]. Consider renaming migration @migrations[$i] to migration ".(@migrations[$i-1]+1));
    }
  }
}
&log('The following migrations will be tested: '.join(', ', @migrations));

# Copy base.sql into temp-before-[n].sql in preparation for testing the migrations.
copy($baseSchemaFile, catfile($config->{'migrationsDir'}, "temp-before-$migrations[0].sql"));
# Test each migration:
for (my $i=0; $i<(scalar @migrations); $i++) {
  my $beforeFile = catfile($config->{'migrationsDir'}, "temp-before-$migrations[$i].sql");  
  my $afterFile  = catfile($config->{'migrationsDir'}, "temp-after-$migrations[$i].sql");
  
  # At each version, take a schema dump, then migrate up
  # one version and migrate down one version and take another schema dump.
  # Compare the schema dumps to see if the up and down migration offset each
  # other perfectly.
  &dumpSchema($beforeFile);
  &log("Migrate $migrations[$i] up");
  &mysqlSource(catfile($config->{'migrationsDir'},"$migrations[$i].up.sql"));  
  &log("Migrate $migrations[$i] down");
  &mysqlSource(catfile($config->{'migrationsDir'},"$migrations[$i].dn.sql"));
  &dumpSchema($afterFile);

  my $diff = `diff $beforeFile $afterFile`;
  if ($? != 0) {
    &log("At migration $migrations[$i], the up and down migrations don't offset each other perfectly. (Comparing $beforeFile and $afterFile)");
    &log("Here are the differences:");
    &log("\n$diff");
    &error("Migrations test failed");
  }
  
  # Cleanup temp files.
  unlink $beforeFile;
  unlink $afterFile;
  
  # If this migration was successful, then reapply the up migration in preparation for the next loop
  &mysqlSource(catfile($config->{'migrationsDir'},"$migrations[$i].up.sql"));  
}

# Dump the final schema into base.sql
&log("Updating your base.sql (A copy of your previous base.sql was saved in base.sql.bak)");
copy($baseSchemaFile, catfile($config->{'migrationsDir'}, 'base.sql.bak'));
&dumpSchema($baseSchemaFile);
open(BASESQL, ">>$baseSchemaFile") or &error("Could not append schema version to base.sql file");
print BASESQL "TRUNCATE TABLE schema_version;\n";
print BASESQL "INSERT INTO schema_version (schema_version) VALUES ($migrations[$#migrations]);\n";
close BASESQL;

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
					       --skip-extended-insert
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
                                           $config->{'tempSchema'}
                                           < $source";
  $command =~ s/\s+/ /g; # Reformat the command onto one line
  &debugLog($command);
  my $result = `$command`;
  if ($? != 0) {
    &error("Command did not execute successfully. See previous output for more info.");
  }
}
