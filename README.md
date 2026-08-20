# ðŸš€ Viya4OC

ðŸ”§ **Viya4OC** est un outil en ligne de commande en **Bash** permettant dâ€™administrer et diagnostiquer une plateforme **SAS Viya 4** dÃ©ployÃ©e sur **OpenShift (Kubernetes)**.

Il sâ€™appuie sur des scripts modulaires et un orchestrateur (`viya.sh`) pour simplifier lâ€™utilisation des commandes `oc` et accÃ©lÃ©rer les opÃ©rations de troubleshooting.

---

## ðŸŽ¯ Objectif

Faciliter le travail des administrateurs et des Ã©quipes support en proposant :

- âœ… Un menu CLI interactif
- âœ… Des audits automatisÃ©s
- âœ… Des raccourcis vers les commandes OpenShift
- âœ… Des diagnostics rapides (pods, CAS, DB, TLS)

---

## âš™ï¸ FonctionnalitÃ©s principales

- ðŸ” Gestion de configuration persistÃ©e (`config.env`)
- ðŸ”Œ Connexion simplifiÃ©e Ã  OpenShift
- ðŸ“Š Audit global de la plateforme
- âš™ï¸ Suivi du moteur CAS
- âš ï¸ DÃ©tection des pods en erreur + logs
- ðŸ—„ï¸ VÃ©rification PostgreSQL (CrunchyData)
- ðŸ” ContrÃ´le des certificats TLS

---

## ðŸ§± PrÃ©-requis

- Bash (Linux / macOS / WSL)
- OpenShift CLI (`oc`)
- AccÃ¨s Ã  un cluster OpenShift avec SAS Viya 4
- Token OpenShift

---

## ðŸ“¦ Installation

```bash
git clone https://github.com/nhousset/Viya4OCDev.git
cd Viya4OCDev
chmod +x viya.sh
```

---

## ðŸŒ Interface Web (Docker)

Une interface web reproduisant le menu de la ligne de commande est disponible sous forme d'application PHP avec Docker.

### Lancement de l'application Web

1. Allez dans le rÃ©pertoire `webapp` :
   ```bash
   cd webapp
   ```
2. DÃ©marrez le conteneur avec Docker Compose :
   ```bash
   docker compose up -d
   ```
3. AccÃ©dez Ã  l'interface depuis votre navigateur :
   [http://localhost:7891](http://localhost:7891)

*Note : Pour arrÃªter l'application, lancez `docker compose down` depuis le rÃ©pertoire `webapp`.*

### Alternative : Lancement sans Docker Compose (Volume Docker)

Si vous ne souhaitez pas utiliser `docker compose` et prÃ©fÃ©rez utiliser un **Volume Docker** dÃ©diÃ© pour stocker vos configurations (Ã©vitant ainsi les Ã©ventuels problÃ¨mes de permissions de fichiers sous Linux), vous pouvez utiliser la commande `docker run` classique.

*Ã€ exÃ©cuter depuis la racine du projet (`Viya4OCDev`) :*

```bash
# 1. Construction de l'image
docker build -t viya4oc webapp/

# 2. Lancement avec un volume nommÃ© "viya4oc_conf" pour la configuration
docker run -d \
  --name viya4oc \
  -p 7891:80 \
  -v $(pwd)/webapp/src:/var/www/html \
  -v $(pwd)/cmd:/var/www/cmd:ro \
  -v $(pwd)/cmd_cli:/var/www/cmd_cli:ro \
  -v viya4oc_conf:/var/www/conf \
  viya4oc
```

*Note : Avec cette mÃ©thode, vos fichiers de profil `config-*.env` ne seront pas crÃ©Ã©s dans le dossier du projet, mais seront stockÃ©s de maniÃ¨re transparente et persistante dans le volume interne de Docker (`viya4oc_conf`).*
