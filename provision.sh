#!/usr/bin/env bash

# Mise à jour des dépots
apt-get -qq update

# Configuration de la timezone
echo "Europe/Paris" > /etc/timezone 
apt-get install -y tzdata
dpkg-reconfigure -f noninteractive tzdata

# Installation de Apache et PHP
apt-get -y install libapache2-mod-php5 php5-cli

# Installation de MySQL
echo "mysql-server mysql-server/root_password password root" | debconf-set-selections
echo "mysql-server mysql-server/root_password_again password root" | debconf-set-selections
apt-get install -y mysql-server

# Installation de PhpMyAdmin
echo "phpmyadmin phpmyadmin/dbconfig-install boolean true" | debconf-set-selections
echo "phpmyadmin phpmyadmin/app-password-confirm password root" | debconf-set-selections
echo "phpmyadmin phpmyadmin/mysql/admin-pass password root" | debconf-set-selections
echo "phpmyadmin phpmyadmin/mysql/app-pass password root" | debconf-set-selections
echo "phpmyadmin phpmyadmin/reconfigure-webserver multiselect apache2" | debconf-set-selections
apt-get install -y phpmyadmin

# Création de la base de données
mysql --defaults-file=/etc/mysql/debian.cnf -e "drop database if exists net_musiqueapproximative_ww"w
mysql --defaults-file=/etc/mysql/debian.cnf -e "create database net_musiqueapproximative_www default charset utf8 collate utf8_general_ci"
mysql --defaults-file=/etc/mysql/debian.cnf net_musiqueapproximative_www < /vagrant/src/data/fixtures/net_musiqueapproximative_www.dump.sql

# Configuration du projet
apt-get install -y ant 
cd /vagrant
./composer.phar install --prefer-dist
ant configure build -Dprofile=vagrant
/vagrant/src/symfony cache:clear

# Mise à disposition du projet dans Apache
ln -sf /vagrant/src/web/* /var/www/
rm -f /var/www/index.html

# Informations
echo 
echo -e "Le site est disponible à l'adresse : http://musiqueapproximative.vagrant.dev/frontend_dev.php"
echo -e "PhpMyAdmin est disponible à l'adresse : http://musiqueapproximative.vagrant.dev/phpmyadmin/ (root / root)"