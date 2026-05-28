#!/bin/bash
# TITLE: Vérifier la connexion SAS Viya CLI

# Couleurs pour faire joli (héritées du script principal)
RED=${RED:-'\033[0;31m'}
GREEN=${GREEN:-'\033[0;32m'}
YELLOW=${YELLOW:-'\033[1;33m'}
CYAN=${CYAN:-'\033[0;36m'}
NC=${NC:-'\033[0m'}

echo -e "\n${CYAN}=== [VÉRIFICATION DE LA CONNEXION SAS VIYA] ===${NC}"

# 1. Présence de l'outil
if ! command -v sas-viya >/dev/null 2>&1; then
    echo -e "${RED}❌ Erreur : Le binaire 'sas-viya' n'a pas été trouvé dans le PATH.${NC}"
    exit 1
fi

# 2. Demande du profil
read -p "👉 Profil SAS Viya à tester [default] : " SAS_PROFILE
SAS_PROFILE=${SAS_PROFILE:-default}

echo -e "\n${CYAN}🔍 Vérification du profil '${SAS_PROFILE}'...${NC}"

# 3. Vérification de l'URL (Endpoint)
ENDPOINT=$(sas-viya --profile "$SAS_PROFILE" profile show 2>/dev/null | grep -i "Service Endpoint" | awk '{print $NF}')
if [ -z "$ENDPOINT" ] || [ "$ENDPOINT" == "None" ]; then
    echo -e "🌐 Endpoint : ${RED}Non configuré${NC}"
    echo -e "${RED}❌ Aucune URL n'est configurée pour ce profil.${NC}"
    echo -e "${YELLOW}💡 Utilisez le script de connexion (01_connect.sh) pour l'initialiser.${NC}"
    exit 1
fi
echo -e "🌐 Endpoint : ${GREEN}$ENDPOINT${NC}"

# 4. Vérification locale du Token
echo -e "🔑 Token    : \c"
if [ -f "$HOME/.sas/credentials.json" ] && grep -q "\"access_token\"" "$HOME/.sas/credentials.json"; then
    echo -e "${GREEN}Présent en local${NC}"
else
    echo -e "${RED}Absent${NC}"
    echo -e "\n${RED}❌ Vous n'êtes pas connecté (ou votre session a été supprimée).${NC}"
    echo -e "${YELLOW}💡 Utilisez le script de connexion (01_connect.sh) pour vous authentifier.${NC}"
    exit 1
fi

# 5. Test réel avec l'API
echo -e "\n${CYAN}📡 Test de communication avec l'API SAS Viya...${NC}"
echo -e "   (Test effectué via une demande de la liste des contextes de calcul...)"

# On lance une commande API silencieuse pour voir si elle passe sans erreur "401 Unauthorized"
TEST_CMD=$(sas-viya --profile "$SAS_PROFILE" compute contexts list 2>&1)
EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    echo -e "\n${GREEN}✅ Succès ! L'API répond correctement.${NC}"
    echo -e "${GREEN}✅ Vous êtes parfaitement connecté.${NC}"
else
    # Si ça échoue à cause du token expiré
    if echo "$TEST_CMD" | grep -qi "unauthorized\|expired\|forbidden"; then
        echo -e "\n${RED}❌ Échec : Le token a expiré ou n'est plus valide.${NC}"
        echo -e "${YELLOW}💡 Relancez la connexion avec 01_connect.sh pour récupérer un nouveau token.${NC}"
    else
        # Autre erreur (ex: plugin manquant, serveur down)
        echo -e "\n${YELLOW}⚠️ La requête de test a échoué, mais vous avez bien un token.${NC}"
        echo -e "Détails de l'erreur :"
        echo -e "${NC}$TEST_CMD${NC}"
    fi
fi

echo -e "\n"
