# -*- mode: ruby -*-
# vi: set ft=ruby :
Vagrant.configure("2") do |config|
  config.vm.box = "freebsd/FreeBSD-12.1-STABLE"

  config.vm.network "forwarded_port", guest: 80, host: 4000

  config.vm.synced_folder ".", "/.ovk_release"

  config.vm.provider "virtualbox" do |vb|
     vb.gui = true
     vb.memory = "1024"
  end

  config.vm.provision "shell", inline: "/bin/tcsh /.ovk_release/install/automated/freebsd-12/install"
end
