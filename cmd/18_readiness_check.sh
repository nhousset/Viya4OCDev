#!/bin/bash
# TITLE: Startup Status (Readiness Service)

# --- Couleurs ---
CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
PURPLE='\033[0;35m'
BOLD='\033[1m'
NC='\033[0m'

# ==============================================================================
# FONCTIONS
# ==============================================================================

check_readiness() {
    echo -e "\n${CYAN}=== [ VÃƒâ€°RIFICATION DE LA DISPONIBILITÃƒâ€° (READINESS) ] ===${NC}"
    echo -e "${YELLOW}Cette commande va interroger le pod 'sas-readiness'.${NC}"
    
    read -p "Ã°Å¸â€˜â€° Entrez le dÃƒÂ©lai d'attente maximum en secondes (ex: 1800 pour 30min) [par dÃƒÂ©faut: 60] : " timeout_val
    timeout_val=${timeout_val:-60}
    
    echo -e "\n${YELLOW}Ã¢ÂÂ³ Attente que l'environnement soit dÃƒÂ©clarÃƒÂ© 'Ready' (Timeout : ${timeout_val}s)...${NC}"
    
    # Commande officielle oc wait
    oc wait -n "$DEFAULT_NAMESPACE" \
        --for=condition=ready pod \
        --selector="app.kubernetes.io/name=sas-readiness" \
        --timeout="${timeout_val}s"
        
    local ret_code=$?
    
    if [ $ret_code -eq 0 ]; then
        echo -e "\n${GREEN}Ã¢Å“â€¦ EXCELLENTE NOUVELLE : L'environnement SAS Viya 4 est PRÃƒÅ T et opÃƒÂ©rationnel !${NC}"
    else
        echo -e "\n${RED}Ã¢ÂÅ’ DÃƒâ€°LAI DÃƒâ€°PASSÃƒâ€° : L'environnement n'est pas encore prÃƒÂªt ou rencontre un problÃƒÂ¨me.${NC}"
        echo -e "Ã°Å¸â€™Â¡ Astuce : Utilisez l'option 2 pour vÃƒÂ©rifier quels composants bloquent le dÃƒÂ©marrage."
    fi
    
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    read -p "Appuyez sur EntrÃƒÂ©e pour continuer..."
}

view_logs() {
    echo -e "\n${CYAN}=== [ ANALYSE DES LOGS DU READINESS SERVICE ] ===${NC}"
    echo -e "${YELLOW}Analyse en cours pour identifier les blocages potentiels...${NC}"
    
    local pod_name=$(oc get pods -n "$DEFAULT_NAMESPACE" -l "app.kubernetes.io/name=sas-readiness" -o custom-columns=":metadata.name" --no-headers 2>/dev/null | head -n 1)
    
    if [ -z "$pod_name" ]; then
        echo -e "${RED}Ã¢ÂÅ’ Erreur : Aucun pod 'sas-readiness' trouvÃƒÂ© dans le namespace '$DEFAULT_NAMESPACE'.${NC}"
    else
        echo -e "\n${BOLD}Pod dÃƒÂ©tectÃƒÂ© : $pod_name${NC}\n"
        
        echo -e "${PURPLE}Ã°Å¸â€Â 1. Recherche des erreurs bloquantes rÃƒÂ©centes (mot-clÃƒÂ© 'failed') :${NC}"
        # On va chercher les lignes avec failed dans les 500 derniÃƒÂ¨res lignes (souvent des JSON)
        local failed_logs=$(oc logs "$pod_name" -n "$DEFAULT_NAMESPACE" --tail=500 2>/dev/null | grep -i "failed" | tail -n 5)
        
        if [ -n "$failed_logs" ]; then
            echo -e "${RED}$failed_logs${NC}"
            echo -e "\n${YELLOW}Ã°Å¸â€™Â¡ Il semblerait que certains composants ne soient pas encore prÃƒÂªts.${NC}"
        else
            echo -e "${GREEN}Ã¢Å“â€¦ Aucune erreur 'failed' trouvÃƒÂ©e rÃƒÂ©cemment.${NC}"
        fi
        
        echo -e "\n${PURPLE}Ã°Å¸Å¸Â¢ 2. Recherche de la validation finale ('All checks passed') :${NC}"
        local passed_logs=$(oc logs "$pod_name" -n "$DEFAULT_NAMESPACE" --tail=500 2>/dev/null | grep -i "All checks passed" | tail -n 1)
        
        if [ -n "$passed_logs" ]; then
            echo -e "${GREEN}$passed_logs${NC}"
            echo -e "\n${GREEN}Ã°Å¸Å¡â‚¬ Tout est au vert selon le Readiness Service !${NC}"
        else
            echo -e "${YELLOW}Ã¢ÂÂ³ Le message 'All checks passed' n'a pas encore ÃƒÂ©tÃƒÂ© repÃƒÂ©rÃƒÂ© (Viya est probablement en train de dÃƒÂ©marrer).${NC}"
        fi
        
        echo -e "\n${CYAN}--------------------------------------------------------------${NC}"
        read -p "Ã°Å¸â€˜â€° Voulez-vous afficher les 20 derniÃƒÂ¨res lignes brutes des logs ? (o/N) : " view_raw
        if [[ "$view_raw" =~ ^[oO]$ ]]; then
            echo -e "\n${YELLOW}Ã°Å¸â€œâ€ž Logs bruts (20 derniÃƒÂ¨res lignes) :${NC}"
            oc logs "$pod_name" -n "$DEFAULT_NAMESPACE" --tail=20 2>/dev/null
        fi
    fi
    
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    read -p "Appuyez sur EntrÃƒÂ©e pour revenir au menu..."
}

follow_logs() {
    echo -e "\n${CYAN}=== [ SUIVI EN TEMPS RÃƒâ€°EL (TAIL -F) ] ===${NC}"
    local pod_name=$(oc get pods -n "$DEFAULT_NAMESPACE" -l "app.kubernetes.io/name=sas-readiness" -o custom-columns=":metadata.name" --no-headers 2>/dev/null | head -n 1)
    
    if [ -z "$pod_name" ]; then
        echo -e "${RED}Ã¢ÂÅ’ Erreur : Aucun pod 'sas-readiness' trouvÃƒÂ© dans le namespace '$DEFAULT_NAMESPACE'.${NC}"
        sleep 2
    else
        echo -e "${YELLOW}Ã¢ÂÂ³ Affichage des logs en direct. Appuyez sur Ctrl+C pour quitter...${NC}"
        echo -e "${CYAN}--------------------------------------------------------------${NC}"
        oc logs -f "$pod_name" -n "$DEFAULT_NAMESPACE" --tail=20
    fi
}

# ==============================================================================
# MENU PRINCIPAL
# ==============================================================================
while true; do
    clear
    echo -e "${CYAN}=== [ STATUT DE DÃƒâ€°MARRAGE (SAS READINESS) ] ===${NC}"
    echo -e "Namespace : ${YELLOW}$DEFAULT_NAMESPACE${NC}\n"
    
    echo -e " ${BOLD}1)${NC} Ã¢ÂÂ³ Tester la disponibilitÃƒÂ© de SAS Viya (oc wait)"
    echo -e " ${BOLD}2)${NC} Ã°Å¸â€œâ€ž Consulter & Analyser les logs du Readiness Service (voir ce qui bloque)"
    echo -e " ${BOLD}3)${NC} Ã°Å¸â€Å½ Suivre les logs en temps rÃƒÂ©el (tail -f)"
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    echo -e " ${RED}q)${NC} Retour au menu principal"
    echo ""
    read -p "Ã°Å¸â€˜â€° Votre choix : " main_choice

    case "$main_choice" in
        1) check_readiness ;;
        2) view_logs ;;
        3) follow_logs ;;
        q|Q) break ;;
        *) echo -e "${RED}Choix invalide.${NC}" ; sleep 1 ;;
    esac
done
