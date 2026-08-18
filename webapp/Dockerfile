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

# Le code PHP sera monté via un volume pour le développement,
# mais on peut aussi le copier pour une image de production.
# COPY src/ /var/www/html/

# Téléchargement et installation du client OpenShift (oc)
RUN curl -sLO https://mirror.openshift.com/pub/openshift-v4/clients/ocp/latest/openshift-client-linux.tar.gz && \
    tar -xzf openshift-client-linux.tar.gz -C /usr/local/bin/ oc && \
    chmod +x /usr/local/bin/oc && \
    rm -f openshift-client-linux.tar.gz
