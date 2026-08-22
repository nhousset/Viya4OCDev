#!/bin/bash
# TITLE: Compute Server Status (Sessions & Jobs)

echo -e "\n=== [STATUT DU SOUS-SYSTÃƒË†ME COMPUTE] ==="

# 1. VÃƒÂ©rification du service Launcher (Le chef d'orchestre)
echo "Ã°Å¸Å¡â‚¬ Ãƒâ€°tat des pods Launcher (Le service qui instancie les sessions) :"
oc get pods -n "$DEFAULT_NAMESPACE" -l app=sas-launcher

# 2. VÃƒÂ©rification des sessions Compute actives
echo -e "\nÃ°Å¸â€™Â» Sessions Compute actives (Utilisateurs dans SAS Studio, etc.) :"
# Viya 4 utilise des labels spÃƒÂ©cifiques pour marquer les pods crÃƒÂ©ÃƒÂ©s par le launcher
COMPUTE_PODS=$(oc get pods -n "$DEFAULT_NAMESPACE" -l launcher.sas.com/job-type=compute-server --no-headers 2>/dev/null)

if [ -z "$COMPUTE_PODS" ]; then
    echo "Ã¢Å“â€¦ Aucune session compute active pour le moment."
else
    # On affiche les pods avec leurs statuts et l'ÃƒÂ¢ge
    oc get pods -n "$DEFAULT_NAMESPACE" -l launcher.sas.com/job-type=compute-server
    
    # 3. Consommation des ressources de ces sessions
    echo -e "\nÃ°Å¸â€œË† Consommation CPU/RAM des sessions actives :"
    oc adm top pods -n "$DEFAULT_NAMESPACE" -l launcher.sas.com/job-type=compute-server 2>/dev/null || echo "MÃƒÂ©triques indisponibles."
fi

# 4. VÃƒÂ©rification des Batchs (Jobs planifiÃƒÂ©s)
echo -e "\nÃ¢Å¡â„¢Ã¯Â¸Â  Jobs Batch en cours d'exÃƒÂ©cution :"
BATCH_PODS=$(oc get pods -n "$DEFAULT_NAMESPACE" -l launcher.sas.com/job-type=sas-programming-environment --no-headers 2>/dev/null)

if [ -z "$BATCH_PODS" ]; then
    echo "Aucun job batch actif."
else
    oc get pods -n "$DEFAULT_NAMESPACE" -l launcher.sas.com/job-type=sas-programming-environment
fi

# 5. Recherche des crashs rÃƒÂ©cents (trÃƒÂ¨s frÃƒÂ©quent sur le compute)
echo -e "\nÃ¢Å¡Â Ã¯Â¸Â  Pods Compute en ÃƒÂ©chec (Erreurs, OOMKilled, Evicted) :"
# On cherche les pods compute qui ont ÃƒÂ©chouÃƒÂ© (souvent ÃƒÂ  cause d'un manque de RAM)
FAILED_COMPUTE=$(oc get pods -n "$DEFAULT_NAMESPACE" -l launcher.sas.com/job-type=compute-server --field-selector=status.phase=Failed --no-headers 2>/dev/null)

if [ -z "$FAILED_COMPUTE" ]; then
    echo "Ã¢Å“â€¦ Aucun crash de session compute dÃƒÂ©tectÃƒÂ©."
else
    oc get pods -n "$DEFAULT_NAMESPACE" -l launcher.sas.com/job-type=compute-server --field-selector=status.phase=Failed
    echo "Ã°Å¸â€™Â¡ Astuce : Utilisez la commande d'extraction des logs pour analyser ces ÃƒÂ©checs."
fi
