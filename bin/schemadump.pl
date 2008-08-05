#!/usr/bin/perl
######################################################################
#
# schemadump.pl
#
# This script dumps the schema named in the schema.cfg file (in the
# same directory as this script) into a file called schema.sql (also
# in this directory. It does not dump any data except for the schema
# version.
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
######################################################################

use strict;
use Cwd qw/realpath/;
use Data::Dumper;
use DBI;
use File::Basename;
use File::Spec::Functions;

require fisma;

######################################################################
# Main entry point
######################################################################

# Read & parse the configuration file: $config is a hash reference
my $config = &getConfig(catfile(dirname(realpath($0)),'schema.cfg'));

# Get current schema version
&log('Connecting to database '.$config->{'dbHost'}.':'.$config->{'dbSchema'});
my $db = DBI->connect('DBI:mysql:'.$config->{'dbSchema'}.':'.$config->{'dbHost'}, $config->{'dbUser'}, $config->{'dbPassword'})
  or &error("Unable to connect to the database");
my $query = "SELECT MAX(schema_version) schema_version FROM schema_version LIMIT 1";
my $dbq = $db->prepare($query) or &error("Could not prepare query \"$query\"");
$dbq->execute() or &error("Could not execute query \"$query\"");
my $database = $dbq->fetchrow_hashref();
if (not defined $database->{'schema_version'}) {&error("No schema version found in \"$config->{'dbSchema'}\" schema")}
my $schemaVersion = $database->{'schema_version'};

# Dump schema into file
&log("Dumping schema");
my $outputFile = catfile(dirname(realpath($0)), 'schema.sql');
open (DUMPFILE, ">$outputFile");
my $command = "$config->{'mysqldumpCommand'} -h $config->{'dbHost'} 
                                             -u $config->{'dbUser'} 
                                             -p$config->{'dbPassword'}
                                             --compact
                                             --no-data
                                             $config->{'dbSchema'}";
$command =~ s/\s+/ /g; # Reformat the command onto one line
&debugLog($command);
my $schemaDump = `$command`;
if ($? != 0) {
  &error("Command did not execute successfully. See previous output for more info.");
}
print DUMPFILE $schemaDump;
# Add schema version to dump file
print DUMPFILE "INSERT INTO schema_version VALUES ($schemaVersion);\n";
&log("Done");