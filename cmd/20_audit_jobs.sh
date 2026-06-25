#!/bin/bash
# TITLE: Audit & Gestion des Jobs Kubernetes

CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BOLD='\033[1m'
NC='\033[0m'

while true; do
    clear
    echo -e "${CYAN}=== [ AUDIT DES JOBS KUBERNETES ] ===${NC}"
    echo -e "Namespace : ${YELLOW}$DEFAULT_NAMESPACE${NC}\n"
    
    echo -e " ${BOLD}1)${NC} 📜 Lister tous les Jobs (Triés par âge)"
    echo -e " ${BOLD}2)${NC} ⚠️  Lister uniquement les Jobs en échec (Failed)"
    echo -e " ${BOLD}3)${NC} 🔎 Voir les Pods d'exécution associés (Job Executions)"
    echo -e " ${BOLD}4)${NC} 🚨 Scanner les logs d'un Job (Recherche Error, Panic, OOM...)"
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    echo -e " ${BOLD}5)${NC} 🧹 Supprimer TOUS les Jobs terminés (Completed) - ${RED}Nettoyage${NC}"
    echo -e " ${BOLD}6)${NC} 🗑️  Supprimer TOUS les Jobs en échec (Failed) - ${RED}Nettoyage${NC}"
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    echo -e " ${RED}r)${NC} Retour au menu"
    echo ""
    read -p "👉 Votre choix : " choice

    case "$choice" in
        1)
            echo -e "\n${YELLOW}Liste de tous les jobs :${NC}"
            ${OC_CMD:-oc} get jobs -n "$DEFAULT_NAMESPACE" --sort-by=.metadata.creationTimestamp
            read -p "Appuyez sur Entrée..."
            ;;
        2)
            echo -e "\n${RED}Liste des jobs en échec :${NC}"
            ${OC_CMD:-oc} get jobs -n "$DEFAULT_NAMESPACE" | awk '$3 == 0 || $2 == "0/1" {print $0}'
            read -p "Appuyez sur Entrée..."
            ;;
        3)
            echo -e "\n${YELLOW}Liste des Pods générés par des Jobs ou liés aux exécutions :${NC}"
            # On cherche les pods qui ont le label 'job-name' (Jobs K8s natifs) 
            # ou qui contiennent 'job' dans leur nom (comme sas-job-execution)
            ${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" | head -n 1
            ${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" | grep -i "job"
            echo ""
            read -p "Appuyez sur Entrée..."
            ;;
        4)
            echo -e "\n${CYAN}=== [ SCANNER DE LOGS DE JOBS ] ===${NC}"
            read -p "👉 Entrez le nom exact ou partiel du Job à analyser : " JOB_FILTER
            
            if [ -n "$JOB_FILTER" ]; then
                echo -e "${YELLOW}Recherche du pod associé à '$JOB_FILTER'...${NC}"
                
                # On tente d'abord de trouver un pod généré par un job précis (label job-name)
                POD_NAME=$(${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" -l job-name --no-headers 2>/dev/null | grep -i "$JOB_FILTER" | awk '{print $1}' | head -n 1)
                
                # Si non trouvé, on cherche n'importe quel pod correspondant (ex: un pod sas-job-execution)
                if [ -z "$POD_NAME" ]; then
                    POD_NAME=$(${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" --no-headers 2>/dev/null | grep -i "$JOB_FILTER" | awk '{print $1}' | head -n 1)
                fi

                if [ -n "$POD_NAME" ]; then
                    echo -e "${GREEN}✅ Pod ciblé : ${BOLD}$POD_NAME${NC}"
                    echo -e "${CYAN}Extraction des 100 dernières lignes et recherche d'erreurs critiques...${NC}"
                    echo -e "Mots-clés recherchés : ${RED}error, panic, killed, oom, unexpected, fatal${NC}"
                    echo -e "--------------------------------------------------------------\n"

                    # Récupération des logs (on inclut stderr avec 2>&1)
                    LOGS_OUT=$(${OC_CMD:-oc} logs "$POD_NAME" -n "$DEFAULT_NAMESPACE" --tail=100 2>&1)
                    
                    # On utilise grep avec -i (insensible à la casse), -E (regex étendue) et on force la couleur
                    FILTERED_LOGS=$(echo "$LOGS_OUT" | grep -iE --color=always "error|panic|killed|oom|unexpected|unexcepted|fatal")

                    if [ -z "$FILTERED_LOGS" ]; then
                        echo -e "${GREEN}✅ Aucune erreur critique trouvée dans les 100 dernières lignes.${NC}\n"
                        read -p "Voulez-vous afficher les 20 dernières lignes normales ? (o/N) : " show_norm
                        if [[ "$show_norm" =~ ^[oO]$ ]]; then
                            echo -e "\n${YELLOW}--- Fin des logs ---${NC}"
                            echo "$LOGS_OUT" | tail -n 20
                        fi
                    else
                        echo -e "${RED}⚠️  Alertes trouvées dans les logs :${NC}"
                        echo "$FILTERED_LOGS"
                    fi
                else
                    echo -e "${RED}❌ Aucun pod correspondant trouvé pour le filtre '$JOB_FILTER'.${NC}"
                fi
            fi
            echo ""
            read -p "Appuyez sur Entrée pour revenir au menu..."
            ;;
        5)
            echo -e "\n${RED}⚠️  ATTENTION : Nettoyage des jobs terminés (Completed)${NC}"
            echo -e "Namespace cible : ${YELLOW}$DEFAULT_NAMESPACE${NC}"
            read -p "👉 Confirmer la suppression de TOUS les jobs COMPLETED dans ce namespace (o/N) ? " confirm
            if [[ "$confirm" =~ ^[oO]$ ]]; then
                ${OC_CMD:-oc} delete jobs -n "$DEFAULT_NAMESPACE" --field-selector status.successful=1
                echo -e "${GREEN}✅ Jobs terminés supprimés.${NC}"
                sleep 2
            else
                echo -e "${YELLOW}Annulation.${NC}"
            fi
            ;;
        6)
            echo -e "\n${RED}⚠️  ATTENTION : Nettoyage des jobs en échec (Failed)${NC}"
            echo -e "Namespace cible : ${YELLOW}$DEFAULT_NAMESPACE${NC}"
            read -p "👉 Confirmer la suppression de TOUS les jobs FAILED dans ce namespace (o/N) ? " confirm
            if [[ "$confirm" =~ ^[oO]$ ]]; then
                ${OC_CMD:-oc} delete jobs -n "$DEFAULT_NAMESPACE" --field-selector status.successful=0
                echo -e "${GREEN}✅ Jobs en échec supprimés.${NC}"
                sleep 2
            else
                echo -e "${YELLOW}Annulation.${NC}"
            fi
            ;;
        r|R) break ;;
        *) echo -e "${RED}Choix invalide.${NC}" ; sleep 1 ;;
    esac
done
