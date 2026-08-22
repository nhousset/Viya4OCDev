#!/bin/bash
# TITLE: Fast Log Extraction (Failed/CrashLoop Pods)

echo -e "\n=== [LOGS DES PODS EN ERREUR] ==="

# On cherche les pods qui ne sont pas en ÃƒÂ©tat Running ou Completed
BAD_PODS=$(oc get pods -n "$DEFAULT_NAMESPACE" --no-headers | awk '$3 != "Running" && $3 != "Completed" {print $1}')

if [ -z "$BAD_PODS" ]; then
    echo "Ã¢Å“â€¦ Tous les pods semblent stables (Running ou Completed)."
else
    for pod in $BAD_PODS; do
        echo -e "\n---------------------------------------------------"
        echo "Ã°Å¸â€œâ€ž Logs rÃƒÂ©cents pour le pod : $pod"
        echo "---------------------------------------------------"
        # On tente de rÃƒÂ©cupÃƒÂ©rer les logs du conteneur principal. 
        # --all-containers=true peut ÃƒÂªtre ajoutÃƒÂ© si nÃƒÂ©cessaire, mais pollue souvent la sortie.
        oc logs "$pod" -n "$DEFAULT_NAMESPACE" --tail=20 2>/dev/null || echo "Impossible de lire les logs (pod en cours de crÃƒÂ©ation ou dÃƒÂ©truit)."
    done
fi
