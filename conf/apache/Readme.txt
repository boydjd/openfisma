This directory contains hardened apache configuration files for openfisma to operate correctly. Below is a description of the directory contents and how the files should be used. 

---
The conf directory is where apache loads the default configuration file, on RHEL the default directory is "/etc/httpd/conf". You should copy this file to this directory or the appropriate directory for other operating system versions. Debian users will want to copy httpd.conf to "/etc/apache2/conf".
/conf		
  http.conf				-	Default Apache configuration file

---
The conf.d directory is where apache loads additional conf include files after the default configuration is loaded. For RHEL users you will want to copy the contents of this directory to "/etc/httpd/conf.d/". Be sure to remove openfisma_test.conf.
/conf.d
  openfisma.conf		-	OpenFISMA configuration file for apache
  openfisma_test.conf	-	Test configuration file for apache
  php.conf				- 	PHP configuration file for apache
  ports.conf			- 	Tells apache which ports to listen on
  ssl.conf				-	SSL configuration file for apache