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
    echo -e " ${BOLD}3)${NC} 🧹 Supprimer TOUS les Jobs terminés (Completed) - ${RED}Nettoyage${NC}"
    echo -e " ${BOLD}4)${NC} 🗑️  Supprimer TOUS les Jobs en échec (Failed) - ${RED}Nettoyage${NC}"
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
        4)
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
