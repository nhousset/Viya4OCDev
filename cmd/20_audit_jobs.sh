#!/bin/bash
# TITLE: Jobs Audit & Management (K8s & Viya Execution)

CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
PURPLE='\033[0;35m'
BOLD='\033[1m'
NC='\033[0m'

while true; do
    clear
    echo -e "${CYAN}=== [ AUDIT DES JOBS (KUBERNETES & SAS VIYA) ] ===${NC}"
    echo -e "Namespace : ${YELLOW}$DEFAULT_NAMESPACE${NC}\n"
    
    echo -e " ${YELLOW}--- Ã¢Å¡â„¢Ã¯Â¸Â  JOBS KUBERNETES (Sauvegardes, Purges, TÃƒÂ¢ches SystÃƒÂ¨me) ---${NC}"
    echo -e " ${BOLD}1)${NC} Ã°Å¸â€œÅ“ Lister tous les Jobs K8s (TriÃƒÂ©s par ÃƒÂ¢ge)"
    echo -e " ${BOLD}2)${NC} Ã¢Å¡Â Ã¯Â¸Â  Lister uniquement les Jobs K8s en ÃƒÂ©chec (Failed)"
    echo -e " ${BOLD}3)${NC} Ã°Å¸Â§Â¹ Supprimer TOUS les Jobs K8s terminÃƒÂ©s (Completed) - ${RED}Nettoyage${NC}"
    echo -e " ${BOLD}4)${NC} Ã°Å¸â€”â€˜Ã¯Â¸Â  Supprimer TOUS les Jobs K8s en ÃƒÂ©chec (Failed) - ${RED}Nettoyage${NC}"
    echo ""
    echo -e " ${YELLOW}--- Ã°Å¸Å¡â‚¬ SAS VIYA JOB EXECUTION (TÃƒÂ¢ches PlanifiÃƒÂ©es & Code SAS) ---${NC}"
    echo -e " ${BOLD}5)${NC} Ã°Å¸â€Å½ Voir les Pods d'orchestration (sas-job-execution, sas-scheduler...)"
    echo -e " ${BOLD}6)${NC} Ã°Å¸Å¡Â¨ Scanner GLOBAL des logs d'orchestration (Recherche Error, OOM...)"
    echo -e "${CYAN}--------------------------------------------------------------${NC}"
    echo -e " ${RED}r)${NC} Retour au menu"
    echo ""
    read -p "Ã°Å¸â€˜â€° Votre choix : " choice

    case "$choice" in
        1)
            echo -e "\n${YELLOW}Liste de tous les Jobs Kubernetes :${NC}"
            ${OC_CMD:-oc} get jobs -n "$DEFAULT_NAMESPACE" --sort-by=.metadata.creationTimestamp
            echo ""
            read -p "Appuyez sur EntrÃƒÂ©e pour revenir au menu..."
            ;;
        2)
            echo -e "\n${RED}Liste des Jobs Kubernetes en ÃƒÂ©chec :${NC}"
            ${OC_CMD:-oc} get jobs -n "$DEFAULT_NAMESPACE" | awk '$3 == 0 || $2 == "0/1" {print $0}'
            echo ""
            read -p "Appuyez sur EntrÃƒÂ©e pour revenir au menu..."
            ;;
        3)
            echo -e "\n${RED}Ã¢Å¡Â Ã¯Â¸Â  ATTENTION : Nettoyage des Jobs K8s terminÃƒÂ©s (Completed)${NC}"
            echo -e "Namespace cible : ${YELLOW}$DEFAULT_NAMESPACE${NC}"
            read -p "Ã°Å¸â€˜â€° Confirmer la suppression de TOUS les jobs COMPLETED dans ce namespace (o/N) ? " confirm
            if [[ "$confirm" =~ ^[oO]$ ]]; then
                ${OC_CMD:-oc} delete jobs -n "$DEFAULT_NAMESPACE" --field-selector status.successful=1
                echo -e "${GREEN}Ã¢Å“â€¦ Jobs terminÃƒÂ©s supprimÃƒÂ©s.${NC}"
                sleep 2
            else
                echo -e "${YELLOW}Annulation.${NC}"
                sleep 1
            fi
            ;;
        4)
            echo -e "\n${RED}Ã¢Å¡Â Ã¯Â¸Â  ATTENTION : Nettoyage des Jobs K8s en ÃƒÂ©chec (Failed)${NC}"
            echo -e "Namespace cible : ${YELLOW}$DEFAULT_NAMESPACE${NC}"
            read -p "Ã°Å¸â€˜â€° Confirmer la suppression de TOUS les jobs FAILED dans ce namespace (o/N) ? " confirm
            if [[ "$confirm" =~ ^[oO]$ ]]; then
                ${OC_CMD:-oc} delete jobs -n "$DEFAULT_NAMESPACE" --field-selector status.successful=0
                echo -e "${GREEN}Ã¢Å“â€¦ Jobs en ÃƒÂ©chec supprimÃƒÂ©s.${NC}"
                sleep 2
            else
                echo -e "${YELLOW}Annulation.${NC}"
                sleep 1
            fi
            ;;
        5)
            echo -e "\n${YELLOW}Liste des Pods Viya liÃƒÂ©s ÃƒÂ  la gestion et l'exÃƒÂ©cution de Jobs :${NC}"
            ${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" | head -n 1
            ${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" | grep -iE "^sas-job-execution|^sas-scheduler|^sas-workload-orchestrator|^sas-batch|^sas-launcher"
            echo ""
            read -p "Appuyez sur EntrÃƒÂ©e pour revenir au menu..."
            ;;
6)
            echo -e "\n${CYAN}=== [ SCANNER DE LOGS - VIYA JOB EXECUTION ] ===${NC}"
            echo -e "Cibles : ${PURPLE}sas-job-execution, sas-scheduler, sas-workload-orchestrator, sas-batch, sas-launcher${NC}"
            echo -e "Mots-clÃƒÂ©s recherchÃƒÂ©s : ${RED}error, panic, killed, oom, unexpected, fatal${NC}"
            echo -e "${CYAN}--------------------------------------------------------------${NC}\n"
            
            echo -e "${YELLOW}Ã°Å¸â€Â RÃƒÂ©cupÃƒÂ©ration et analyse en cours sur l'ensemble des pods...${NC}\n"
            
            PODS_LIST=$(${OC_CMD:-oc} get pods -n "$DEFAULT_NAMESPACE" --no-headers 2>/dev/null | grep -iE "^sas-job-execution|^sas-scheduler|^sas-workload-orchestrator|^sas-batch|^sas-launcher" | awk '{print $1}')

            if [ -z "$PODS_LIST" ]; then
                echo -e "${RED}Ã¢ÂÅ’ Aucun pod d'orchestration trouvÃƒÂ© dans le namespace $DEFAULT_NAMESPACE.${NC}"
            else
                ERRORS_FOUND=0
                
                for POD_NAME in $PODS_LIST; do
                    CONTAINER_NAME=$(${OC_CMD:-oc} get pod "$POD_NAME" -n "$DEFAULT_NAMESPACE" -o jsonpath='{.spec.containers[0].name}' 2>/dev/null)
                    C_OPT=""
                    [ -n "$CONTAINER_NAME" ] && C_OPT="-c $CONTAINER_NAME"

                    LOGS_OUT=$(${OC_CMD:-oc} logs "$POD_NAME" $C_OPT -n "$DEFAULT_NAMESPACE" --tail=100 2>&1)
                    
                    # Filtrage intelligent :
                    # 1. On exclut les lignes qui contiennent un "=" avant le mot-clÃƒÂ© (faux positifs Java/CLI)
                    # 2. On cherche ensuite les mots-clÃƒÂ©s qui sont prÃƒÂ©cÃƒÂ©dÃƒÂ©s d'un espace, du dÃƒÂ©but de ligne ou d'une ponctuation
                    FILTERED_LOGS=$(echo "$LOGS_OUT" | \
                        grep -ivE "=(error|panic|killed|oom|unexpected|unexcepted|fatal)" | \
                        grep -iE --color=always "(^|[[:space:]]|\[)(error|panic|killed|oom|unexpected|unexcepted|fatal)")

                    if [ -n "$FILTERED_LOGS" ]; then
                        echo -e "${RED}Ã¢Å¡Â Ã¯Â¸Â  Alertes trouvÃƒÂ©es dans : ${BOLD}$POD_NAME${NC}"
                        echo "$FILTERED_LOGS"
                        echo -e "${CYAN}--------------------------------------------------------------${NC}"
                        ERRORS_FOUND=1
                    fi
                done
                
                if [ $ERRORS_FOUND -eq 0 ]; then
                    echo -e "${GREEN}Ã¢Å“â€¦ Analyse terminÃƒÂ©e. Aucune erreur critique rÃƒÂ©elle trouvÃƒÂ©e.${NC}\n"
                fi
            fi
            
            read -p "Appuyez sur EntrÃƒÂ©e pour revenir au menu..."
            ;;
        r|R) break ;;
        *) echo -e "${RED}Choix invalide.${NC}" ; sleep 1 ;;
    esac
done
