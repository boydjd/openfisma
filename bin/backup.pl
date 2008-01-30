#!/usr/bin/perl

# ---------------------------------------------------------------------
#
# REQUIRES / USES
#
# ---------------------------------------------------------------------

require warnings;
require strict;


# ---------------------------------------------------------------------
#
# CONSTANTS
#
# ---------------------------------------------------------------------

use constant DB_ADMIN_USER => 'ovms';
use constant DB_ADMIN_PASS => '1qaz@WSX';
use constant DB_NAME       => 'ovms';


use constant MAX_ARCHIVE_AGE => 7;


# ---------------------------------------------------------------------
#
# LOCAL VARIABLES
#
# ---------------------------------------------------------------------

my $app_root = '/opt/endeavor/';


# ---------------------------------------------------------------------
#
# SUBROUTINES
#
# ---------------------------------------------------------------------

sub daystamp {
# Name     : daystamp()
# Purpose  : creates the YYYYMMDD stamp for backups
# Requires : none
# Input    : none
# Output   : YYYYMMDD formatted daystamp
		
    # grab the local time
    my ($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime time;

	# date corrections
	$year += 1900;
	$mon  += 1;

    # add the year
    my $date = $year;

    # add the month
	$date .= ($mon < 10)  ? '0'.$mon  : $mon;

    # add the day
    $date .= ($mday < 10) ? '0'.$mday : $mday;

    # return the conconction
    return ($date);

} # daystamp()


# ---------------------------------------------------------------------
#
# ARCHIVE SUBROUTINES
#
# ---------------------------------------------------------------------

sub get_archives {
# Name     : get_archives()
# Purpose  : retrieves a list of archives that are present
# Requires : none
# Input    : $backup_dir   - directory containing archives (string)
# Output   : @backup_files - array containing file names

	# shift off the parameters
	my $archive_dir = shift @_; 

	# verify that directory exists
	if (! -d $archive_dir) { print 'directory does not exist' and exit; }
	
	# directory exists, so get file list
	opendir(DIR, $archive_dir);
	my @files = readdir(DIR);
	closedir(DIR);
	
	# loop through and grab on the backup files
	my @archivess;
	foreach (@files) { 
	
		# match the file name against our standard name (YYYYMMDD---ovms---backup.tgz)
		if (/^[0-9]{8}---ovms---backup.tar.gz$/) { push (@archives, $_); }
		
	}
	
	# return the file list
	return @archives;

} # sub get_archives()


sub print_archives {
# Name     : print_archives()
# Purpose  : prints out the array of archives
# Requires : none
# Input    : @archives - list of archive files 
# Output   : none

	# loop through the archive list and print
	foreach (@_) { print; print "\n"; }

} # sub print_archives()


sub purge_archives {
# Name     : purge_archives()
# Purpose  : purges any archive older than $age days old
# Requires : none
# Input    : $today    - date from which to work with $age
#            $age      - number of days old to purge files after
#            @archives - list of archive files 
# Output   : array of remaining files

	# grab the archives list
	my $today       = shift @_;
	my $age         = shift @_;
	my $archive_dir = shift @_;
	my @archives    = @_;

	# loop through each archive and check its age
	foreach my $archive (@archives) {

		# match against the date
		$archive =~ /^([0-9]{8})/;

		#
		$date = $1;
		
		# remove our older archives
		if (($today - $date) > AGE) { system("rm $archive_dir$archive"); }
	
	}

	# return the results
	return @archives;

} # sub purge_archives()


sub create_archive {
# Name     : create_archive()
# Purpose  : dumps the database and copies the evidence to a temp dir
#            and archives them
# Requires : none
# Input    : $today    - the date for the daystamp
#            $app_root - our base application installation
# Output   : nothing

	# grab the parameters
	my $today          = shift @_;
	my $db_admin_user  = shift @_;
	my $db_admin_pass  = shift @_;
	my $db_name        = shift @_;
	my $archive_dir    = shift @_;
	
	# set the temporary archive name
	my $archive_name = $today.'---ovms---backup'; 

	# change to the current working directory
	chdir($archive_dir);

	# delete archive directory if it exists before creating it new
	if (-d $archive_name) { rmdir($archive_name); }
	mkdir($archive_name);

	# dump the database 
	system ("mysqldump -ic --add-drop-database --add-drop-table --skip-lock-tables ".
			"-u ".$db_admin_user." --password=".$db_admin_pass." $db_name ".
			"> ".$archive_name.'/'.$today."---ovms---database.sql");

	# copy the evidence
	system("cp ../www/evidence/* $archive_name/");

	# tar and compress the directory into an archive
	system("tar cfz $archive_name.tar.gz $archive_name/*");

	# remove the temporary directory
	system("rm -rf $archive_name");

} # create_archive()


# ---------------------------------------------------------------------
#
# DEBUG BLOCK
#
# ---------------------------------------------------------------------



# ---------------------------------------------------------------------
#
# MAIN LOGIC BLOCK
#
# ---------------------------------------------------------------------

# grab the current date, and set the archive directory
my $today = daystamp();
my $archive_dir = $app_root.'backup/';

# get list of existing archive
my @archives = get_archives($archive_dir);

# purge files older than 7 days
@archives = purge_archives($today, MAX_ARCHIVE_AGE, $archive_dir, @archives);

# create the new archive
create_archive($today, DB_ADMIN_USER, DB_ADMIN_PASS, DB_NAME, $archive_dir);
