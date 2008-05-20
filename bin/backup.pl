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

######################################################################
# Main entry point
######################################################################

my $timestamp = &timestamp();

# Read & parse the configuration file: $config is a hash reference
my $config = &getConfig();

# Create backup directory
&backupLog('Application directory is '.$config->{'appRoot'});
&backupError('Application directory does not exist') unless
  -d $config->{'appRoot'};
&backupError('backupRoot is not defined in the configuration file') unless
  defined $config->{'backupRoot'};
my $backupDir = catfile($config->{'backupRoot'},$timestamp);
# Create subdirectory for holding the source code
my $backupAppDir = catfile($backupDir,'app');
&backupError('The backup directory already exists') if
  -e $backupDir;
&backupLog("Creating directory for backup $backupDir");
mkdir($backupDir);
mkdir($backupAppDir); 

# Backup schema and copy files from the application root into the backup directory
&backupLog("Backing up schema");
&copySchema($config, $backupDir);
&backupLog("Backing up application");
&copyDir($config->{'appRoot'}, $backupAppDir);

# Create tar file and gzip it
if ($config->{'compress'} eq 'true') {
  &backupLog("Compressing backup into $timestamp.tgz");
  chdir($config->{'backupRoot'});
  qx/tar -czf $timestamp.tgz $timestamp/;
  rmtree($backupDir);
}

# Prune any old files
if ($config->{'retentionPeriod'} != 0) {
  &backupLog("Checking for expired backup files");
  &pruneBackups($config);
}

# Done
&backupLog("Backup complete");

######################################################################
# Subroutines
######################################################################

# Print log messages in a standard format, including a timestamp.
sub backupLog {
  my ($second, $minute, $hour, $mday, $month, $year, $wday, $yday, $isdst) = localtime();
  $year -= 100; # 2 digit year format
  $month  += 1;
  my $time = sprintf('[%02d:%02d:%02d %02d/%02d/%02d]',$hour,$minute,$second,$month,$mday,$year);
  print "$time @_\n";
}

# Prints a log message than exits with an error code
sub backupError {
  backupLog("ERROR: @_");
  exit 1;
}

# Loads the configuration from key=value pairs stored in the config file,
# and returns a hashref
sub getConfig {
  my %config;
  my $line = 0;
  my $configPath = catfile(dirname(realpath($0)),'backup-restore.cfg');
  &backupLog("Using config file $configPath");
  open(CONFIG, $configPath) or &backupError("No configuration file found! (Create \"backup-restore.cfg\" in the same directory as this script.)");
  while (<CONFIG>) {
    $line++;
    next if /^#/; # Ignore comment lines
    next if /^\s+$/; # Ignore blank lines
    
    if (m/^\s*(\S+)\s*=\s*(\S+)\s*/) { # Extract the key=value pair into $1 and $2
      $config{$1} = $2;
    } else {
      my $syntax = chomp;
      &backupError("Syntax error in configuration file on line $line: $syntax");
    }
  }
  if ($config{'debug'} eq 'true') {
    &backupLog(Dumper(\%config));
  }
  return \%config;
}

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
        &backupLog("Removing backup file $_");
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