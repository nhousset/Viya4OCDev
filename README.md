
# 🚀 Viya4OC

🛠️ **Viya4OC** est un outil en ligne de commande en **Bash** permettant d'administrer et diagnostiquer une plateforme **SAS Viya 4** déployée sur **OpenShift (Kubernetes)**.

Il s'appuie sur des scripts modulaires et un orchestrateur (iya.sh) pour simplifier l'utilisation des commandes oc et accélérer les opérations de troubleshooting.

---

## 🎯 Objectif

Faciliter le travail des administrateurs et des équipes support en proposant :

- 📋 Un menu CLI interactif
- 🔍 Des audits automatisés
- ⚡ Des raccourcis vers les commandes OpenShift
- 🩺 Des diagnostics rapides (pods, CAS, DB, TLS)

---

## 🌟 Fonctionnalités principales

- 💾 Gestion de configuration persistée (config.env)
- 🔑 Connexion simplifiée à OpenShift
- 🏥 Audit global de la plateforme
- 📊 Suivi du moteur CAS
- 🚨 Détection des pods en erreur + logs
- 🐘 Vérification PostgreSQL (CrunchyData)
- 🔒 Contrôle des certificats TLS

---

## 📋 Pré-requis

- Bash (Linux / macOS / WSL)
- OpenShift CLI (oc)
- Accès à un cluster OpenShift avec SAS Viya 4
- Token OpenShift

---

## 🚀 Installation

``bash
git clone https://github.com/nhousset/Viya4OCDev.git
cd Viya4OCDev
chmod +x viya.sh
``

---

## 🌐 Interface Web (Docker)

Une interface web reproduisant le menu de la ligne de commande est disponible sous forme d'application PHP avec Docker.

### Lancement de l'application Web

1. Allez dans le répertoire webapp :
   ``bash
   cd webapp
   ``
2. Démarrez le conteneur avec Docker Compose :
   ``bash
   docker compose up -d
   ``
3. Accédez à l'interface depuis votre navigateur :
   [http://localhost:7891](http://localhost:7891)

*Note : Pour arrêter l'application, lancez docker compose down depuis le répertoire webapp.*

### Alternative : Lancement sans Docker Compose (Volume Docker)

Si vous ne souhaitez pas utiliser docker compose et préférez utiliser un **Volume Docker** dédié pour stocker vos configurations (évitant ainsi les éventuels problèmes de permissions de fichiers sous Linux), vous pouvez utiliser la commande docker run classique.

*À exécuter depuis la racine du projet (`Viya4OCDev`) :*

```bash
# 1. Construction de l'image
docker build -t viya4oc -f webapp/Dockerfile .

# 2. Lancement avec un volume nommé "viya4oc_conf" pour la configuration
docker run -d \
  --name viya4oc \
  -p 7891:80 \
  -v viya4oc_conf:/var/www/conf \
  viya4oc
```

*Note : Avec cette méthode, vos fichiers de profil config-*.env ne seront pas créés dans le dossier du projet, mais seront stockés de manière transparente et persistante dans le volume interne de Docker ( iya4oc_conf).*
