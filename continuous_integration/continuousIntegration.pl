#!/usr/bin/perl
######################################################################
#
# continuousIntegration.pl
#
# This is the CI listener script. This script wakes up at regular
# intervals (such as a scheduled cron job every 5 minutes) and checks
# to see if any new commits have occurred. If it finds new commits,
# then it kicks off a series of tests to run on the new commits.
#
# Author:    Mark E. Haase (mhaase@endeavorsystems.com)
# Project:   OpenFISMA
# Copyright: (c) 2008 Endeavor Systems, Inc.
# License:   http://www.openfisma.org/mw/index.php?title=License
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
our $config = &getConfig(catfile(dirname(realpath($0)),'continuous_integration.cfg'));

# Connect to the CI control schema and get the most recent version information
&log('Connecting to database '.$config->{'ciControlHost'}.':'.$config->{'ciControlSchema'});
my $db = DBI->connect('DBI:mysql:'.$config->{'ciControlSchema'}.':'.$config->{'ciControlHost'}, $config->{'ciControlUser'}, $config->{'ciControlPassword'});
my $query = "SELECT MAX(revision_id) max_revision FROM revision";
my $preparedQuery = $db->prepare($query);
$preparedQuery->execute() or &error("Unable to execute query: $query");
my $maxRevisionWorkingCopy = ($preparedQuery->fetchrow_hashref())->{'max_revision'};
if (not defined $maxRevisionWorkingCopy) {
  $maxRevisionWorkingCopy = -1;
}
&log("The max revision in the working copy is $maxRevisionWorkingCopy");

# Connect to the repository and update the CI control schema with any new
# version information.
my $revision = 'HEAD';
my $svninfo;
my $svnlog;
my $maxRevisionRepository = 0;
&log("Getting info for new revisions");
while ($revision eq 'HEAD' or $revision > $maxRevisionWorkingCopy) {
  &log("Revision: $revision");

  $svninfo = &svninfo($revision);
  # For 'HEAD', map the version to a revision number
  if ($revision eq 'HEAD') {
    &log("HEAD revision is $svninfo->{'Revision'}");
    $maxRevisionRepository = $svninfo->{'Revision'};
    last if $maxRevisionRepository == $maxRevisionWorkingCopy;
  }
  $svnlog = &svnlog($revision);
  
  # Sometimes svn log gives weird results. If the author of a revision is null,
  # then assume that there's no prior revision information.
  last if not defined $svnlog->{'author'};

  # Insert current revision information into the revision table.
  $query = "INSERT INTO revision (revision_id,
                                  author,
                                  commit_time,
                                  commit_message)
            VALUES ('$svnlog->{'revision'}',
                    '$svnlog->{'author'}',
                    str_to_date('$svnlog->{'date'}', '%Y-%m-%d %k:%i:%s'),
                    '$svnlog->{'message'}')";
  $preparedQuery = $db->prepare($query);
  $preparedQuery->execute() or &error("Unable to execute query: $query");
  $revision = $svninfo->{'Last Changed Rev'}-1;
}
&log("Completed new revisions");

# If a new version exists, kick off a test on that max version
&log("The working copy is at $maxRevisionWorkingCopy and the repository is at $maxRevisionRepository.");
if ($maxRevisionRepository == $maxRevisionWorkingCopy) {
  &log("Versions are equal...nothing to do.");
} else {
  &log("Kicking off test suite for revision $maxRevisionRepository");
}

######################################################################
# Subroutines
######################################################################

# Returns a hashref containing the output of the svn info command for
# the root of the working copy at the specified revision.
sub svninfo {
 (my $revision) = @_;
 
  # We need a URL to the repository, but the config specifies a path to the
  # working copy by default. On the first execution, we need to translate the
  # working copy path to a repository URL.
  if (not defined $config->{'repositorySubtree'}) {
    $config->{'repositorySubtree'} = &workingCopyPathToURL($config->{'workingCopyRoot'});
  }

  # Execute the svn info command and parse out the response
  my $svninfoOutput = `svn info -r$revision $config->{'repositorySubtree'} 2>&1`;
  if ($? != 0) {&error("Unable to execute \"svn info\": $svninfoOutput")}
  my @svninfoLines = split('\n', $svninfoOutput);
  my %svninfo = ();

  foreach (@svninfoLines) {
    /^\s*(.*?)\s*:\s*(.*)\s*$/;
    if (defined $2) {
      $svninfo{$1} = $2;
    }
  }

  return \%svninfo;
}

# Returns a hashref containing the output of the svn log command for
# the root of the working copy at the specified revision.
sub svnlog {
 (my $revision) = @_;

  # Execute the svn log command and parse out the response
  my $svnlogOutput = `svn log -r$revision $config->{'workingCopyRoot'} 2>&1`;
  if ($? != 0) {&error("Unable to execute \"svn log\": $svnlogOutput")}
  my @svnlogLines = split('\n', $svnlogOutput);
  my %svnlog = ();

  my $logMessage = "";
  my @tokens = ();
  foreach (@svnlogLines) {
    next if /^-+$/;
    if (/^r\d+ \| /) {
      # Split on the pipe character and trim all array items
      @tokens = map {s:^\s+|\s+$::g; $_} split('\|',$_);
      $tokens[0] =~ s/^r//;
      $tokens[2] = join(' ',(split(' ',@tokens[2]))[0,1]);
    } else {
      $logMessage .= "$_\n";
    }
  }
  # Trim the log message.
  $logMessage =~ s:^\s+|\s+$::g;
  # Escape any quotation marks or semicolons
  $logMessage =~ s:([\"';]):\\\1:g;
  
  $svnlog{'revision'} = $tokens[0];
  $svnlog{'author'}   = $tokens[1];
  $svnlog{'date'}     = $tokens[2];
  $svnlog{'message'}  = $logMessage;

  return \%svnlog;
}

# Converts a working copy path to a repository URL by using the svn info command.
sub workingCopyPathToURL {
 (my $workingCopyPath) = @_;

  # Execute the svn info command and parse out the response
  my $svninfoOutput = `svn info -rHEAD $workingCopyPath 2>&1`;
  if ($? != 0) {&error("Unable to execute \"svn info\": $svninfoOutput")}
  my @svninfoLines = split('\n', $svninfoOutput);
  my %svninfo = ();

  foreach (@svninfoLines) {
    /^\s*(.*?)\s*:\s*(.*)\s*$/;
    if (defined $2) {
      $svninfo{$1} = $2;
    }
  }

  return $svninfo{'URL'};
}