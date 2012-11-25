class net::musiqueapproximative::www {
  # TODO : make sure this is executed before packages installation
  exec { 'apt-get update':
    command => '/usr/bin/apt-get update'
  }

  exec { 'a2enmod rewrite':
    command => '/usr/sbin/a2enmod rewrite',
    require => Package['apache2'],
    notify => Service['apache2']
  }

  package { ["apache2", "libapache2-mod-php5", "php5-cli", "php5-mysqlnd", "php-apc", "vim-tiny", "mysql-server"]:
    ensure => present,
  }

  service { ["apache2"]:
    ensure => running,
    require => Package["apache2"],
  }

  file { '/etc/apache2/sites-enabled/000-default':
    ensure => absent,
    notify => Service['apache2']
  }

  file { '/etc/apache2/sites-enabled/net_musiqueapproximative_www':
    ensure => present,
    source => "puppet:///vagrant_puppet_files/etc/apache2/sites-enabled/net_musiqueapproximative_www",
    notify => Service['apache2']
  }

  file { '/etc/apache2/envvars':
    ensure => present,
    source => "puppet:///vagrant_puppet_files/etc/apache2/envvars",
    notify => Service['apache2']
  }
}

include net::musiqueapproximative::www