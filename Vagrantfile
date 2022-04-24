# -*- mode: ruby -*-
# vi: set ft=ruby :
Vagrant.configure("2") do |config|
  config.vm.box = "freebsd/FreeBSD-13.1-RC2"
  config.vm.box_version = "2022.04.07"

  config.vm.network "forwarded_port", guest: 80, host: 4000

  config.vm.provider "virtualbox" do |vb|
     vb.gui = true
     vb.cpus = 4
     vb.memory = "1568"
  end
  
  config.vm.provider "vmware_workstation" do |vwx|
     vwx.gui = true
     vwx.vmx["memsize"] = "1568"
     vwx.vmx["numvcpus"] = "4"
  end

  config.vm.provision "shell", inline: "/bin/tcsh /.ovk_release/install/automated/freebsd-13/install"
end
