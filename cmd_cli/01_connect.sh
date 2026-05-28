#!/bin/bash
# TITLE: Connexion au CLI SAS Viya

RED=${RED:-'\033[0;31m'}
GREEN=${GREEN:-'\033[0;32m'}
YELLOW=${YELLOW:-'\033[1;33m'}
CYAN=${CYAN:-'\033[0;36m'}
NC=${NC:-'\033[0m'}

echo -e "\n${CYAN}=== [CONNEXION AU CLI SAS VIYA] ===${NC}"

if ! command -v sas-viya >/dev/null 2>&1; then
    echo -e "${RED}❌ Erreur : Le binaire 'sas-viya' n'a pas été trouvé dans le PATH.${NC}"
    exit 1
fi

# Utilisation directe des variables définies globalement dans viya.sh
PROFILE_NAME=${PROFILE_NAME:-default}

if [ -z "$SAS_VIYA_URL" ]; then
    echo -e "${RED}❌ L'URL SAS Viya n'est pas définie dans votre configuration actuelle.${NC}"
    echo -e "${YELLOW}💡 Veuillez relancer viya.sh pour la configurer.${NC}"
    exit 1
fi

echo -e "\n${CYAN}🔍 Configuration automatique du profil '${PROFILE_NAME}' avec l'URL : ${SAS_VIYA_URL}...${NC}"

sas-viya --profile "$PROFILE_NAME" profile set-endpoint "$SAS_VIYA_URL" >/dev/null
sas-viya --profile "$PROFILE_NAME" profile toggle-color on >/dev/null
sas-viya --profile "$PROFILE_NAME" profile set-output text >/dev/null

echo -e "\n${YELLOW}🔑 Authentification requise (Profil : $PROFILE_NAME).${NC}"
echo -e "Lancement de 'sas-viya auth login'..."
echo -e "---------------------------------------------------"

sas-viya --profile "$PROFILE_NAME" auth login

if [ $? -eq 0 ]; then
    echo -e "---------------------------------------------------"
    echo -e "${GREEN}🎉 Connexion réussie ! Le token est lié au profil '$PROFILE_NAME'.${NC}"
else
    echo -e "---------------------------------------------------"
    echo -e "${RED}❌ Échec de la connexion ou annulation.${NC}"
    exit 1
fi
echo -e "\n"
