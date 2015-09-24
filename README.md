```bash
sudo apt-get install virtualbox resolvconf dnsmasq
wget https://dl.bintray.com/mitchellh/vagrant/vagrant_1.7.2_x86_64.deb
sudo dpkg -i vagrant_1.7.2_x86_64.deb
vagrant plugin install vagrant-vbguest
vagrant plugin install vagrant-share
vagrant plugin install landrush
vagrant up

sudo sh -c 'echo "server=/vagrant.dev/127.0.0.1#10053" > /etc/dnsmasq.d/vagrant-landrush'
sudo service dnsmasq restart
```

# Approximerge
```bash
OUTFILENAME="musiqueapproximative_$(date +%Y%m%d_%H%M%S)"
./bin/approximerge src/web/tracks/ 3600 "../../../httpdocs/approximerge/${OUTFILENAME}.mp3" 2>&1 | tee "../../../httpdocs/approximerge/${OUTFILENAME}.txt"
```
