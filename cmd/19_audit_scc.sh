#!/bin/bash
# TITLE: SCC and Permissions Audit

CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BOLD='\033[1m'
NC='\033[0m'

echo -e "${CYAN}=== [ AUDIT DES SECURITY CONTEXT CONSTRAINTS (SCC) ] ===${NC}"
echo -e "Namespace : ${YELLOW}$DEFAULT_NAMESPACE${NC}\n"

# 1. Lister les SCC globales
echo -e "${YELLOW}Ã°Å¸â€œÅ  Liste des SCC disponibles sur le cluster :${NC}"
${OC_CMD:-oc} get scc

# 2. VÃƒÂ©rifier quelle SCC est utilisÃƒÂ©e par les Pods du namespace
echo -e "\n${YELLOW}Ã°Å¸Å½Â¯ SCC utilisÃƒÂ©es par les Pods du namespace '$DEFAULT_NAMESPACE' :${NC}"
echo -e "${CYAN}(Analyse basÃƒÂ©e sur les annotations 'openshift.io/scc')${NC}"
echo "--------------------------------------------------------------------------------"
printf "${BOLD}%-45s | %-20s${NC}\n" "NOM DU POD" "SCC UTILISÃƒâ€°E"
echo "--------------------------------------------------------------------------------"

# On rÃƒÂ©cupÃƒÂ¨re le nom de chaque pod et son annotation SCC
${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" -o custom-columns=NAME:.metadata.name,SCC:.metadata.annotations.openshift\.io/scc --no-headers | while read -r pod_name scc_name; do
    
    # Coloration si la SCC est 'restricted' (par dÃƒÂ©faut, souvent trop restrictif pour Viya)
    if [[ "$scc_name" == *"restricted"* ]]; then
        printf "%-45s | ${YELLOW}%-20s${NC}\n" "$pod_name" "$scc_name"
    else
        printf "%-45s | ${GREEN}%-20s${NC}\n" "$pod_name" "$scc_name"
    fi
done

# 3. VÃƒÂ©rifier les permissions du ServiceAccount par dÃƒÂ©faut (souvent lÃƒÂ  que ÃƒÂ§a bloque)
echo -e "\n${YELLOW}Ã°Å¸â€â€˜ VÃƒÂ©rification des ServiceAccounts du namespace :${NC}"
${OC_CMD:-oc} get sa -n "$DEFAULT_NAMESPACE"

echo -e "\nÃ°Å¸â€™Â¡ ${CYAN}Note : Si un pod reste en 'CreateContainerConfigError' ou 'CrashLoopBackOff' avec des erreurs de permission, vÃƒÂ©rifiez que le ServiceAccount utilisÃƒÂ© par le pod possÃƒÂ¨de bien les droits dans la SCC correspondante.${NC}"
echo -e "${CYAN}--------------------------------------------------------------------------------${NC}"
read -p "Appuyez sur EntrÃƒÂ©e pour revenir au menu..."
