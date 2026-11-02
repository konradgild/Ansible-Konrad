#!/bin/bash
# Eenmalige voorbereiding op konrad-ansible (Konrad Sawicki)
# Starten met: bash setup.sh
set -e

echo "== Ansible installeren"
sudo apt update
sudo apt install -y ansible

echo "== SSH-key maken (als hij er nog niet is)"
if [ ! -f ~/.ssh/konrad_ansible ]; then
  ssh-keygen -t ed25519 -f ~/.ssh/konrad_ansible -N "" -C "konrad@konrad-ansible"
fi
chmod 600 ~/.ssh/konrad_ansible

echo "== Public key naar de servers kopieren (typ 1x per server je wachtwoord)"
for ip in $(grep -oP 'ansible_host=\K[0-9.]+' inventory/hosts.ini); do
  ssh-copy-id -o StrictHostKeyChecking=accept-new -i ~/.ssh/konrad_ansible.pub konrad@"$ip"
done

echo "== Rechten van de projectmap goed zetten"
chmod 755 .

echo "== Collectie voor MariaDB installeren"
ansible-galaxy collection install -r collections/requirements.yml

echo "== Klaar! Test nu met: ansible all -m ansible.builtin.ping"
