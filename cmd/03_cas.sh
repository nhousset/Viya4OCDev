#!/bin/bash
# TITLE: Gestion & Statut du Moteur CAS (Global, Opérateur, Contrôleur)

# --- Couleurs ---
CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BOLD='\033[1m'
NC='\033[0m'

# ==============================================================================
# CONFIGURATION ET CHARGEMENT DYNAMIQUE DES SERVEURS CAS
# ==============================================================================
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PROFILE=${PROFILE_NAME:-"default"}
CAS_FILE="$SCRIPT_DIR/.cas_servers_$PROFILE"

CAS_SERVERS=()

load_cas_servers() {
    CAS_SERVERS=()
    # Si le fichier n'existe pas, on le crée avec la valeur par défaut
    if [ ! -f "$CAS_FILE" ]; then
        echo "default" > "$CAS_FILE"
    fi
    
    # Lecture du fichier
    while IFS= read -r line || [ -n "$line" ]; do
        line=$(echo "$line" | xargs) # Trim
        if [ -n "$line" ] && [[ ! "$line" =~ ^# ]]; then
            CAS_SERVERS+=("$line")
        fi
    done < "$CAS_FILE"
}

ajouter_serveur_cas() {
    echo -e "\n${CYAN}=== [ AJOUTER UN SERVEUR CAS ] ===${NC}"
    echo -e "Le serveur sera rattaché au profil : ${YELLOW}$PROFILE${NC}"
    read -p "👉 Nom du nouveau serveur CAS (ex: shared-default, experimentation) : " new_cas
    
    if [ -n "$new_cas" ]; then
        if grep -qx "$new_cas" "$CAS_FILE" 2>/dev/null; then
            echo -e "${YELLOW}Ce serveur est déjà dans la liste pour ce profil.${NC}"
        else
            echo "$new_cas" >> "$CAS_FILE"
            echo -e "${GREEN}✅ Serveur '$new_cas' ajouté avec succès.${NC}"
            load_cas_servers
        fi
    else
        echo -e "${RED}Nom invalide ou vide.${NC}"
    fi
    sleep 1.5
}

# Initialisation au démarrage du script
load_cas_servers

# ==============================================================================
# FONCTIONS D'ACTION
# ==============================================================================

afficher_statut_global() {
    echo -e "\n${CYAN}=== [ STATUT GLOBAL DU MOTEUR CAS ] ===${NC}"
    
    echo -e "${YELLOW}⚙️  Ressources CASDeployments :${NC}"
    ${OC_CMD:-oc} get casdeployments -n "$DEFAULT_NAMESPACE" 2>/dev/null || echo -e "${RED}Aucun CASDeployment trouvé.${NC}"
    
    echo -e "\n${YELLOW}🖥️  Tous les pods du sous-système CAS :${NC}"
    ${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" -l app.kubernetes.io/managed-by=sas-cas-operator 2>/dev/null
    
    echo -e "\n${YELLOW}📈 Consommation CPU/RAM des noeuds CAS :${NC}"
    ${OC_CMD:-oc} adm top pods -n "$DEFAULT_NAMESPACE" -l app.kubernetes.io/managed-by=sas-cas-operator 2>/dev/null || echo -e "${RED}Métriques indisponibles.${NC}"
    
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    read -p "Appuyez sur Entrée pour continuer..."
}

menu_action_pod() {
    local pod_name="$1"
    
    # Extraction dynamique du conteneur principal pour éviter les erreurs "a container name must be specified" (sidecars)
    local container_name=$(${OC_CMD:-oc} get pod "$pod_name" -n "$DEFAULT_NAMESPACE" -o jsonpath='{.spec.containers[0].name}' 2>/dev/null)
    local c_opt=""
    if [ -n "$container_name" ]; then
        c_opt="-c $container_name"
    fi

    while true; do
        clear
        echo -e "\n${CYAN}=== [ ACTIONS SUR LE POD : ${YELLOW}$pod_name${CYAN} ] ===${NC}"
        echo -e "Namespace : ${YELLOW}$DEFAULT_NAMESPACE${NC}\n"
        
        echo -e " ${BOLD}1)${NC} 📄 Afficher les logs (100 dernières lignes)"
        echo -e " ${BOLD}2)${NC} 🔎 Suivre les logs en direct (tail -f)"
        echo -e " ${BOLD}3)${NC} 📋 Décrire le pod (oc describe)"
        echo -e " ${BOLD}4)${NC} 📈 Voir la consommation CPU/RAM (oc adm top)"
        echo -e " ${BOLD}5)${NC} 🗑️  Supprimer le pod (oc delete pod) - ${RED}⚠️ DANGER${NC}"
        echo -e "${CYAN}--------------------------------------------------------------${NC}"
        echo -e " ${RED}r)${NC} Retour à la liste des pods"
        echo ""
        read -p "👉 Votre choix : " act_choice
        
        case "$act_choice" in
            1)
                echo -e "\n${YELLOW}📄 Logs de $pod_name :${NC}"
                ${OC_CMD:-oc} logs "$pod_name" $c_opt -n "$DEFAULT_NAMESPACE" --tail=100
                echo -e "\n${CYAN}--------------------------------------------------------------${NC}"
                read -p "Appuyez sur Entrée pour continuer..."
                ;;
            2)
                echo -e "\n${YELLOW}⏳ Suivi des logs (Ctrl+C pour quitter)...${NC}"
                ${OC_CMD:-oc} logs -f "$pod_name" $c_opt -n "$DEFAULT_NAMESPACE" --tail=50
                ;;
            3)
                echo -e "\n${YELLOW}📋 Description de $pod_name :${NC}"
                ${OC_CMD:-oc} describe pod "$pod_name" -n "$DEFAULT_NAMESPACE"
                echo -e "\n${CYAN}--------------------------------------------------------------${NC}"
                read -p "Appuyez sur Entrée pour continuer..."
                ;;
            4)
                echo -e "\n${YELLOW}📈 Consommation de $pod_name :${NC}"
                ${OC_CMD:-oc} adm top pod "$pod_name" -n "$DEFAULT_NAMESPACE" 2>/dev/null || echo -e "${RED}Métriques indisponibles.${NC}"
                echo -e "\n${CYAN}--------------------------------------------------------------${NC}"
                read -p "Appuyez sur Entrée pour continuer..."
                ;;
            5)
                echo -e "\n${RED}⚠️ ATTENTION : La suppression d'un pod CAS (surtout le controller) peut interrompre le service.${NC}"
                read -p "👉 Voulez-vous VRAIMENT supprimer $pod_name ? (o/N) : " confirm_del
                if [[ "$confirm_del" =~ ^[oO]$ ]]; then
                    ${OC_CMD:-oc} delete pod "$pod_name" -n "$DEFAULT_NAMESPACE"
                    echo -e "${GREEN}✅ Commande de suppression envoyée.${NC}"
                    sleep 2
                    return # On quitte le sous-menu car le pod n'existe plus
                else
                    echo -e "${GREEN}Action annulée.${NC}"
                    sleep 1
                fi
                ;;
            r|R)
                return
                ;;
            *)
                echo -e "${RED}Choix invalide.${NC}"
                sleep 1
                ;;
        esac
    done
}

afficher_statut_cas() {
    local cas_name="$1"
    
    while true; do
        clear
        echo -e "\n${CYAN}=== [ STATUT DU SERVEUR CAS : ${YELLOW}$cas_name${CYAN} ] ===${NC}"
        
        echo -e "${YELLOW}⚙️  Ressource CASDeployment :${NC}"
        ${OC_CMD:-oc} get casdeployment "$cas_name" -n "$DEFAULT_NAMESPACE" 2>/dev/null || echo -e "${RED}CASDeployment '$cas_name' introuvable.${NC}"
        
        echo -e "\n${YELLOW}🖥️  Pods associés (Controller & Workers) :${NC}"
        
        # On stocke la liste des pods pour faire un menu
        local pod_list=$(${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" -l "casoperator.sas.com/server=$cas_name" --no-headers 2>/dev/null | awk '{print $1}')
        
        if [ -z "$pod_list" ]; then
            echo -e "${RED}Aucun pod trouvé pour le serveur CAS '$cas_name'.${NC}"
            echo -e "${CYAN}--------------------------------------------------------------${NC}"
            read -p "Appuyez sur Entrée pour revenir..."
            return
        fi
        
        # Affichage propre avec entêtes
        ${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" -l "casoperator.sas.com/server=$cas_name" 2>/dev/null
        
        echo -e "\n${CYAN}Sélectionnez un pod pour interagir avec (logs, describe, top, delete...) :${NC}"
        echo -e "${CYAN}--------------------------------------------------------------${NC}"
        
        local pod_array=($pod_list)
        for i in "${!pod_array[@]}"; do
            echo -e " ${BOLD}$((i+1)))${NC} ${pod_array[$i]}"
        done
        echo -e "${CYAN}--------------------------------------------------------------${NC}"
        echo -e " ${RED}r)${NC} Retour"
        echo ""
        read -p "👉 Votre choix : " pod_choice
        
        if [[ "$pod_choice" == "r" || "$pod_choice" == "R" ]]; then
            return
        fi
        
        if [[ "$pod_choice" =~ ^[0-9]+$ ]] && [ "$pod_choice" -ge 1 ] && [ "$pod_choice" -le "${#pod_array[@]}" ]; then
            local selected_pod="${pod_array[$((pod_choice-1))]}"
            menu_action_pod "$selected_pod"
        else
            echo -e "${RED}Choix invalide.${NC}"
            sleep 1
        fi
    done
}

arreter_cas() {
    local cas_name="$1"
    echo -e "\n${RED}⚠️  ATTENTION : L'arrêt du serveur CAS va déconnecter toutes les sessions analytiques en cours et purger les données in-memory non sauvegardées.${NC}"
    read -p "👉 Voulez-vous VRAIMENT arrêter le CAS '$cas_name' ? (o/N) : " confirm
    
    if [[ "$confirm" =~ ^[oO]$ ]]; then
        echo -e "${YELLOW}⏳ Envoi de l'instruction d'arrêt (Shutdown) à l'opérateur...${NC}"
        ${OC_CMD:-oc} patch casdeployment "$cas_name" -n "$DEFAULT_NAMESPACE" --type=merge -p '{"spec":{"shutdown":true}}'
        echo -e "${GREEN}✅ Commande d'arrêt envoyée. Les pods vont se terminer progressivement.${NC}"
    else
        echo -e "${GREEN}Action annulée.${NC}"
    fi
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    read -p "Appuyez sur Entrée pour continuer..."
}

demarrer_cas() {
    local cas_name="$1"
    echo -e "\n${YELLOW}⏳ Envoi de l'instruction de démarrage à l'opérateur...${NC}"
    ${OC_CMD:-oc} patch casdeployment "$cas_name" -n "$DEFAULT_NAMESPACE" --type=merge -p '{"spec":{"shutdown":false}}'
    echo -e "${GREEN}✅ Commande de démarrage envoyée. L'opérateur va provisionner le Controller puis les Workers.${NC}"
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    read -p "Appuyez sur Entrée pour continuer..."
}

afficher_logs_et_statut() {
    local component_name="$1"
    local search_string="$2"
    
    echo -e "\n${CYAN}=== [ $component_name ] ===${NC}"
    echo -e "${YELLOW}🖥️  Statut du/des Pod(s) :${NC}"
    
    local pod_list=$(${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" --no-headers 2>/dev/null | grep "$search_string" | awk '{print $1}')
    
    if [ -z "$pod_list" ]; then
        echo -e "${RED}Aucun pod trouvé pour $component_name ($search_string).${NC}"
        echo -e "${CYAN}--------------------------------------------------------------${NC}"
        read -p "Appuyez sur Entrée pour revenir au menu..."
        return
    fi
    
    ${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" 2>/dev/null | head -n 1
    ${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" 2>/dev/null | grep "$search_string"
    
    local first_pod=$(echo "$pod_list" | head -n 1)
    
    local container_name=$(${OC_CMD:-oc} get pod "$first_pod" -n "$DEFAULT_NAMESPACE" -o jsonpath='{.spec.containers[0].name}' 2>/dev/null)
    local c_opt=""
    if [ -n "$container_name" ]; then
        c_opt="-c $container_name"
    fi
    
    echo -e "\n${YELLOW}📄 Logs récents de $first_pod (10 dernières lignes) :${NC}"
    ${OC_CMD:-oc} logs "$first_pod" $c_opt -n "$DEFAULT_NAMESPACE" --tail=10
    
    echo -e "\n${CYAN}--------------------------------------------------------------${NC}"
    read -p "👉 Voulez-vous suivre les logs en temps réel (tail -f) ? (o/N) : " voir_logs
    if [[ "$voir_logs" =~ ^[oO]$ ]]; then
        echo -e "${YELLOW}⏳ Affichage des logs en temps réel (Appuyez sur Ctrl+C pour quitter)...${NC}"
        ${OC_CMD:-oc} logs -f "$first_pod" $c_opt -n "$DEFAULT_NAMESPACE" --tail=50
    fi
    
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    read -p "Appuyez sur Entrée pour revenir au menu..."
}

choisir_et_agir() {
    local action_msg="$1"
    local func_to_call="$2"
    
    echo -e "\n${CYAN}Sélectionnez le serveur CAS pour l'action : ${YELLOW}$action_msg${NC}"
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    for i in "${!CAS_SERVERS[@]}"; do
        echo -e " ${BOLD}$((i+1)))${NC} ${CAS_SERVERS[$i]}"
    done
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    echo -e " ${RED}a)${NC} Annuler"
    echo ""
    read -p "👉 Votre choix : " srv_choice
    
    if [[ "$srv_choice" == "a" || "$srv_choice" == "A" ]]; then
        return
    fi
    
    if [[ "$srv_choice" =~ ^[0-9]+$ ]] && [ "$srv_choice" -ge 1 ] && [ "$srv_choice" -le "${#CAS_SERVERS[@]}" ]; then
        local target_cas="${CAS_SERVERS[$((srv_choice-1))]}"
        $func_to_call "$target_cas"
    else
        echo -e "${RED}Choix invalide.${NC}"
        sleep 1
    fi
}

# ==============================================================================
# GESTION DE L'AFFICHAGE ET CADRE DE PRODUCTION
# ==============================================================================
local IS_PROD="false"
if [[ "${ENV_TYPE,,}" == *"prod"* ]]; then
    IS_PROD="true"
fi
local IW=92

m_echo() {
    local text="$1"
    if [ "$IS_PROD" == "true" ]; then
        local clean_text=$(echo -e "$text" | sed 's/\x1b\[[0-9;]*m//g')
        local len=${#clean_text}
        local pad=$((IW - len))
        [ $pad -lt 0 ] && pad=0
        echo -e "${RED}|${NC}${text}$(printf '%*s' "$pad" "")${RED}|${NC}"
    else
        echo -e "${text}"
    fi
}

# ==============================================================================
# MENU PRINCIPAL
# ==============================================================================
while true; do
    clear
    
    if [ "$IS_PROD" == "true" ]; then
        echo -e "${RED}+$(printf '%*s' "$IW" | tr ' ' '-')+${NC}"
        m_echo " ${BOLD}${RED}!!! ATTENTION - ENVIRONNEMENT DE PRODUCTION !!!${NC}"
        echo -e "${RED}+$(printf '%*s' "$IW" | tr ' ' '=')+${NC}"
    else
        echo -e "${BLUE}============================================================================================${NC}"
    fi

    m_echo "${BOLD}   ☁️  GESTION & STATUT DU MOTEUR CAS${NC}"
    m_echo "   Namespace : ${CYAN}${DEFAULT_NAMESPACE:-'Par défaut'}${NC} | Profil : ${PURPLE}${PROFILE}${NC}"
    
    if [ "$IS_PROD" == "true" ]; then
        echo -e "${RED}+$(printf '%*s' "$IW" | tr ' ' '=')+${NC}"
    else
        echo -e "${BLUE}============================================================================================${NC}"
    fi
    
    # --- DASHBOARD DES SERVEURS CAS ---
    m_echo " ${YELLOW}🖥️  Tableau de bord des serveurs enregistrés :${NC}"
    if [ "$DRY_RUN" == "true" ]; then
        for cas in "${CAS_SERVERS[@]}"; do
            m_echo "    • ${BOLD}$cas${NC} : 🟡 ${YELLOW}[DRY-RUN] Simulé${NC}"
        done
    else
        for cas in "${CAS_SERVERS[@]}"; do
            # 1. Vérifie si le CASDeployment existe
            if ! ${OC_CMD:-oc} get casdeployment "$cas" -n "$DEFAULT_NAMESPACE" >/dev/null 2>&1; then
                m_echo "    • ${BOLD}$cas${NC} : ⚪ ${CYAN}Non déployé (Inconnu)${NC}"
                continue
            fi
            
            # 2. Compte les pods en cours d'exécution
            running_pods=$(${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" -l "casoperator.sas.com/server=$cas" --field-selector=status.phase=Running --no-headers 2>/dev/null | wc -l)
            
            if [ "$running_pods" -gt 0 ]; then
                m_echo "    • ${BOLD}$cas${NC} : 🟢 ${GREEN}Démarré ($running_pods pods actifs)${NC}"
            else
                m_echo "    • ${BOLD}$cas${NC} : 🔴 ${RED}Arrêté (0 pod)${NC}"
            fi
        done
    fi
    
    if [ "$IS_PROD" == "true" ]; then
        echo -e "${RED}+$(printf '%*s' "$IW" | tr ' ' '-')+${NC}"
    else
        echo -e "${BLUE}--------------------------------------------------------------------------------------------${NC}"
    fi
    
    m_echo " ${BOLD}${CYAN}1)${NC} 📊 Statut Global (Ressources, Pods, Déploiements)"
    m_echo " ${BOLD}${CYAN}2)${NC} 🔎 Inspecter et gérer les Pods d'un serveur CAS (Logs, Describe, etc)"
    m_echo " ${BOLD}${CYAN}3)${NC} 🚀 Démarrer un serveur CAS (Start)"
    m_echo " ${BOLD}${CYAN}4)${NC} 🛑 Arrêter un serveur CAS (Stop)"
    m_echo ""
    m_echo " ${BOLD}${CYAN}5)${NC} 🛠️  Opérateur CAS (sas-cas-operator) : Statut & Logs"
    m_echo " ${BOLD}${CYAN}6)${NC} ⚙️  Contrôleur CAS (sas-cas-control) : Statut & Logs"
    m_echo ""
    m_echo " ${BOLD}${CYAN}7)${NC} ➕ Ajouter un serveur CAS à la liste d'administration"
    
    if [ "$IS_PROD" == "true" ]; then
        echo -e "${RED}+$(printf '%*s' "$IW" | tr ' ' '-')+${NC}"
        m_echo "  ${RED}q)${NC} Retour au menu principal"
        echo -e "${RED}+$(printf '%*s' "$IW" | tr ' ' '=')+${NC}"
    else
        echo -e "${BLUE}--------------------------------------------------------------------------------------------${NC}"
        echo -e "  ${RED}q)${NC} Retour au menu principal"
        echo -e "${BLUE}============================================================================================${NC}"
    fi

    echo ""
    read -p "👉 Votre choix : " main_choice

    case "$main_choice" in
        1) afficher_statut_global ;;
        2) choisir_et_agir "Inspecter les Pods" afficher_statut_cas ;;
        3) choisir_et_agir "Démarrer (Start)" demarrer_cas ;;
        4) choisir_et_agir "Arrêter (Stop)" arreter_cas ;;
        5) afficher_logs_et_statut "Opérateur CAS" "sas-cas-operator" ;;
        6) afficher_logs_et_statut "Contrôleur CAS" "sas-cas-control" ;;
        7) ajouter_serveur_cas ;;
        q|Q) break ;;
        *) echo -e "${RED}Choix invalide.${NC}" ; sleep 1 ;;
    esac
done
