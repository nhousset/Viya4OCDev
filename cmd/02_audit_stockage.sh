#!/bin/bash
# TITLE: Storage Audit (PV, PVC, Events)
echo -e "\n=== [AUDIT DU STOCKAGE] ==="
# On dÃƒÂ©finit le namespace par dÃƒÂ©faut si ce n'est pas dÃƒÂ©jÃƒÂ  fait
export DEFAULT_NAMESPACE="${DEFAULT_NAMESPACE:-default}"
# On exÃƒÂ©cute le programme Go qui prend le relais pour le reste
./audit_stockage
