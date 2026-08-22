#!/bin/bash
# TITLE: TLS Certificates Check (cert-manager)

echo -e "\n=== [STATUT DES CERTIFICATS TLS] ==="

echo "Ã°Å¸â€Â Certificats gÃƒÂ©rÃƒÂ©s par cert-manager :"
oc get certificates -n "$DEFAULT_NAMESPACE" 2>/dev/null || echo "Aucun certificat cert-manager trouvÃƒÂ©."

echo -e "\nÃ°Å¸â€â€˜ Secrets contenant des certificats TLS :"
oc get secrets -n "$DEFAULT_NAMESPACE" --field-selector type=kubernetes.io/tls -o custom-columns=NAME:.metadata.name,AGE:.metadata.creationTimestamp
