#
# spec file for package openfisma
#
# norootforbuild

%define name openfisma
%define version 3.1.0
%define install_directory /usr/share/%{name}

Name:       %{name}
Version:    %{version}
Release:    1
Summary:    Web application for automating FISMA compliance
Group:      Productivity/Networking/Security
License:    GPL-3.0
URL:        http://www.openfisma.org
Source0:    OpenFISMA-%{version}.tgz
Source1:    %{name}.conf
Source5:    %{name}.cron
BuildRoot:  %{_tmppath}/%{name}-%{version}-build 
BuildArch:  noarch

# For more information on buildrequires and requires check out
# http://en.opensuse.org/openSUSE:Build_Service_cross_distribution_howto
# http://repo.mindtouch.com/home:mindtouch/MindTouch_10.0.1/mindtouch.spec

# REDHAT flavor specifications
%if 0%{?fedora} || 0%{?fedora_version} || 0%{?rhel_version} || 0%{?centos_version}
%define webuser apache
%define webgroup apache
%define apache httpd
%endif

# SUSE flavor specifications
%if 0%{?suse_version} || 0%{?sles_version}
%define webuser wwwrun
%define webgroup www
%define apache apache2
# If openSUSE version 12.2 do the following
%if 0%{?suse_version} == 1220
BuildRequires: java-1_7_0-openjdk
BuildRequires: mysql-community-server
Requires: java-1_7_0-openjdk
Requires: ImageMagick
Requires: mysql-community-server
%endif
# If openSUSE version 12.1 or 11.4 do the following
%if 0%{?suse_version} == 1210 || 0%{?suse_version} == 1140
BuildRequires: java-1_6_0-openjdk
BuildRequires: mysql-community-server
Requires: java-1_6_0-openjdk
Requires: ImageMagick
Requires: mysql-community-server
%endif
# If SLES version 11 do the following
%if 0%{?sles_version} == 11
BuildRequires: java-1_6_0-ibm
BuildRequires: mysql
Requires: java-1_6_0-ibm
Requires: libMagickCore
Requires: mysql
%endif
BuildRequires: apache2 
BuildRequires: fdupes  
BuildRequires: php5
BuildRequires: sudo
Requires: apache2
Requires: apache2-mod_php5
Requires: cron
Requires: logrotate
Requires: perl-Config-Crontab
Requires: php5
Requires: php5-apc
Requires: php5-bcmath
Requires: php5-ctype
Requires: php5-curl
Requires: php5-dom
Requires: php5-fileinfo
Requires: php5-hash
Requires: php5-iconv
Requires: php5-json
Requires: php5-ldap
Requires: php5-mbstring
Requires: php5-mysql
Requires: php5-openssl
Requires: php5-pdo
Requires: php5-pecl-solr
Requires: php5-sqlite
Requires: php5-tokenizer
Requires: php5-xmlreader
Requires: php5-xmlwriter
Requires: php5-zip
Requires: php5-zlib
%endif


%description
OpenFISMA is an open, customizable application sponsored by Endeavor Systems, Inc. that greatly reduces the cost and complexity associated with FISMA compliance and risk management for U.S. Federal agencies.

%prep

# unpack the source and cd into the source directory
%setup -q

# find and remove unwanted files
find . -type f -name '.DS_Store' -exec rm {} \;
find . -type f -name '.braids' -exec rm {} \;
find . -type f -name '.gitignore' -exec rm {} \;
find . -type f -name '.cvsignore' -exec rm {} \; 
find . -type f -name '._*' -exec rm {} \;

%build

%install

# disables brp-check-bytecode-version which throws an error on jar files
export NO_BRP_CHECK_BYTECODE_VERSION=true

# get openfisma files ready for installation
mkdir -p %{buildroot}/%{install_directory}
cp -avL * %{buildroot}/%{install_directory}

# get apache configuration files ready for installation
mkdir -p %{buildroot}/etc/%{apache}/vhosts.d/
cp -avL %{S:1} %{buildroot}/etc/%{apache}/vhosts.d/%{name}.conf

mkdir -p %{buildroot}/etc/init.d
ln -s %{install_directory}/scripts/rpm/openfisma_solr %{buildroot}/etc/init.d/openfisma_solr

mkdir -p %{buildroot}/etc/cron.d
cp -avL %{S:5} %{buildroot}/etc/cron.d/openfisma

%clean
rm -rf %{buildroot}

# The files list indicates to RPM which files on the build system are to be packaged.
%files
%defattr(-,root,root,-)
%attr(-,root,root) %{install_directory}
%config /etc/apache2/vhosts.d/openfisma.conf
%config /etc/cron.d/openfisma
%config /etc/init.d/openfisma_solr
%config /usr/share/openfisma/application/config/application.ini

# run the following scripts after installation of rpm
%post

# Check and update all permissions
sudo chown -R %{webuser}:%{webgroup} /usr/share/openfisma 
sudo find /usr/share/openfisma -type d -exec chmod 770 {} \;
sudo find /usr/share/openfisma -type f -exec chmod 660 {} \;

# if this is the first installation run the following
if [ "$1" == "1" ] ; then
echo "installing openfisma for the first time"

echo "enabling Apache rewrite module"
%{_sbindir}/a2enmod env
%{_sbindir}/a2enmod expires
%{_sbindir}/a2enmod log_config
%{_sbindir}/a2enmod mime
%{_sbindir}/a2enmod php5
%{_sbindir}/a2enmod rewrite
%{_sbindir}/a2enmod setenvif
%{_sbindir}/a2enmod ssl

# uncomment this section to generate SSL certificates
# echo "generating ssl certificates"
# sudo gensslcert -y 3650 -Y 3650 > /dev/null 2>&1

# autostart mysql, apache2, and solr
echo "enable autostart of mysql, apache, and solr"
%if 0%{?suse_version} >= 1210  
   systemctl enable apache2.service
   systemctl enable mysql.service
%else
   insserv apache2
   insserv mysql
   insserv openfisma_solr
%endif

# restar apache2, mysql, and solr
echo "restarting apache2, mysql, and solr"
%if 0%{?suse_version} >= 1210
   systemctl restart apache2.service
   systemctl restart mysql.service
%else
   /etc/init.d/apache2 reload
   /etc/init.d/mysql restart
   /etc/init.d/openfisma_solr start
%endif

echo "create database.ini file"
sudo -u %{webuser} cat > /usr/share/openfisma/application/config/database.ini << EOF
[production]
db.adapter = mysql
db.host = localhost
db.port = 3306
db.username = openfisma_app
db.password = ##DB_PASS##
db.schema = openfisma

[development : production]
EOF

# generates a random password for database.ini
echo "generate random password for openfisma application account"
openfisma_app_password=`dd if=/dev/urandom count=100 | tr -dc "A-Za-z0-9" | fold -w 20 | head -n 1`
sed --in-place "s/##DB_PASS##/$openfisma_app_password/" /usr/share/openfisma/application/config/database.ini

# mysql configuration
echo "setup MySQL permissions for openfisma database"
echo "grant all on openfisma.* to openfisma_app@localhost identified by '$openfisma_app_password'" | mysql -u root
echo "flush mysql privileges"
echo "flush privileges;" | mysql -u root

# build the openfisma database and load sample data
echo "build the openfisma database and load sample data"
php /usr/share/openfisma/scripts/bin/doctrine.php -bs
php /usr/share/openfisma/scripts/bin/generate-findings.php -n 50
php /usr/share/openfisma/scripts/bin/generate-vulnerabilities.php -n 50
php /usr/share/openfisma/scripts/bin/generate-incidents.php -n 50

# turns off APC and secure cookies
sed --in-place "s/resources.session.cookie_secure = true/resources.session.cookie_secure = false/" /usr/share/openfisma/application/config/application.ini
sed --in-place "s/cache_id_prefix = openfisma_ /cache_id_prefix = openfisma_test2_ /" /usr/share/openfisma/application/config/application.ini
sed --in-place "s/resources.cachemanager.default.backend.name = Apc/;resources.cachemanager.default.backend.name = Apc/" /usr/share/openfisma/application/config/application.ini
sed --in-place "s/;resources.cachemanager.default.backend.name = File/resources.cachemanager.default.backend.name = File/" /usr/share/openfisma/application/config/application.ini
sed --in-place "s/;resources.cachemanager.default.backend.options.cache_dir/resources.cachemanager.default.backend.options.cache_dir/" /usr/share/openfisma/application/config/application.ini

# finish installation scripts
fi

# force clean exit
exit 0

# if this is an upgrade then run the following
if [ "$1" == "2" ] ; then
echo "Upgrading OpenFISMA"

/usr/share/openfisma/scripts/bin/doctrine.php -m || true
/usr/share/openfisma/scripts/bin/migrate.php || true
/etc/init.d/mysql restart
/etc/init.d/openfisma_solr restart
/etc/init.d/apache2 try-restart

# finish upgrade scripts
fi
exit 0

%changelog
