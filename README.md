# Ã°Å¸Å¡â‚¬ Viya4OC

Ã°Å¸â€ºÂ Ã¯Â¸Â **Viya4OC** est un outil en ligne de commande en **Bash** permettant d'administrer et diagnostiquer une plateforme **SAS Viya 4** dÃƒÂ©ployÃƒÂ©e sur **OpenShift (Kubernetes)**.

Il s'appuie sur des scripts modulaires et un orchestrateur (iya.sh) pour simplifier l'utilisation des commandes oc et accÃƒÂ©lÃƒÂ©rer les opÃƒÂ©rations de troubleshooting.

---

## Ã°Å¸Å½Â¯ Objectif

Faciliter le travail des administrateurs et des ÃƒÂ©quipes support en proposant :

- Ã°Å¸â€œâ€¹ Un menu CLI interactif
- Ã°Å¸â€Â Des audits automatisÃƒÂ©s
- Ã¢Å¡Â¡ Des raccourcis vers les commandes OpenShift
- Ã°Å¸Â©Âº Des diagnostics rapides (pods, CAS, DB, TLS)

---

## Ã°Å¸Å’Å¸ FonctionnalitÃƒÂ©s principales

- Ã°Å¸â€™Â¾ Gestion de configuration persistÃƒÂ©e (config.env)
- Ã°Å¸â€â€˜ Connexion simplifiÃƒÂ©e ÃƒÂ  OpenShift
- Ã°Å¸ÂÂ¥ Audit global de la plateforme
- Ã°Å¸â€œÅ  Suivi du moteur CAS
- Ã°Å¸Å¡Â¨ DÃƒÂ©tection des pods en erreur + logs
- Ã°Å¸ÂËœ VÃƒÂ©rification PostgreSQL (CrunchyData)
- Ã°Å¸â€â€™ ContrÃƒÂ´le des certificats TLS

---

## Ã°Å¸â€œâ€¹ PrÃƒÂ©-requis

- Bash (Linux / macOS / WSL)
- OpenShift CLI (oc)
- AccÃƒÂ¨s ÃƒÂ  un cluster OpenShift avec SAS Viya 4
- Token OpenShift

---

## Ã°Å¸Å¡â‚¬ Installation

``bash
git clone https://github.com/nhousset/Viya4OCDev.git
cd Viya4OCDev
chmod +x viya.sh
``

---

## Ã°Å¸Å’Â Interface Web (Docker)

Une interface web reproduisant le menu de la ligne de commande est disponible sous forme d'application PHP avec Docker.

### Lancement de l'application Web

1. Allez dans le rÃƒÂ©pertoire webapp :
   ``bash
   cd webapp
   ``
2. DÃƒÂ©marrez le conteneur avec Docker Compose :
   ``bash
   docker compose up -d
   ``
3. AccÃƒÂ©dez ÃƒÂ  l'interface depuis votre navigateur :
   [http://localhost:7891](http://localhost:7891)

*Note : Pour arrÃƒÂªter l'application, lancez docker compose down depuis le rÃƒÂ©pertoire webapp.*

### Alternative : Lancement sans Docker Compose (Volume Docker)

Si vous ne souhaitez pas utiliser docker compose et prÃƒÂ©fÃƒÂ©rez utiliser un **Volume Docker** dÃƒÂ©diÃƒÂ© pour stocker vos configurations (ÃƒÂ©vitant ainsi les ÃƒÂ©ventuels problÃƒÂ¨mes de permissions de fichiers sous Linux), vous pouvez utiliser la commande docker run classique.

*Ãƒâ‚¬ exÃƒÂ©cuter depuis la racine du projet (`Viya4OCDev`) :*

```bash
# 1. Construction de l'image
docker build -t viya4oc -f webapp/Dockerfile .

# 2. Lancement avec un volume nommÃƒÂ© "webapp_viya4oc_conf" pour la configuration
docker run -d \
  --name viya4oc \
  -p 7891:80 \
  -v webapp_viya4oc_conf:/var/www/conf \
  viya4oc
```

*Note : Avec cette mÃƒÂ©thode, vos fichiers de profil config-*.env ne seront pas crÃƒÂ©ÃƒÂ©s dans le dossier du projet, mais seront stockÃƒÂ©s de maniÃƒÂ¨re transparente et persistante dans le volume interne de Docker ( iya4oc_conf).*