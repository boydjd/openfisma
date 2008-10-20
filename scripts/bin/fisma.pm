######################################################################
#
# fisma.pm
#
# A collection of routines used through the OpenFISMA perl scripts.
# 
# Author: Mark E. Haase
#
######################################################################

use Data::Dumper;

# Print log messages in a standard format, including a timestamp.
sub log {
  my ($second, $minute, $hour, $mday, $month, $year, $wday, $yday, $isdst) = localtime();
  $year -= 100; # 2 digit year format
  $month  += 1;
  my $time = sprintf('[%02d:%02d:%02d %02d/%02d/%02d]',$hour,$minute,$second,$month,$mday,$year);
  my $message = "$time @_";
  # Reformat new line characters to indent properly
  my $space = ' ' x (length($time)+1);
  $message =~ s/\n\s*/\n$space/g;
  print "$message\n";
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
 (my $configPath) = @_;
  my %config;
  my $line = 0;
  &log("Using config file $configPath");
  open(CONFIG, $configPath) or &error("No configuration file found! (Create $configPath.)");
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

# Prompt the user for a response
sub prompt {
 (my $message, my @options) = @_;
  print "$message (".join('/', @options).")> ";
  my $response = <STDIN>;
  chomp $response;
  foreach (@options) {
    if ($_ eq $response) {return $response}
  }
  # If the user didn't type an appropriate response, then recurse
  return &response(@_);
}

1;