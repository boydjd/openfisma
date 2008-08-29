#!/usr/bin/perl
######################################################################
#
# backup.pl
#
# This is a script to take a backup of an OpenFISMA application
# instance. The script makes a copy of all source code files and also
# produces a schema dump. The backup is tar'ed and gzip'ed.
# 
# Before running this script, make sure to edit the
# backup-restore.cfg file to specify the proper database access
# properties.
#
# The script is designed to run in a POSIX environment, but may run
# under windows if a compatible mysqldump and tar executable exists
# in the path.
#
# Author: Mark E. Haase
#
######################################################################

use strict;
use Cwd qw/realpath/;
use Data::Dumper;
use File::Basename;
use File::Copy;
use File::Glob;
use File::Path;
use File::Spec::Functions;
use File::stat;

require fisma;

######################################################################
# Main entry point
######################################################################

my $timestamp = &timestamp();

# Read & parse the configuration file: $config is a hash reference
our $config = &getConfig(catfile(dirname(realpath($0)),'backup-restore.cfg'));

# Create backup directory
&log('Application directory is '.$config->{'appRoot'});
&error('Application directory does not exist') unless
  -d $config->{'appRoot'};
&error('backupRoot is not defined in the configuration file') unless
  defined $config->{'backupRoot'};
my $backupDir = catfile($config->{'backupRoot'},$timestamp);
# Create subdirectory for holding the source code
my $backupAppDir = catfile($backupDir,'app');
&error('The backup directory already exists') if
  -e $backupDir;
&log("Creating directory for backup $backupDir");
mkdir($backupDir);
mkdir($backupAppDir); 

# Backup schema and copy files from the application root into the backup directory
&log("Backing up schema");
&copySchema($config, $backupDir);
&log("Backing up application");
&copyDir($config->{'appRoot'}, $backupAppDir);

# Create tar file and gzip it
if ($config->{'compress'} eq 'true') {
  &log("Compressing backup into $timestamp.tgz");
  chdir($config->{'backupRoot'});
  qx/tar -czf $timestamp.tgz $timestamp/;
  rmtree($backupDir);
}

# Prune any old files
if ($config->{'retentionPeriod'} != 0) {
  &log("Checking for expired backup files");
  &pruneBackups($config);
}

# Done
&log("Backup complete");

######################################################################
# Subroutines
######################################################################

# Dumps a copy of the specified schema into a file inside the backup directory.
sub copySchema {
 (my $config, my $backupDir) = @_;
  my @schema = qx/mysqldump --user="$config->{'dbUser'}" --password="$config->{'dbPassword'}" --add-drop-database  --compact $config->{'dbSchema'}/;
  my $schemaFile = catfile($backupDir,'schema.sql');
  open (SCHEMA_FILE, ">$schemaFile");
  print SCHEMA_FILE @schema;
}

# Copies directory recursively from $source to $target
sub copyDir {
 (my $source, my $target) = @_;
  opendir (DIR, $source);
  my @files = readdir(DIR);
  foreach (@files) {
    next if m/^\./; #ignore directories with a "." prefix (includes . and ..)
    my $sourceFullPath = catfile($source,$_);
    my $targetFullPath = catfile($target,$_);
    if (-d $sourceFullPath) {
      mkdir($targetFullPath);
      &copyDir($sourceFullPath, $targetFullPath);
    } else {
      copy($sourceFullPath, $targetFullPath);
    }
  }
  close DIR; 
}

# Checks for expired backup files and removes them. Currently doesn't do anything
# for directories.
sub pruneBackups {
 (my $config) = @_;
  opendir (DIR, $config->{'backupRoot'});
  my $now = time;
  # Convert retentionPeriod from days to seconds
  my $offset = $config->{'retentionPeriod'} * 24 * 60 * 60;
  my @files = readdir(DIR);
  foreach (@files) {
    next if m/^\./; #ignore directories with a "." prefix (includes . and ..)
    my $fullPath = catfile($config->{'backupRoot'},$_);
    if (stat($fullPath)->mtime < ($now - $offset)) {
      if (not -d $fullPath) {
        &log("Removing backup file $_");
        unlink $fullPath; # TODO doesn't do anything for directories
      }
    }
  }
  closedir DIR;
}

# Produces a YYYYMMDDHHMMSS timestamp to label the backup archive with.
sub timestamp {
  my ($second, $minute, $hour, $mday, $month, $year, $wday, $yday, $isdst) = localtime();
  $year += 1900;
  $month  += 1;
  return sprintf('%04d%02d%02d%02d%02d%02d',$year,$month,$mday,$hour,$minute,$second);
}