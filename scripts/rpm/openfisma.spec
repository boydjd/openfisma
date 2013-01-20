#
# spec file for package openfisma
#
# norootforbuild

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

# It is in the %prep section that the build environment for the software is created, starting with removing the remnants of any previous builds.
%prep
%setup -q

# the part of the spec file that is responsible for performing the build. Like the %prep section, the %build section is an ordinary sh script. 
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

# The %install section is executed as a sh script, just like %prep and %build. 
%install

# disables brp-check-bytecode-version which throws an error on jar files
export NO_BRP_CHECK_BYTECODE_VERSION=true

# create openfisma installation directory to represent how the files should be installed into the target system. 
%{__mkdir_p} %{buildroot}/%{installation_dir}

# copy all of the files from the source directory into the new installation directory
cp -rp * %{buildroot}/%{installation_dir}

# Copy configuration files from current location into location of where they should be installed
%{__mkdir_p} %{buildroot}/etc/%{apache}/vhosts.d/
%{__mkdir_p} %{buildroot}/etc/init.d/
%{__mkdir_p} %{buildroot}/etc/cron.d/
cp -rp %{buildroot}%{installation_dir}/scripts/rpm/openfisma_apache2 %{buildroot}/etc/%{apache}/vhosts.d/%{name}.conf
cp -rp %{buildroot}%{installation_dir}/scripts/rpm/openfisma_solr %{buildroot}/etc/init.d/openfisma_solr
cp -rp %{buildroot}%{installation_dir}/scripts/rpm/openfisma_cron %{buildroot}/etc/cron.d/openfisma_cron

# By adding a sh script to the %clean section, such situations can be handled gracefully, right after the binary package is created.
%clean

# The %files section is different from the others, in that it contains a list of the files that are part of the package. Always remember — if it isn't in the file list, it won't be put in the package!
%files
%defattr(-,root,root,-)
%{installation_dir}
%config /etc/apache2/vhosts.d/openfisma.conf
%config /etc/cron.d/openfisma_cron
%config /etc/init.d/openfisma_solr
%config %{installation_dir}/application/config/application.ini

# run the following scripts after installation of rpm
%post

# check to see if apache user is in the sudoers file, if not add it
if grep "^%{webuser}.*ALL=NOPASSWD:.*/usr/sbin/%{apache}.*/sbin/ifconfig" /etc/sudoers > /dev/null ; then
    echo "sudo active"
else
    echo "%{webuser} ALL=NOPASSWD:/usr/sbin/%{apache}, /sbin/ifconfig" >> /etc/sudoers
fi

# Check and update all permissions
find %{installation_dir} -type d -exec chmod 770 {} \;
find %{installation_dir} -type f -exec chmod 660 {} \;
chown -R %{webuser}:%{webgroup} %{installation_dir} 
chmod 755 /etc/init.d/openfisma_solr
chmod 666 %{installation_dir}/data/logs/*

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

###########################################
#%pre
#
# Create dekiwiki user
#grep "^dekiwiki" /etc/passwd >>/dev/null
#if [ $? -ne 0 ]; then
#  useradd -s /bin/sh -d %{webhome} -g %{webgroup} \
#          -c "DekiWiki user" dekiwiki 2>/dev/null
#fi
#
#exit 0

# Changing init script to use dekiwiki user
#sed -i -e "s/^DEKIWIKI_USER=.*$/"'DEKIWIKI_USER="dekiwiki"/' \
#       /etc/init.d/dekiwiki

#%if 0%{?fedora}||0%{?fedora_version}||0%{?rhel_version}||0%{?centos_version}
   # Make sure paths are good for RedHat
#   sed -i -e 's/\/var\/log\/apache2/\/var\/log\/httpd/g' \
#         %{buildroot}/etc/%{apache}/vhost.d/openfisma_apache
#%endif
#%elseif 0%{?suse_version}
#%if 0%{?suse_version} || 0%{?sles_version}
   # For OpenSUSE we need to change some default values
#   sed -i -e 's/\/var\/log\/httpd/\/var\/log\/apache2/g' \
#          -e 's/\/var\/www/\/srv\/www/g' \
#          %{buildroot}/etc/%{apache}/vhost.d/openfisma_apache
#%endif

# Populate the conf files with the host name
#HNAME=$(hostname)
#SHNAME=$(hostname -s)
#sed -i -e "s/.*#ServerName.*/        ServerName $HNAME/" \
#       /etc/%{apache}/conf.d/openfisma.conf
#sed -i -e "s/.*#ServerAlias.*/        ServerAlias $SHNAME/" \
#       /etc/%{apache}/conf.d/openfisma.conf

# autostart mysql, apache2, and solr
echo "enable autostart of mysql, apache, and solr"
%if 0%{?suse_version} >= 1210  
   systemctl enable apache2.service
   systemctl enable mysql.service
   insserv openfisma_solr
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
   /etc/init.d/openfisma_solr restart
%else
   /etc/init.d/apache2 reload
   /etc/init.d/mysql restart
   /etc/init.d/openfisma_solr start
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
php %{installation_dir}/scripts/bin/doctrine.php -bs
php %{installation_dir}/scripts/bin/generate-findings.php -n 50
php %{installation_dir}/scripts/bin/generate-vulnerabilities.php -n 50
php %{installation_dir}/scripts/bin/generate-incidents.php -n 50
php %{installation_dir}/scripts/bin/rebuild-index.php -a

# turns off APC and secure cookies
#sed --in-place "s/resources.session.cookie_secure = true/resources.session.cookie_secure = false/" %{installation_dir}/application/config/application.ini
#sed --in-place "s/cache_id_prefix = openfisma_ /cache_id_prefix = openfisma_test2_ /" %{installation_dir}/application/config/application.ini
#sed --in-place "s/resources.cachemanager.default.backend.name = Apc/;resources.cachemanager.default.backend.name = Apc/" %{installation_dir}/application/config/application.ini
#sed --in-place "s/;resources.cachemanager.default.backend.name = File/resources.cachemanager.default.backend.name = File/" %{installation_dir}/application/config/application.ini
#sed --in-place "s/;resources.cachemanager.default.backend.options.cache_dir/resources.cachemanager.default.backend.options.cache_dir/" %{installation_dir}/application/config/application.ini

# finish installation scripts
fi

# force clean exit
exit 0

# if this is an upgrade then run the following
if [ "$1" == "2" ] ; then
echo "Upgrading OpenFISMA"

%{installation_dir}/scripts/bin/doctrine.php -m || true
%{installation_dir}/scripts/bin/migrate.php || true
/etc/init.d/mysql restart
/etc/init.d/openfisma_solr restart
/etc/init.d/apache2 try-restart

# finish upgrade scripts
fi
exit 0

%changelog
