#!/bin/bash
# TITLE: Interactive Pod Management (Search, Logs, Shell)

# --- Couleurs ---
CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BOLD='\033[1m'
NC='\033[0m'

# Variable globale pour conserver la recherche
FILTER=""

# ==============================================================================
# SOUS-MENU DES ACTIONS SUR UN POD
# ==============================================================================
action_menu() {
    local pod_name="$1"
    
    while true; do
        clear
        echo -e "${CYAN}==============================================================${NC}"
        echo -e " Ã°Å¸â€ºÂ Ã¯Â¸Â  ACTIONS SUR LE POD : ${YELLOW}$pod_name${NC}"
        echo -e "${CYAN}==============================================================${NC}"
        
        # Affichage rapide du statut actuel du pod
        oc get pod "$pod_name" -n "$DEFAULT_NAMESPACE" | sed -n '2p' | awk '{print " Statut actuel : \033[1;33m" $3 "\033[0m | Restarts : " $4}'
        echo -e "${CYAN}--------------------------------------------------------------${NC}"
        
        echo -e " ${BOLD}1)${NC} Ã°Å¸â€œâ€ž Afficher les logs (200 derniÃƒÂ¨res lignes)"
        echo -e " ${BOLD}2)${NC} Ã°Å¸â€â€ž Suivre les logs en direct (tail -f)"
        echo -e " ${BOLD}3)${NC} Ã°Å¸â€Â DÃƒÂ©crire le pod (oc describe)"
        echo -e " ${BOLD}4)${NC} Ã¢Å¡Â Ã¯Â¸Â  Voir les ÃƒÂ©vÃƒÂ©nements (Events) de ce pod"
        echo -e " ${BOLD}5)${NC} Ã°Å¸â€™Â» Ouvrir un terminal dans le pod (oc rsh)"
        echo -e " ${BOLD}6)${NC} Ã°Å¸â€”â€˜Ã¯Â¸Â  RedÃƒÂ©marrer le pod (Delete avec confirmation)"
        echo -e "${CYAN}--------------------------------------------------------------${NC}"
        echo -e " ${RED}r)${NC} Retour ÃƒÂ  la liste des pods"
        echo ""
        read -p "Ã°Å¸â€˜â€° Votre choix : " act_choice

        echo -e "${CYAN}--------------------------------------------------------------${NC}"
        
        case "$act_choice" in
            1)
                echo "RÃƒÂ©cupÃƒÂ©ration des logs..."
                oc logs "$pod_name" -n "$DEFAULT_NAMESPACE" --tail=200 | less -R
                ;;
            2)
                echo -e "${YELLOW}Appuyez sur Ctrl+C pour quitter le suivi.${NC}"
                oc logs "$pod_name" -n "$DEFAULT_NAMESPACE" -f
                ;;
            3)
                oc describe pod "$pod_name" -n "$DEFAULT_NAMESPACE" | less -R
                ;;
            4)
                oc get events -n "$DEFAULT_NAMESPACE" --field-selector involvedObject.name="$pod_name"
                read -p "Appuyez sur EntrÃƒÂ©e pour continuer..."
                ;;
            5)
                echo -e "${YELLOW}Connexion au pod $pod_name... (Tapez 'exit' pour en sortir)${NC}"
                oc rsh -n "$DEFAULT_NAMESPACE" "$pod_name"
                ;;
            6)
                # CONFIRMATION SÃƒâ€°CURISÃƒâ€°E
                echo -e "${RED}Ã¢Å¡Â Ã¯Â¸Â  ATTENTION : La suppression d'un pod force le cluster ÃƒÂ  l'interrompre et ÃƒÂ  en recrÃƒÂ©er un neuf.${NC}"
                read -p "Ã°Å¸â€˜â€° Voulez-vous VRAIMENT redÃƒÂ©marrer le pod '$pod_name' ? (o/N) : " confirm
                
                # VÃƒÂ©rifie si l'utilisateur a tapÃƒÂ© 'o' ou 'O'. Sinon, on annule.
                if [[ "$confirm" =~ ^[oO]$ ]]; then
                    echo -e "${YELLOW}Suppression en cours...${NC}"
                    oc delete pod "$pod_name" -n "$DEFAULT_NAMESPACE"
                    echo -e "${GREEN}Ã¢Å“â€¦ Pod supprimÃƒÂ© avec succÃƒÂ¨s.${NC}"
                    read -p "Appuyez sur EntrÃƒÂ©e pour rafraÃƒÂ®chir la liste..."
                    return # Quitte le sous-menu pour rafraÃƒÂ®chir la liste principale
                else
                    echo -e "${GREEN}Action annulÃƒÂ©e. Le pod n'a pas ÃƒÂ©tÃƒÂ© touchÃƒÂ©.${NC}"
                    sleep 1.5
                fi
                ;;
            r)
                return # Quitte la fonction et remonte ÃƒÂ  la boucle principale
                ;;
            *)
                echo -e "${RED}Choix invalide.${NC}"
                sleep 1
                ;;
        esac
    done
}

# ==============================================================================
# MENU PRINCIPAL (LISTE & RECHERCHE)
# ==============================================================================
while true; do
    clear
    echo -e "${CYAN}=== [ EXPLORATEUR INTERACTIF DE PODS ] ===${NC}"
    echo -e "Namespace : ${YELLOW}$DEFAULT_NAMESPACE${NC}"
    
    if [ -z "$FILTER" ]; then
        echo -e "Filtre actif : ${GREEN}Aucun (Tous les pods)${NC}\n"
        PODS_RAW=$(oc get pods -n "$DEFAULT_NAMESPACE" --no-headers -o custom-columns=NAME:.metadata.name,STATUS:.status.phase 2>/dev/null)
    else
        echo -e "Filtre actif : ${YELLOW}'$FILTER'${NC} (Tapez 'c' pour effacer)\n"
        PODS_RAW=$(oc get pods -n "$DEFAULT_NAMESPACE" --no-headers -o custom-columns=NAME:.metadata.name,STATUS:.status.phase 2>/dev/null | grep -i "$FILTER")
    fi

    POD_NAMES=()
    
    if [ -z "$PODS_RAW" ]; then
        echo -e "${RED}Aucun pod trouvÃƒÂ© avec ces critÃƒÂ¨res.${NC}"
    else
        i=1
        while read -r pod_name pod_status; do
            [ -z "$pod_name" ] && continue
            
            POD_NAMES+=("$pod_name")
            
            if [ "$pod_status" == "Running" ] || [ "$pod_status" == "Completed" ]; then
                stat_color="${GREEN}"
            else
                stat_color="${RED}"
            fi
            
            printf " ${BOLD}${CYAN}%3d)${NC} %-55s [${stat_color}%s${NC}]\n" "$i" "$pod_name" "$pod_status"
            ((i++))
        done <<< "$PODS_RAW"
    fi

    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    echo -e " ${BOLD}s)${NC} Ã°Å¸â€Â Chercher / Filtrer"
    if [ -n "$FILTER" ]; then
        echo -e " ${BOLD}c)${NC} Ã°Å¸Â§Â¹ Effacer le filtre"
    fi
    echo -e " ${RED}q)${NC} Ã°Å¸Å¡Âª Quitter l'explorateur"
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    
    read -p "Ã°Å¸â€˜â€° Choisissez un pod (1-${#POD_NAMES[@]}) ou une action : " choice

    if [[ "$choice" == "q" ]]; then
        break 
        
    elif [[ "$choice" == "s" ]]; then
        read -p "Entrez un mot-clÃƒÂ© (ex: logon, compute, cas) : " new_filter
        FILTER="$new_filter"
        
    elif [[ "$choice" == "c" ]]; then
        FILTER=""
        
    elif [[ "$choice" =~ ^[0-9]+$ ]] && [ "$choice" -ge 1 ] && [ "$choice" -le "${#POD_NAMES[@]}" ]; then
        selected_pod="${POD_NAMES[$((choice-1))]}"
        action_menu "$selected_pod"
        
    else
        echo -e "${RED}Choix invalide.${NC}"
        sleep 1
    fi
done
