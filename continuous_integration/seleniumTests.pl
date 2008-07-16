#!/usr/bin/perl
######################################################################
#
# seleniumTests.pl
#
# Executes a batch of Selenium tests (defined in the 
# continuous_integration.cfg file) by running a headless instance
# of firefox.
#
# Author:    Mark E. Haase (mhaase@endeavorsystems.com)
# Project:   OpenFISMA
# Copyright: (c) 2008 Endeavor Systems, Inc.
# License:   http://www.openfisma.org/mw/index.php?title=License
#
######################################################################

use strict;
use Cwd qw/realpath/;
use File::Basename;
use File::Spec::Functions;

require fisma;

######################################################################
# Main entry point
######################################################################

# Read & parse the configuration file: $config is a hash reference
our $config = &getConfig(catfile(dirname(realpath($0)),'continuous_integration.cfg'));

# Start Xvfb if it is not already running.
my $xvfbIsRunning = 0;
foreach (`ps aux`) {
  /^.{65}(.*)$/;
  my $process = $1;
  my @processTokens = split(' ', $process);
  if ($processTokens[0] eq "Xvfb") {
    &debugLog("Xvfb is already running: \"$process\"");
    $xvfbIsRunning = 1;
    $process =~ /(:\d+)/;
    my $display = $1;
    if (not defined $display) {
      &error("Xvfb is running, but it's display is unknown. Terminate Xvfb and try again.");
    }
    &debugLog("Xvfb's display is $1");
  }
}
if (not $xvfbIsRunning) {
  my $pid = fork();
  if ($pid == 0) {
    `Xvfb :1 -screen 0 1024x768x24`;
    exit;
  } else {
    $ENV{'DISPLAY'} = ':1';
    &debugLog("Waiting 5 seconds for Xvfb to start up");
    sleep 5;
  }
}

# Use firefox to run the Selenium tests.
my $url = "$config->{'testRunnerURL'}?test=/test/cases/$config->{'testCase[]'}.html".
          "&resultsUrl=/ci/postSeleniumResults.php&auto=true&close=true&highlight=true";
&log("Launching firefox with URL $url");
my $pid = fork();
if ($pid == 0) {
  `firefox -height 1024 -width 768 -new-tab "$url"`;
  exit;
}

######################################################################
# Subroutines
######################################################################
