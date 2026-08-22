#!/bin/bash
# TITLE: Global Stop / Start Procedure for SAS Viya 4

# --- Couleurs ---
CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BOLD='\033[1m'
NC='\033[0m'

# ==============================================================================
# FONCTIONS D'ACTION
# ==============================================================================

start_viya() {
    echo -e "\n${YELLOW}Ã¢Å¡Â Ã¯Â¸Â  Lancement de la procÃƒÂ©dure de dÃƒÂ©marrage global de SAS Viya 4...${NC}"
    read -p "Ã°Å¸â€˜â€° Voulez-vous vraiment DÃƒâ€°MARRER l'environnement dans le namespace '$DEFAULT_NAMESPACE' ? (o/N) : " confirm
    
    if [[ "$confirm" =~ ^[oO]$ ]]; then
        local job_name="sas-start-all-$(date +%s)"
        echo -e "\n${CYAN}CrÃƒÂ©ation du job : ${BOLD}$job_name${NC}"
        
        # Commande officielle de dÃƒÂ©marrage Viya 4
        oc create job "$job_name" --from cronjobs/sas-start-all -n "$DEFAULT_NAMESPACE"
        
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}Ã¢Å“â€¦ Job de dÃƒÂ©marrage crÃƒÂ©ÃƒÂ© avec succÃƒÂ¨s.${NC}"
            echo -e "${YELLOW}Vous pouvez suivre les logs du job en direct avec cette commande (ou utiliser l'option 3) :${NC}"
            echo -e "oc logs -f job/$job_name -n $DEFAULT_NAMESPACE"
        else
            echo -e "${RED}Ã¢ÂÅ’ Erreur lors de la crÃƒÂ©ation du job de dÃƒÂ©marrage.${NC}"
            echo -e "VÃƒÂ©rifiez que le CronJob 'sas-start-all' existe dans le namespace."
        fi
    else
        echo -e "${GREEN}Action annulÃƒÂ©e.${NC}"
    fi
}

stop_viya() {
    echo -e "\n${RED}Ã¢Å¡Â Ã¯Â¸Â  ATTENTION : Cette action va interrompre tous les services SAS Viya 4 en cours.${NC}"
    read -p "Ã°Å¸â€˜â€° Voulez-vous vraiment ARRÃƒÅ TER l'environnement dans le namespace '$DEFAULT_NAMESPACE' ? (o/N) : " confirm
    
    if [[ "$confirm" =~ ^[oO]$ ]]; then
        local job_name="sas-stop-all-$(date +%s)"
        echo -e "\n${CYAN}CrÃƒÂ©ation du job : ${BOLD}$job_name${NC}"
        
        # Commande officielle d'arrÃƒÂªt Viya 4
        oc create job "$job_name" --from cronjobs/sas-stop-all -n "$DEFAULT_NAMESPACE"
        
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}Ã¢Å“â€¦ Job d'arrÃƒÂªt crÃƒÂ©ÃƒÂ© avec succÃƒÂ¨s.${NC}"
            echo -e "${YELLOW}Vous pouvez suivre les logs du job en direct avec cette commande (ou utiliser l'option 3) :${NC}"
            echo -e "oc logs -f job/$job_name -n $DEFAULT_NAMESPACE"
        else
            echo -e "${RED}Ã¢ÂÅ’ Erreur lors de la crÃƒÂ©ation du job d'arrÃƒÂªt.${NC}"
            echo -e "VÃƒÂ©rifiez que le CronJob 'sas-stop-all' existe dans le namespace."
        fi
    else
        echo -e "${GREEN}Action annulÃƒÂ©e.${NC}"
    fi
}

check_status() {
    echo -e "\n${CYAN}=== [ HISTORIQUE DES JOBS START / STOP ] ===${NC}"
    echo -e "${YELLOW}Derniers jobs de dÃƒÂ©marrage/arrÃƒÂªt :${NC}"
    
    # Affichage du header des jobs
    oc get jobs -n "$DEFAULT_NAMESPACE" 2>/dev/null | head -n 1
    
    # RÃƒÂ©cupÃƒÂ©ration des jobs filtrÃƒÂ©s et triÃƒÂ©s chronologiquement
    local jobs_list=$(oc get jobs -n "$DEFAULT_NAMESPACE" --sort-by=.metadata.creationTimestamp 2>/dev/null | grep -E "sas-start-all|sas-stop-all")
    
    if [ -n "$jobs_list" ]; then
        # Affichage des 5 derniers pour la lisibilitÃƒÂ©
        echo "$jobs_list" | tail -n 5
        
        echo -e "\n${CYAN}--------------------------------------------------------------${NC}"
        # On rÃƒÂ©cupÃƒÂ¨re le nom exact du dernier job crÃƒÂ©ÃƒÂ©
        local last_job=$(echo "$jobs_list" | tail -n 1 | awk '{print $1}')
        
        read -p "Ã°Å¸â€˜â€° Voulez-vous afficher les logs du dernier job ($last_job) ? (o/N) : " view_logs
        if [[ "$view_logs" =~ ^[oO]$ ]]; then
            echo -e "\n${YELLOW}Ã¢ÂÂ³ RÃƒÂ©cupÃƒÂ©ration des logs pour $last_job...${NC}"
            oc logs "job/$last_job" -n "$DEFAULT_NAMESPACE" 2>/dev/null || echo -e "${RED}Logs indisponibles (le job n'a peut-ÃƒÂªtre pas encore dÃƒÂ©marrÃƒÂ© ses pods).${NC}"
            echo -e "${CYAN}--------------------------------------------------------------${NC}"
        fi
    else
        echo -e "${RED}Aucun job sas-start-all ou sas-stop-all n'a ÃƒÂ©tÃƒÂ© trouvÃƒÂ© dans le namespace '$DEFAULT_NAMESPACE'.${NC}"
    fi
    
    read -p "Appuyez sur EntrÃƒÂ©e pour revenir au menu..."
}

# ==============================================================================
# MENU PRINCIPAL
# ==============================================================================
while true; do
    clear
    echo -e "${CYAN}=== [ DÃƒâ€°MARRAGE ET ARRÃƒÅ T DE SAS VIYA 4 ] ===${NC}"
    echo -e "Namespace : ${YELLOW}$DEFAULT_NAMESPACE${NC}\n"
    
    echo -e " ${BOLD}1)${NC} Ã°Å¸Å¡â‚¬ DÃƒÂ©marrer l'environnement SAS Viya 4 (sas-start-all)"
    echo -e " ${BOLD}2)${NC} Ã°Å¸â€ºâ€˜ ArrÃƒÂªter l'environnement SAS Viya 4 (sas-stop-all)"
    echo -e " ${BOLD}3)${NC} Ã°Å¸â€œÅ  Historique et Logs des actions de dÃƒÂ©marrage/arrÃƒÂªt"
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    echo -e " ${RED}q)${NC} Retour au menu principal"
    echo ""
    read -p "Ã°Å¸â€˜â€° Votre choix : " main_choice

    case "$main_choice" in
        1) start_viya ; sleep 2 ;;
        2) stop_viya ; sleep 2 ;;
        3) check_status ;;
        q|Q) break ;;
        *) echo -e "${RED}Choix invalide.${NC}" ; sleep 1 ;;
    esac
done
