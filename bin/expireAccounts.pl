#!/usr/bin/perl -w
######################################################################
#
# expireAccounts.pl
#
# This POSIX-only script (not Windows compatible) is used to expire
# inactive OS accounts. This script allows OpenFISMA instances to 
# implement the security control AC-2(3) specified in NIST SP 800-53.
#
# This will also lock accounts which have never logged in, since their
# last login date is undefined. When creating new accounts, ensure
# that the user logs into their account at least once on the same day.
#
# The script should run as root and the file permissions should be set
# so that the contents of the script can not be modified. Also, NEVER
# run the script directly from a subversion working copy, as any
# update of the respository could replace the script contents with
# unexpected code.
#
# Set the script to run nightly in the root crontab.
# 
# Author: Mark E. Haase
#
######################################################################

use strict;
use Cwd qw/realpath/;
use Data::Dumper;
use File::Basename;
use File::Spec::Functions;

require fisma;

######################################################################
# Main entry point
######################################################################

# Read & parse the configuration file: $config is a hash reference
our $config = &getConfig(catfile(dirname(realpath($0)),'expireAccounts.cfg'));

# Verify that the inactivePeriod configuration variable is actually a number
# to prevent shell expansion attacks.
my $inactivePeriod = $config->{'inactivePeriod'};
if (not ($inactivePeriod =~ /^[0-9]+$/)) {
  &error("The configuration item inactivePeriod is not a valid number: \"$inactivePeriod\"");
}
&log("Searching for accounts that have been inactive for $inactivePeriod days or more");

# Verify the lastlog and passwd commands are actual paths (containing 
# letters and forward slashes only) to prevent shell expansion attacks.
my $lastlog = $config->{'lastlog'};
if (not ($lastlog =~ /^[a-zA-Z\/]+$/)) {
  &error("The configuration item lastlog is not a valid path: \"$lastlog\"");
}
my $passwd = $config->{'passwd'};
if (not ($passwd =~ /^[a-zA-Z\/]+$/)) {
  &error("The configuration item passwd is not a valid path: \"$passwd\"");
}

# Execute a command to see who hasn't logged in within the inactivePeriod
my @lastlogOutput = `$lastlog -b $inactivePeriod 2>&1`;
if ($? != 0) {
  &error("Could not execute the lastlog command ($lastlog):\n\"@lastlogOutput\"");
}

# Analyze the lastlog output. For any accounts which have not logged in
# during the inactivePeriod, check if the account is already locked, and
# lock the account if it is not locked already. Keep track of which accounts
# get locked for reporting purposes.
my @lockedAccounts;
chomp @lastlogOutput;
shift @lastlogOutput; # The first line of output is column headers
foreach (@lastlogOutput) {
  # Parse the lastlog columns -- each row has up to four columns
  if (not /^(\S+)\s+(\S+)\s+(\S+)\s+(.*)$/) {
    &error("Unable to parse this line of output from lastlog ($lastlog): \"$_\"");
  }
  my $user = $1;
  my $port = $2;
  my $from = $3;
  my $latest = $4;
  
  # Skip any system accounts (accounts with uid <= 99)
  my $uid = getpwnam($user);
  if ($uid <= 99) {
    &log("Skipping system account $user (uid=$uid)");
    next;
  }
  
  # Make sure that the account isn't locked before trying to lock it
  my $passwdStatusOutput = `$passwd -S $user 2>&1`;
  if (($? % 255) != 1) { # passwd -S returns 1 or 256 for success
    chomp $passwdStatusOutput;
    &error("Could not execute the \"passwd -S\" command ($passwd):\n\"$passwdStatusOutput\"");
  }
  if (not ($passwdStatusOutput =~ /Password locked/)) {
    my $passwdLockOutput = `$passwd -l $user 2>&1`;
    if ($? != 0) {
      &error("Could not execute the \"passwd -l\" command ($passwd):\n\"$passwdLockOutput\"");
    } else {
      # For accounts that have never been used, the $port will be '**Never'
      my $lockDescription;
      if ($port eq '**Never') {
        $lockDescription = "Locked $user. (This account has never logged in before)";
        &log($lockDescription);
        unshift @lockedAccounts, $lockDescription;
      } else {
        $lockDescription = "Locked $user. (Last login was from $from on device $port on date $latest)";
        &log($lockDescription);
        unshift @lockedAccounts, $lockDescription;
      } 
    }
  } else {
    # The account is already locked, don't do anything:
    &log("Skipping locked account $user (uid=$uid)");
  }
}

# Send an e-mail to the administrator specifying which accounts were locked
if ((scalar @lockedAccounts) > 0) {
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
  
  
  open (MAILPIPE, "| mail -s \"$subject\" \"$adminEmail\"");
  print MAILPIPE $body;
  close MAILPIPE; 
}
  
# Done
&log("Done");

######################################################################
# Subroutines
######################################################################

