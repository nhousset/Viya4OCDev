FROM php:8.2-apache

# Installation des paquets de base pour permettre l'exécution des scripts shell
RUN apt-get update && apt-get install -y \
    bash \
    curl \
    jq \
    && rm -rf /var/lib/apt/lists/*

# Activation du module rewrite d'Apache (optionnel mais recommandé)
RUN a2enmod rewrite

# Configuration du dossier de travail
WORKDIR /var/www/html

# Copie du code de l'application
COPY webapp/src/ /var/www/html/
COPY cmd/ /var/www/cmd/
COPY cmd_cli/ /var/www/cmd_cli/

# Téléchargement et installation du client OpenShift (oc)
RUN curl -sLO https://mirror.openshift.com/pub/openshift-v4/clients/ocp/latest/openshift-client-linux.tar.gz && \
    tar -xzf openshift-client-linux.tar.gz -C /usr/local/bin/ oc && \
    chmod +x /usr/local/bin/oc && \
    rm -f openshift-client-linux.tar.gz
