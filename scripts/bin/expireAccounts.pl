#!/usr/bin/perl
################################################################################
#
# expireAccounts.pl
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
# expireAccounts.pl [--dryrun]
#
# This POSIX-only script (not Windows compatible) is used to expire
# inactive OS accounts. This script allows OpenFISMA instances to
# implement the security control AC-2(3) specified in NIST SP 800-53.
#
# This will also lock accounts which have never logged in, since their
# last login date is undefined, so make sure that users have at least
# one login on the day that their account is created.
#
# The script should run as root and the file permissions should be set
# so that the contents of the script can not be modified.
#
# The --dryrun parameter evaluates which accounts should be locked but
# does not actually lock them. Use this to see what the effects of the
# script will be before actually committing those effects.
#
# Set the script to run nightly in the root crontab.
#
# Author:    Mark E. Haase <mhaase@endeavorsystems.com>
# Copyright: (c) 2008 Endeavor Systems, Inc. (http://www.endeavorsystems.com)
# License:   http://www.openfisma.org/mw/index.php?title=License
# Version:   $Id$
#
################################################################################

#use strict;
use Cwd qw/realpath/;
use Data::Dumper;
use File::Basename;
use File::Spec::Functions;
use Getopt::Long;

require fisma;

######################################################################
# Main entry point
######################################################################

# Read & parse the configuration file: $config is a hash reference
our $config = &getConfig(catfile(dirname(realpath($0)),'expireAccounts.cfg'));

# We support one command line flag: --dryrun. It defaults to false.
my $dryRun = '';
GetOptions('dryrun' => \$dryRun);

# Verify that the inactivePeriod configuration variable is actually a number
# to prevent shell expansion attacks.
my $inactivePeriod = $config->{'inactivePeriod'};
if (not ($inactivePeriod =~ /^[0-9]+$/)) {
  &error("The configuration item inactivePeriod is not a valid number: \"$inactivePeriod\"");
}
&log("Searching for accounts that have been inactive for $inactivePeriod days or more");

# Verify the passwd command is an actual path (containing 
# letters and forward slashes only) to prevent shell expansion attacks.
my $passwd = $config->{'passwd'};
if (not ($passwd =~ /^[a-zA-Z\/]+$/)) {
  &error("The configuration item passwd is not a valid path: \"$passwd\"");
}

# Load the last log into a hashref
my $lastlog = &loadLastlog($config->{'lastlog'});

# Analyze the lastlog output. For any accounts which have not logged in
# during the inactivePeriod, check if the account is already locked, and
# lock the account if it is not locked already. Keep track of which accounts
# get locked for reporting purposes.
my @lockedAccounts;
foreach my $uid (keys %$lastlog) {
  my $inactivePeriodSeconds = $inactivePeriod * 24 * 60 * 60; # Convert period in days to period in seconds
  my $accountInactiveSeconds = time() - $lastlog->{$uid}->{'time'};
  if ($inactivePeriodSeconds <= $accountInactiveSeconds || $accountInactiveSeconds == 0) {
    # Skip any system accounts (accounts with uid <= 99)
    if ($uid <= 99) {
      &log("Skipping system account $lastlog->{$uid}->{'uname'} (uid=$uid)");
      next;
    }
    
    # Make sure that the account isn't locked before trying to lock it
    my $passwdStatusOutput = `$passwd -S $lastlog->{$uid}->{'uname'} 2>&1`;
    if (($? % 255) != 1) { # passwd -S returns 1 or 256 for success
      chomp $passwdStatusOutput;
      &error("Could not execute the \"passwd -S\" command ($passwd):\n\"$passwdStatusOutput\"");
    }
    if (not ($passwdStatusOutput =~ /Password locked/)) {
      my $passwdLockOutput = '';
      if (not $dryRun) {
        $passwdLockOutput = `$passwd -l $lastlog->{$uid}->{'uname'} 2>&1`;
      } else {
        $passwdLockOutput = `true`; # In dry run mode, just execute a safe command instead of locking the account
      }
      if ($? != 0) {
        &error("Could not execute the \"passwd -l\" command ($passwd):\n\"$passwdLockOutput\"");
      } else {
        # For accounts that have never been used, the $accountInactiveSeconds will be zero
        my $lockDescription;
        if ($lastlog->{$uid}->{'time'} == 0) {
          $lockDescription = "Locked $lastlog->{$uid}->{'uname'}. (This account has never logged in before)";
          &log($lockDescription);
          unshift @lockedAccounts, $lockDescription;
        } else {
          $lockDescription = "Locked $lastlog->{$uid}->{'uname'}. (Last login was from $lastlog->{$uid}->{'host'} on device $lastlog->{$uid}->{'device'} on date $lastlog->{$uid}->{'htime'})";
          &log($lockDescription);
          unshift @lockedAccounts, $lockDescription;
        }
      }
    } else {
      # The account is already locked, don't do anything:
      &log("Skipping locked account $lastlog->{$uid}->{'uname'} (uid=$uid)");
    }
  }
}

# Send an e-mail to the administrator specifying which accounts were locked
if ((scalar @lockedAccounts) > 0 && not $dryRun) {
  my $adminEmail = $config->{'adminEmail'};
  my $lockedAccountsDesc = '';
  foreach (@lockedAccounts) {$lockedAccountsDesc = $lockedAccountsDesc . $_ . "\n"}
  my $hostname = `hostname`;
  chomp $hostname;
  my $subject = "Accounts locked after $inactivePeriod days of inactivity on $hostname";
  my $body = <<MAILBODY;
In accordance with system policy and configuration for $hostname, 
a daily scan of inactive accounts resulted in the following account[s] being 
locked after $inactivePeriod days of inactivity:

-------------------------------------------------------------------

$lockedAccountsDesc
-------------------------------------------------------------------

These accounts should be reviewed to determine if they are still necessary. 
Accounts which are not needed should be permanently removed by the administrator.

(This e-mail was automatically generated by a daily script. Do not reply to
this address.)

MAILBODY
  
  
  open (MAILPIPE, "| mail -s \"$subject\" \"$adminEmail\"") or &error("Unable to send summary e-mail to $adminEmail -- couldn't open mail pipe.");
  print MAILPIPE $body;
  close MAILPIPE;

  &log("Summary: " . scalar(@lockedAccounts) . " accounts were locked and $adminEmail has been notified.");
} elsif ((scalar @lockedAccounts) > 0) {
  &log("Summary: " . scalar(@lockedAccounts) . " accounts are expired, but none were locked because the script is in dryrun mode.");
} else {
  &log("Summary: No accounts were locked.");
}
  
# Done
&log("Done");

######################################################################
# Subroutines
######################################################################

# Loads the specified last log into a hasharray and returns the reference
sub loadLastlog {
 (my $lastlogFile) = @_;
  my %lastlog;
  
  # Fetch the file into a single string
  open (LASTLOG, $lastlogFile) or &error("Unable to open the lastlog file ($lastlogFile)");
  $recs = ""; 
  while (<LASTLOG>) {$recs .= $_}
  
  # Iterate through each uid until EOF
  $uid = -1;
  foreach (split(/(.{292})/s,$recs)) {
    # Skip loop if the record is null
    next if length($_) == 0;
    $uid++;
    
    # Skip loop if this user doesn't exist
    my $username = getpwuid($uid);
    next if not defined $username;
    
    # Create hash entries:
    my %logRow;
    my ($time,$device,$host) = $_ =~ /(.{4})(.{32})(.{256})/;
    $logRow{'uname'} = $username;
    $logRow{'time'} = scalar(unpack("I4",$time)); # Last login time in seconds since epoch
    $logRow{'htime'} = scalar(localtime(unpack("I4",$time))); # Human-readable last login time
    $device =~ s/\x00+//g;
    $logRow{'device'} = $device;
    $host =~ s/\x00+//g;
    $logRow{'host'} = $host;
    $lastlog{$uid} = \%logRow;
  }

  return \%lastlog;
}
