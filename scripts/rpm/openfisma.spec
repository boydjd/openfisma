#
# spec file for package openfisma
#
# norootforbuild
#
# TODO LIST
# - configure the upgrade section and test upgrading from one version to the next
# - symlink all configuration file into an /etc/openfisma directory
# - check if ssl certs already exist, if not then generate them, if so then leave them alone
# - tag user documentation
# - configure and test rpm for RHEL/CentOS

%define installation_dir /usr/share/%{name}

Name:       openfisma
Version:    3.1.0
Release:    1
Summary:    Web application for automating FISMA compliance
Group:      Productivity/Networking/Security
License:    GPL-3.0
URL:        http://www.openfisma.org
Source0:    OpenFISMA-%{version}.tgz
BuildArch:  noarch
BuildRoot:  %{_tmppath}/%{name}-%{version}-build

# If REDHAT based operating system do the following
%if 0%{?fedora} || 0%{?fedora_version} || 0%{?rhel_version} || 0%{?centos_version}
%define webuser apache
%define webgroup apache
%define apache httpd
%define mysql mysqld
%define apache_conf_location /etc/httpd/conf.d
%define platform rhel

# If Fedora version 17 use the following packages
%if 0%{?fedora} == 17 
BuildRequires: java-1.7.0-openjdk-devel
Requires: java-1.7.0-openjdk
%endif

%if 0%{?fedora_version} <= 16 || 0%{?rhel_version} || 0%{?centos_version}
BuildRequires: java-1.6.0-openjdk-devel
Requires: java-1.6.0-openjdk
%endif

# Packages required by all REDHAT based operating systems
BuildRequires: httpd
BuildRequires: mysql-server
BuildRequires: php
BuildRequires: sudo
BuildRequires: ant
Requires: cronie
Requires: logrotate
Requires: httpd
Requires: mysql
Requires: mysql-server
Requires: php
Requires: php-bcmath
Requires: php-ldap
Requires: php-mbstring
Requires: php-mysql
Requires: php-pdo
Requires: php-xml
Requires: sudo
%endif

# If SUSE based operating system do the following
%if 0%{?suse_version} || 0%{?sles_version}
%define webuser wwwrun
%define webgroup www
%define apache apache2
%define mysql mysql
%define apache_conf_location /etc/apache2/vhosts.d
%define platform suse

# If openSUSE version 12.2 use the following packages
%if 0%{?suse_version} == 1220
#BuildRequires: java-1_7_0-openjdk
BuildRequires: mysql-community-server
Requires: java-1_7_0-openjdk
Requires: ImageMagick
Requires: mysql-community-server
%endif

# If openSUSE version 12.1 or 11.4 use the following packages
%if 0%{?suse_version} == 1210 || 0%{?suse_version} == 1140
#BuildRequires: java-1_6_0-openjdk
BuildRequires: mysql-community-server
Requires: java-1_6_0-openjdk
Requires: ImageMagick
Requires: mysql-community-server
%endif

# If SLES version 11 use the following packages
%if 0%{?sles_version} == 11
BuildRequires: java-1_6_0-ibm
BuildRequires: mysql
Requires: java-1_6_0-ibm
Requires: libMagickCore
Requires: mysql
%endif

# Packages required by all SUSE based operating systems
BuildRequires: apache2
BuildRequires: php5
BuildRequires: sudo
BuildRequires: ant
Requires: apache2
Requires: apache2-mod_php5
Requires: cron
Requires: logrotate
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
Requires: sudo
%endif

%description
OpenFISMA is an open, customizable application sponsored by Endeavor Systems, Inc. that greatly reduces the cost and complexity associated with FISMA compliance and risk management for U.S. Federal agencies.

%prep
%setup -q
%build

# turns off APC and secure cookies
sed --in-place "s/resources.session.cookie_secure = true/resources.session.cookie_secure = false/" application/config/application.ini
sed --in-place "s/cache_id_prefix = openfisma_ /cache_id_prefix = openfisma_test2_ /" application/config/application.ini
sed --in-place "s/resources.cachemanager.default.backend.name = Apc/;resources.cachemanager.default.backend.name = Apc/" application/config/application.ini
sed --in-place "s/;resources.cachemanager.default.backend.name = File/resources.cachemanager.default.backend.name = File/" application/config/application.ini
sed --in-place "s/;resources.cachemanager.default.backend.options.cache_dir/resources.cachemanager.default.backend.options.cache_dir/" application/config/application.ini

# find and remove unwanted files while in source directory
find . -type f -name '.DS_Store' -exec rm {} \;
find . -type f -name '.braids' -exec rm {} \;
find . -type f -name '.gitignore' -exec rm {} \;
find . -type f -name '.cvsignore' -exec rm {} \; 
find . -type f -name '._*' -exec rm {} \;

# minify the css files
cd scripts/build
ant minify

# The install section is executed as a sh script, just like prep and build. 
%install

# disables brp-check-bytecode-version which throws an error on jar files
export NO_BRP_CHECK_BYTECODE_VERSION=true

# create openfisma installation directory to represent how the files should be installed into the target system. 
%{__mkdir_p} %{buildroot}/%{installation_dir}

# copy all of the files from the source directory into the new installation directory
cp -rp * %{buildroot}/%{installation_dir}

# Copy configuration files from current location into location of where they should be installed
%{__mkdir_p} %{buildroot}%{apache_conf_location}
%{__mkdir_p} %{buildroot}/etc/init.d/
%{__mkdir_p} %{buildroot}/etc/cron.d/
cp -rp %{buildroot}%{installation_dir}/scripts/rpm/openfisma_%{apache} %{buildroot}%{apache_conf_location}/%{name}.conf
cp -rp %{buildroot}%{installation_dir}/scripts/rpm/openfisma_solr_%{platform} %{buildroot}/etc/init.d/solr
cp -rp %{buildroot}%{installation_dir}/scripts/rpm/openfisma_cron %{buildroot}/etc/cron.d/openfisma

# By adding a sh script to the %clean section, such situations can be handled gracefully, right after the binary package is created.
%clean

# The %files section is different from the others, in that it contains a list of the files that are part of the package. Always remember — if it isn't in the file list, it won't be put in the package!
%files
%defattr(-,root,root,-)
%{installation_dir}
%config %{apache_conf_location}/openfisma.conf
%config /etc/cron.d/openfisma
%config /etc/init.d/solr
%config %{installation_dir}/application/config/application.ini

# run the following scripts after installation of rpm
%post

# check to see if apache user is in the sudoers file, if not add it
if grep "^%{webuser}.*ALL=NOPASSWD:.*/usr/sbin/%{apache}" /etc/sudoers > /dev/null ; then
    echo "sudo active"
else
    echo "%{webuser} ALL=NOPASSWD:/usr/sbin/%{apache}" >> /etc/sudoers
fi

# Check and update all permissions
find %{installation_dir} -type d -exec chmod 770 {} \;
find %{installation_dir} -type f -exec chmod 660 {} \;
chown -R %{webuser}:%{webgroup} %{installation_dir} 
chmod 755 /etc/init.d/solr

# if this is the first installation run the following
if [ "$1" == "1" ] ; then
echo "installing openfisma for the first time"

# only applies to suse/debian based operating systems
echo "enabling Apache rewrite module"
%{_sbindir}/a2enmod env
%{_sbindir}/a2enmod expires
%{_sbindir}/a2enmod log_config
%{_sbindir}/a2enmod mime
%{_sbindir}/a2enmod php5
%{_sbindir}/a2enmod rewrite
%{_sbindir}/a2enmod setenvif
%{_sbindir}/a2enmod ssl

# use some sed magic to enable apache modules for RHEL based systems


# Populate the conf files with the host name
HNAME=$(hostname)
SHNAME=$(hostname -s)
sed -i -e "s/.*ServerName.*/        ServerName $HNAME/" \
       %{apache_conf_location}/%{name}.conf
#sed -i -e "s/.*ServerAlias.*/        ServerAlias $SHNAME/" \
#       %{apache_conf_location}/%{name}.conf

# autostart mysql, apache2, and solr
echo "enable autostart of mysql, apache, and solr"
%if 0%{?suse_version} >= 1210  
   systemctl enable %{apache}.service
   systemctl enable %{mysql}.service
   insserv solr
%else
   chkconfig %{apache} on
   chkconfig %{mysql} on
   chkconfig solr on
%endif

# restar apache2, mysql, and solr
echo "restarting apache2, mysql, and solr"
%if 0%{?suse_version} >= 1210
   systemctl restart %{apache}.service
   systemctl restart %{mysql}.service
   /etc/init.d/solr restart
%else
   /etc/init.d/%{apache} reload
   /etc/init.d/%{mysql} restart
   /etc/init.d/solr start
%endif

echo "create database.ini file"
sudo -u %{webuser} cat > %{installation_dir}/application/config/database.ini << EOF
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
sed --in-place "s/##DB_PASS##/$openfisma_app_password/" %{installation_dir}/application/config/database.ini

# mysql configuration
echo "setup MySQL permissions for openfisma database"
echo "grant all on openfisma.* to openfisma_app@localhost identified by '$openfisma_app_password'" | mysql -u root
echo "flush mysql privileges"
echo "flush privileges;" | mysql -u root

# build the openfisma database and load sample data
echo "build the openfisma database and load sample data"
sudo -u %{webuser} php -qc %{installation_dir}/scripts/bin/doctrine.php -bs
sudo -u %{webuser} php -qc %{installation_dir}/scripts/bin/generate-findings.php -n 50
sudo -u %{webuser} php -qc %{installation_dir}/scripts/bin/generate-vulnerabilities.php -n 50
sudo -u %{webuser} php -qc %{installation_dir}/scripts/bin/generate-incidents.php -n 50
sudo -u %{webuser} php -qc %{installation_dir}/scripts/bin/rebuild-index.php -a

# finish installation scripts
fi

# force clean exit
exit 0

# if this is an upgrade then run the following
if [ "$1" == "2" ] ; then
echo "Upgrading OpenFISMA"

# Load new fixtures / YAML
sudo -u %{webuser} php -qc %{installation_dir}/scripts/bin/doctrine.php -m || true

# Run database migrations
sudo -u %{webuser} php -qc %{installation_dir}/scripts/bin/migrate.php || true

# Restart apache, mysql, and solr
%if 0%{?suse_version} >= 1210
   systemctl restart %{apache}.service
   systemctl restart %{mysql}.service
   /etc/init.d/solr restart
%else
   /etc/init.d/%{apache} reload
   /etc/init.d/%{mysql} restart
   /etc/init.d/solr start
%endif

# finish upgrade scripts
fi
exit 0

%changelog
