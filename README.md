# Ansible (web)serverrollen – Konrad Sawicki

Met dit Ansible-project maak ik van twee kale Linux-servers een **webserver** (Apache + PHP) en een **databaseserver** (MariaDB). In een aparte play wordt daarna een PHP-website op de webserver gezet. De website praat met de database en telt hoe vaak de pagina is bezocht.

## Mijn netwerk

| Machine | Distro | Rol | IP (voorbeeld) |
|---|---|---|---|
| konrad-ansible | Ubuntu Server 24.04 | Ansible host (control node) | 192.168.23.10 |
| konrad-web | Ubuntu Server 24.04 | Webserver: Apache + PHP + website | 192.168.23.11 |
| konrad-db | Debian 13 | Databaseserver: MariaDB | 192.168.23.12 |

```
          konrad-ansible (Ansible + SSH-key konrad_ansible)
               |  SSH als gebruiker konrad
       +-------+--------+
       |                |
  konrad-web  ----->  konrad-db
  Apache/PHP   MySQL   MariaDB
               poort 3306
```

Alle VM's zitten in hetzelfde VMware-netwerk (NAT). Op elke machine heet het account `konrad`.

## Wat staat waar

```
ansible-konrad/
├── ansible.cfg                  instellingen (inventory, sudo-wachtwoord vragen)
├── site.yml                     hoofd-playbook
├── website.yml                  aparte play voor de PHP-website
├── inventory/
│   ├── hosts.ini                de servers en groepen
│   └── group_vars/
│       ├── all.yml              account, SSH-key, database-gegevens
│       ├── webservers.yml       pakketten voor de webserver
│       └── databases.yml        pakketten voor de databaseserver
├── collections/requirements.yml community.mysql
└── roles/
    ├── common/                  wat op ELKE server moet (pakketten, hostname, /etc/hosts, motd)
    ├── webserver/               Apache + PHP, met handler "Apache herladen"
    ├── database/                MariaDB, database en gebruiker, handler "MariaDB herstarten"
    └── website/                 index.php, style.css en config.php uploaden
```

Dubbele code heb ik voorkomen door alles wat op beide servers hetzelfde is in de role `common` te zetten. Lijsten met pakketten staan als variabelen in `group_vars`, zodat de taken zelf geen vaste waarden hebben.

## Stappenplan

### 1. VM's maken in VMware

Downloads:
- Ubuntu Server 24.04 LTS: https://ubuntu.com/download/server
- Debian 13 (netinst): https://www.debian.org/download

Maak 3 VM's (2 GB RAM en 20 GB schijf is genoeg), netwerk op **NAT**:

- **konrad-ansible** en **konrad-web** met Ubuntu Server. Bij de installatie: naam server `konrad-ansible` / `konrad-web`, gebruiker `konrad`, en vink **Install OpenSSH server** aan.
- **konrad-db** met Debian. Hostname `konrad-db`, **root-wachtwoord leeg laten** (dan krijgt `konrad` automatisch sudo), gebruiker `konrad`. Bij software alleen **SSH server** en **standard system utilities** aanvinken.

Gebruik op alle machines hetzelfde wachtwoord voor `konrad`, want Ansible vraagt het sudo-wachtwoord maar één keer.

Zoek van elke machine het IP op met `ip a` en zet ze in `inventory/hosts.ini`.

### 2. Ansible installeren op konrad-ansible

```bash
sudo apt update
sudo apt install -y ansible git
ansible --version
```

### 3. SSH-key maken en kopiëren

```bash
ssh-keygen -t ed25519 -f ~/.ssh/konrad_ansible -C "konrad@konrad-ansible"
ssh-copy-id -i ~/.ssh/konrad_ansible.pub konrad@192.168.23.11
ssh-copy-id -i ~/.ssh/konrad_ansible.pub konrad@192.168.23.12
ssh -i ~/.ssh/konrad_ansible konrad@192.168.23.11
```

De private key blijft op de Ansible host, alleen de `.pub` gaat naar de servers.

### 4. Project ophalen en rechten goed zetten

```bash
git clone https://github.com/JOUW-NAAM/ansible-konrad.git
cd ansible-konrad
chmod 755 .                      # anders negeert Ansible ansible.cfg
chmod 600 ~/.ssh/konrad_ansible  # anders weigert SSH de key
ansible-galaxy collection install -r collections/requirements.yml
```

### 5. Testen en uitvoeren (vaste volgorde)

```bash
ansible-inventory --graph
ansible all -m ansible.builtin.ping
ansible-playbook site.yml --syntax-check
ansible-playbook site.yml --list-hosts
ansible-playbook site.yml --list-tasks
ansible-playbook site.yml --check
ansible-playbook site.yml
ansible-playbook site.yml        # 2e keer: hoort changed=0 te geven
```

Daarna in de browser op Windows: `http://192.168.23.11` (het IP van konrad-web).

Let op: `--check` op een helemaal nieuwe server kan een fout geven bij het starten van Apache of MariaDB. Dat komt doordat check mode de pakketten niet echt installeert, dus de service bestaat dan nog niet.

## Bewijs in de video

1. Laat de drie VM's zien in VMware (namen met konrad).
2. `hostname`, `whoami` en `cat /etc/os-release` op konrad-web en konrad-db (twee verschillende distro's).
3. `ls ~/.ssh` op konrad-ansible (de key `konrad_ansible`).
4. `ansible all -m ansible.builtin.ping`.
5. `ansible-playbook site.yml` en de tweede keer met changed=0.
6. De website in de browser, met "Verbonden met MariaDB op konrad-db".

## Bronnen

- Ansible documentatie: https://docs.ansible.com
- community.mysql collectie: https://docs.ansible.com/ansible/latest/collections/community/mysql/
- SSH-uitleg van de opdracht: https://www.learnlinux.tv/getting-started-with-ansible-02-ssh-overview-setup/
