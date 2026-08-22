#!/bin/bash
# TITLE: CronJobs and Last Executions Status

CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BOLD='\033[1m'
NC='\033[0m'

echo -e "${CYAN}=== [ TÃƒâ€šCHES PLANIFIÃƒâ€°ES (CRONJOBS & JOBS) ] ===${NC}"
echo -e "Namespace : ${YELLOW}$DEFAULT_NAMESPACE${NC}\n"

# 1. Liste des CronJobs (La planification)
echo -e "${YELLOW}Ã¢ÂÂ±Ã¯Â¸Â  Liste des CronJobs configurÃƒÂ©s :${NC}"
CRONJOBS=$(oc get cronjobs -n "$DEFAULT_NAMESPACE" 2>/dev/null)

if [ -z "$CRONJOBS" ]; then
    echo -e "Aucun CronJob trouvÃƒÂ© dans ce namespace."
else
    echo "$CRONJOBS"
fi

# 2. VÃƒÂ©rification des Jobs rÃƒÂ©cents (L'exÃƒÂ©cution)
echo -e "\n${YELLOW}Ã¢Å¡â„¢Ã¯Â¸Â  Statut des Jobs rÃƒÂ©cents (En cours ou en erreur) :${NC}"

# On cherche les jobs qui n'ont pas la valeur "1/1" dans la colonne COMPLETIONS
# Cela inclut les jobs en train de tourner (0/1) ou ceux qui ont crashÃƒÂ©.
FAILED_JOBS=$(oc get jobs -n "$DEFAULT_NAMESPACE" --no-headers 2>/dev/null | awk '$2 != "1/1" {print $0}')

if [ -z "$FAILED_JOBS" ]; then
    echo -e "${GREEN}Ã¢Å“â€¦ Tous les jobs rÃƒÂ©cents se sont terminÃƒÂ©s avec succÃƒÂ¨s.${NC}"
else
    printf "${BOLD}%-55s %-15s %-10s %s${NC}\n" "NOM DU JOB" "COMPLETIONS" "DURATION" "AGE"
    echo "------------------------------------------------------------------------------------------------"
    
    # On colore diffÃƒÂ©remment selon si c'est "en cours" (0/1 rÃƒÂ©cent) ou si c'est vraiment plantÃƒÂ©
    while read -r line; do
        if [[ -n "$line" ]]; then
            echo -e "${RED}$line${NC}"
        fi
    done <<< "$FAILED_JOBS"
    
    echo "------------------------------------------------------------------------------------------------"
    echo -e "Ã°Å¸â€™Â¡ ${CYAN}Note : Si 'COMPLETIONS' est ÃƒÂ  0/1 et 'DURATION' s'incrÃƒÂ©mente, le job est en cours d'exÃƒÂ©cution.${NC}"
fi
