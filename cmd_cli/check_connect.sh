#!/bin/bash
# TITLE: Vérifier la connexion SAS Viya CLI

RED=${RED:-'\033[0;31m'}
GREEN=${GREEN:-'\033[0;32m'}
YELLOW=${YELLOW:-'\033[1;33m'}
CYAN=${CYAN:-'\033[0;36m'}
NC=${NC:-'\033[0m'}

echo -e "\n${CYAN}=== [VÉRIFICATION DE LA CONNEXION SAS VIYA] ===${NC}"

if ! command -v sas-viya >/dev/null 2>&1; then
    echo -e "${RED}❌ Erreur : Le binaire 'sas-viya' n'a pas été trouvé dans le PATH.${NC}"
    exit 1
fi

# On se base strictement sur le profil injecté par viya.sh
PROFILE_NAME=${PROFILE_NAME:-default}

echo -e "\n${CYAN}🔍 Vérification de la session pour le profil '${PROFILE_NAME}'...${NC}"

# 1. Vérification de l'URL existante
ENDPOINT=$(sas-viya --profile "$PROFILE_NAME" profile show 2>/dev/null | grep -i "Service Endpoint" | awk '{print $NF}')
if [ -z "$ENDPOINT" ] || [ "$ENDPOINT" == "None" ]; then
    echo -e "🌐 Endpoint : ${RED}Non configuré${NC}"
    echo -e "${RED}❌ Ce profil ne pointe vers aucun serveur.${NC}"
    echo -e "${YELLOW}💡 Utilisez le script de connexion (01_connect.sh) pour tout configurer.${NC}"
    exit 1
fi
echo -e "🌐 Endpoint : ${GREEN}$ENDPOINT${NC}"

# 2. Vérification locale du Token (Lié à ce profil précis)
echo -e "🔑 Token    : \c"
if [ -f "$HOME/.sas/credentials.json" ] && grep -q "\"$PROFILE_NAME\"" "$HOME/.sas/credentials.json"; then
    echo -e "${GREEN}Présent en local${NC}"
else
    echo -e "${RED}Absent${NC}"
    echo -e "\n${RED}❌ Vous n'êtes pas connecté (ou votre session a expirée).${NC}"
    echo -e "${YELLOW}💡 Utilisez le script de connexion (01_connect.sh) pour vous authentifier.${NC}"
    exit 1
fi

# 3. Test réel avec l'API
echo -e "\n${CYAN}📡 Test de communication avec l'API SAS Viya...${NC}"
TEST_CMD=$(sas-viya --profile "$PROFILE_NAME" compute contexts list 2>&1)
EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    echo -e "\n${GREEN}✅ Succès ! L'API répond correctement.${NC}"
    echo -e "${GREEN}✅ Vous êtes parfaitement connecté sur le profil '${PROFILE_NAME}'.${NC}"
else
    if echo "$TEST_CMD" | grep -qi "unauthorized\|expired\|forbidden"; then
        echo -e "\n${RED}❌ Échec : Le token a expiré ou n'est plus valide.${NC}"
        echo -e "${YELLOW}💡 Relancez la connexion avec 01_connect.sh pour récupérer un nouveau token.${NC}"
    else
        echo -e "\n${YELLOW}⚠️ La requête de test a échoué, mais vous avez bien un token.${NC}"
        echo -e "${NC}$TEST_CMD${NC}"
    fi
fi
echo -e "\n"
