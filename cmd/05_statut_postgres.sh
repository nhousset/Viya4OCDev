#!/bin/bash
# TITLE: PostgreSQL Status (CrunchyData)

echo -e "\n=== [STATUT DE L'INFRASTRUCTURE POSTGRESQL] ==="

echo "Ã°Å¸â€”â€žÃ¯Â¸Â  Clusters PostgreSQL (PostgresCluster) :"
oc get postgrescluster -n "$DEFAULT_NAMESPACE" 2>/dev/null || echo "Aucune ressource PostgresCluster trouvÃƒÂ©e."

echo -e "\nÃ°Å¸ÂÂ³ Pods de la base de donnÃƒÂ©es (Master & Replicas) :"
oc get pods -n "$DEFAULT_NAMESPACE" -l postgres-operator.crunchydata.com/data=postgres
