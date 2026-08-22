#!/bin/bash
# TITLE: Deployments, StatefulSets & DaemonSets Status

CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${CYAN}=== [ STATUT DES DÃƒâ€°PLOIEMENTS (MICROSERVICES) ] ===${NC}"
echo -e "Namespace : ${YELLOW}$DEFAULT_NAMESPACE${NC}\n"

echo -e "${YELLOW}Ã°Å¸â€œÅ  RÃƒÂ©sumÃƒÂ© global :${NC}"
# Calcul du total et de ceux qui sont 100% prÃƒÂªts
TOTAL=$(oc get deploy -n "$DEFAULT_NAMESPACE" --no-headers 2>/dev/null | wc -l)
AVAILABLE=$(oc get deploy -n "$DEFAULT_NAMESPACE" --no-headers 2>/dev/null | awk '{split($2,a,"/"); if(a[1]==a[2] && a[1]>0) count++} END {print count+0}')

echo -e "DÃƒÂ©ploiements totaux  : ${CYAN}$TOTAL${NC}"
if [ "$TOTAL" -eq "$AVAILABLE" ] && [ "$TOTAL" -gt 0 ]; then
    echo -e "DÃƒÂ©ploiements prÃƒÂªts   : ${GREEN}$AVAILABLE${NC}"
else
    echo -e "DÃƒÂ©ploiements prÃƒÂªts   : ${RED}$AVAILABLE${NC}"
fi

echo -e "\n${YELLOW}Ã°Å¸Å¡Â¨ DÃƒÂ©ploiements en anomalie (Non Ready) :${NC}"
# awk analyse la colonne 2 (READY, ex: 1/1). Il isole ceux oÃƒÂ¹ le chiffre de gauche est diffÃƒÂ©rent de celui de droite.
BAD_DEPLOY=$(oc get deploy -n "$DEFAULT_NAMESPACE" --no-headers 2>/dev/null | awk '{split($2,a,"/"); if(a[1]!=a[2] || (a[2]>0 && a[1]==0)) print $0}')

if [ -z "$BAD_DEPLOY" ]; then
    echo -e "${GREEN}Ã¢Å“â€¦ Tous les microservices sont ÃƒÂ  100% de disponibilitÃƒÂ©.${NC}"
else
    printf "${BOLD}%-45s %-10s %-12s %-10s %s${NC}\n" "NOM DU MICROSERVICE" "READY" "UP-TO-DATE" "AVAILABLE" "AGE"
    echo "----------------------------------------------------------------------------------------"
    echo -e "${RED}$BAD_DEPLOY${NC}"
    echo "----------------------------------------------------------------------------------------"
    echo -e "Ã°Å¸â€™Â¡ ${CYAN}Astuce : Utilisez 'oc describe deploy <nom>' pour comprendre pourquoi il ne dÃƒÂ©marre pas.${NC}"
fi
