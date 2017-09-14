# -*- mode: ruby -*-
# vi: set ft=ruby :

# All Vagrant configuration is done below. The "2" in Vagrant.configure
# configures the configuration version (we support older styles for
# backwards compatibility). Please don't change it unless you know what
# you're doing.
Vagrant.configure(2) do |config|
  # The most common configuration options are documented and commented below.
  # For a complete reference, please see the online documentation at
  # https://docs.vagrantup.com.

  # Every Vagrant development environment requires a box. You can search for
  # boxes at https://atlas.hashicorp.com/search.
  config.vm.box = 'bento/ubuntu-14.04'

  config.vm.network 'private_network', type: 'dhcp', nictype: 'virtio'

  config.ssh.forward_agent = true

  # Enable provisioning with a shell script. Additional provisioners such as
  # Puppet, Chef, Ansible, Salt, and Docker are also available. Please see the
  # documentation for more information about their specific syntax and use.
  config.vm.provision 'shell' do |s|
    s.path = 'provision.sh'
    s.args = ENV['PROFILE'] || 'vagrant'
  end

  config.vm.synced_folder '.', '/vagrant', type: 'nfs'

  config.vm.provider 'virtualbox' do |v|
    v.customize ['modifyvm', :id, '--hpet', 'on']
    v.linked_clone = true
  end

  # @see https://github.com/phinze/landrush
  config.landrush.enabled = true
  config.vm.hostname = 'musiqueapproximative.vagrant.test'
end
