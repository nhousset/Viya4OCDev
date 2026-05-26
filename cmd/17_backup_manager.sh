#!/bin/bash
# TITLE: Gestion des Sauvegardes & PRA (Backups Viya)

# ==============================================================================
# Fichier : 05_backup_manager.sh
# Description : Boîte à outils d'administration des sauvegardes SAS Viya 4
# ==============================================================================

# Définition des couleurs (redéfinies ici car exécuté dans un sous-shell)
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Génération d'une chaîne d'horodatage pour les exécutions ad-hoc uniques
get_timestamp() {
    date +%Y%m%d-%H%M%S
}

# Fonction principale pour afficher le menu interactif complet
manage_backups_loop() {
    while true; do
        clear
        echo -e "${BLUE}============================================================================================${NC}"
        echo -e "${BOLD}   📦 OUTILLAGE ET GESTION DES SAUVEGARDES SAS VIYA 4${NC}"
        echo -e "   Namespace courant : ${CYAN}${DEFAULT_NAMESPACE:-'Par défaut'}${NC} | Commande : ${CYAN}${OC_CMD:-oc}${NC}"
        echo -e "${BLUE}============================================================================================${NC}"
        
        echo -e "  ${YELLOW}--- SURVEILLANCE & INFRASTRUCTURE ---${NC}"
        echo -e "  ${BOLD}${CYAN}1)${NC} Vérifier les volumes de stockage des sauvegardes (PVC & Rôles)"
        echo -e "  ${BOLD}${CYAN}2)${NC} Lister l'historique global de TOUTES les sauvegardes exécutées"
        echo -e "  ${BOLD}${CYAN}3)${NC} Filtrer les sauvegardes incluant PostgreSQL (INCLUDE_POSTGRES=true)"
        echo -e "  ${BOLD}${CYAN}4)${NC} Filtrer les sauvegardes excluant PostgreSQL (INCLUDE_POSTGRES=false)"
        echo -e "  ${BOLD}${CYAN}5)${NC} Consulter l'état des planifications automatiques (CronJobs)"
        echo ""
        echo -e "  ${YELLOW}--- RECHERCHE & DIAGNOSTIC CIBLÉ ---${NC}"
        echo -e "  ${BOLD}${CYAN}6)${NC} Obtenir le statut précis d'une sauvegarde via son ID (Backup ID)"
        echo -e "  ${BOLD}${CYAN}7)${NC} Inspecter les détails et les types de sources d'un Backup"
        echo -e "  ${BOLD}${CYAN}8)${NC} Suivre l'avancement en temps réel / Temps restant estimé (Progress)"
        echo -e "  ${BOLD}${CYAN}9)${NC} Consulter les logs d'un job de sauvegarde spécifique"
        echo ""
        echo -e "  ${YELLOW}--- DÉCLENCHEMENT DE SAUVEGARDES (AD-HOC) ---${NC}"
        echo -e "  ${BOLD}${CYAN}10)${NC} Lancer une sauvegarde Ad-Hoc Standard (Full)"
        echo -e "  ${BOLD}${CYAN}11)${NC} Lancer une sauvegarde Ad-Hoc Incrémentale (Nécessite une Full préalable)"
        echo -e "  ${BOLD}${CYAN}12)${NC} Lancer une sauvegarde Totale Forcée (All Sources avec PostgreSQL)"
        echo ""
        echo -e "  ${YELLOW}--- CONFIGURATION AVANCÉE & DÉPANNAGE ---${NC}"
        echo -e "  ${BOLD}${CYAN}13)${NC} Consulter le CronJob de purge automatique (sas-backup-purge-job)"
        echo -e "  ${BOLD}${CYAN}14)${NC} ${PURPLE}[Dépannage]${NC} Désactiver la validation d'espace disque (Patch DISABLE_VALIDATION=true)"
        echo -e "  ${BOLD}${CYAN}15)${NC} ${PURPLE}[Dépannage]${NC} Réactiver la validation d'espace disque (Patch DISABLE_VALIDATION=false)"
        echo -e "${BLUE}--------------------------------------------------------------------------------------------${NC}"
        echo -e "  ${RED}r)${NC} Retour au menu d'administration principal"
        echo -e "${BLUE}============================================================================================${NC}"
        read -p "👉 Sélectionnez une action (1-15) ou 'r' pour quitter : " MENU_CHOICE
        echo ""

        case "$MENU_CHOICE" in
            1)
                echo -e "${CYAN}🔍 [Doc p.5, 59, 61] Analyse des PersistentVolumeClaims liés au rôle de stockage...${NC}"
                echo -e "${BLUE}--------------------------------------------------------------------------------------------${NC}"
                ${OC_CMD:-oc} get pvc -l "sas.com/backup-role=storage"
                ;;
            2)
                echo -e "${CYAN}📜 [Doc p.18] Liste exhaustive de l'historique des sauvegardes (Trié par date de début)...${NC}"
                echo -e "${BLUE}--------------------------------------------------------------------------------------------${NC}"
                ${OC_CMD:-oc} get jobs -l "sas.com/backup-job-type in (scheduled-backup, scheduled-backup-incremental)" \
                    -L "sas.com/sas-backup-id,sas.com/backup-job-type,sas.com/sas-backup-job-status,sas.com/sas-backup-persistence-status,sas.com/sas-backup-include-postgres" \
                    --sort-by=.status.startTime
                ;;
            3)
                echo -e "${CYAN}📜 [Doc p.18] Sauvegardes contenant la base PostgreSQL (INCLUDE_POSTGRES=true)...${NC}"
                echo -e "${BLUE}--------------------------------------------------------------------------------------------${NC}"
                ${OC_CMD:-oc} get jobs -l "sas.com/backup-job-type in (scheduled-backup, scheduled-backup-incremental),sas.com/sas-backup-include-postgres=true" \
                    -L "sas.com/sas-backup-id,sas.com/backup-job-type,sas.com/sas-backup-job-status,sas.com/sas-backup-persistence-status" \
                    --sort-by=.status.startTime
                ;;
            4)
                echo -e "${CYAN}📜 [Doc p.18] Sauvegardes excluant la base PostgreSQL (INCLUDE_POSTGRES=false)...${NC}"
                echo -e "${BLUE}--------------------------------------------------------------------------------------------${NC}"
                ${OC_CMD:-oc} get jobs -l "sas.com/backup-job-type in (scheduled-backup, scheduled-backup-
