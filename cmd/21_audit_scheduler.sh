#!/bin/bash
# TITLE: Audit du Scheduler SAS Viya 4

CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BOLD='\033[1m'
NC='\033[0m'

while true; do
    clear
    echo -e "${CYAN}=== [ AUDIT DU SCHEDULER SAS VIYA 4 ] ===${NC}"
    echo -e "Namespace : ${YELLOW}$DEFAULT_NAMESPACE${NC}\n"
    
    echo -e " ${BOLD}1)${NC} 📊 Statut du microservice sas-scheduler"
    echo -e " ${BOLD}2)${NC} 🗓️  Lister les CronJobs SAS (Le scheduler K8s)"
    echo -e " ${BOLD}3)${NC} 📋 Logs du service sas-scheduler"
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    echo -e " ${RED}r)${NC} Retour au menu"
    echo ""
    read -p "👉 Votre choix : " choice

    case "$choice" in
        1)
            echo -e "\n${YELLOW}Vérification des pods du scheduler :${NC}"
            ${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" -l app=sas-scheduler
            read -p "Appuyez sur Entrée..."
            ;;
        2)
            echo -e "\n${YELLOW}Liste des CronJobs SAS (Planification native K8s) :${NC}"
            ${OC_CMD:-oc} get cronjobs -n "$DEFAULT_NAMESPACE" -l "sas.com/backup-job-type"
            echo -e "\n💡 ${CYAN}Ces CronJobs sont ceux pilotés directement par l'opérateur SAS Viya.${NC}"
            read -p "Appuyez sur Entrée..."
            ;;
        3)
            echo -e "\n${YELLOW}Récupération des logs du pod sas-scheduler...${NC}"
            POD_NAME=$(${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" -l app=sas-scheduler --no-headers | head -n 1 | awk '{print $1}')
            if [ -n "$POD_NAME" ]; then
                ${OC_CMD:-oc} logs "$POD_NAME" -n "$DEFAULT_NAMESPACE" --tail=50
            else
                echo -e "${RED}Aucun pod de scheduler trouvé.${NC}"
            fi
            read -p "Appuyez sur Entrée..."
            ;;
        r|R) break ;;
        *) echo -e "${RED}Choix invalide.${NC}" ; sleep 1 ;;
    esac
done
