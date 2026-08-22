#!/bin/bash
# TITLE: Top 10 Pods (CPU/RAM Consumption)

# Couleurs pour le plugin
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${CYAN}=== [ TOP 10 CONSOMMATION RESSOURCES ] ===${NC}"
echo -e "Namespace : ${YELLOW}$DEFAULT_NAMESPACE${NC}\n"

# VÃƒÂ©rification si les mÃƒÂ©triques sont disponibles
if ! oc adm top pods -n "$DEFAULT_NAMESPACE" > /dev/null 2>&1; then
    echo -e "Ã¢ÂÅ’ Erreur : Les mÃƒÂ©triques (Metrics Server) ne semblent pas disponibles sur ce cluster."
    exit 1
fi

echo -e "${YELLOW}Ã°Å¸â€Â¥ Top 10 par CPU (milliCPUs) :${NC}"
echo "----------------------------------------------------------"
oc adm top pods -n "$DEFAULT_NAMESPACE" --no-headers | sort -rnk 2 | head -n 10
echo "----------------------------------------------------------"

echo -e "\n${YELLOW}Ã°Å¸Â§Â  Top 10 par MÃƒâ€°MOIRE (MiB/GiB) :${NC}"
echo "----------------------------------------------------------"
oc adm top pods -n "$DEFAULT_NAMESPACE" --no-headers | sort -rnk 3 | head -n 10
echo "----------------------------------------------------------"
