#!/bin/bash
# TITLE: Connexion au CLI SAS Viya

# Couleurs pour faire joli (hété du script principal si appelé par lui)
RED=${RED:-'\033[0;31m'}
GREEN=${GREEN:-'\033[0;32m'}
YELLOW=${YELLOW:-'\033[1;33m'}
CYAN=${CYAN:-'\033[0;36m'}
NC=${NC:-'\033[0m'}

echo -e "\n${CYAN}=== [CONNEXION AU CLI SAS VIYA] ===${NC}"

# 1. Véfication de la prénce du CLI
if ! command -v sas-viya >/dev/null 2>&1; then
    echo -e "${RED} Erreur : Le binaire 'sas-viya' n'a pas é trouvéans le PATH.${NC}"
    echo -e "${YELLOW} Avez-vous bien configurée chemin lors du lancement de viya.sh ?${NC}"
    exit 1
fi

# 2. Choix du nom du profil
read -p " Nom du profil SAS Viya àonfigurer ou utiliser [default] : " SAS_PROFILE
SAS_PROFILE=${SAS_PROFILE:-default}

echo -e "\n${CYAN} Véfication du profil '${SAS_PROFILE}'...${NC}"

# On véfie si un endpoint est dé configuréans ce profil
CURRENT_ENDPOINT=$(sas-viya --profile "$SAS_PROFILE" profile show 2>/dev/null | grep -i "Service Endpoint" | awk '{print $NF}')

# 3. Configuration du endpoint si manquant
if [ -z "$CURRENT_ENDPOINT" ] || [ "$CURRENT_ENDPOINT" == "None" ]; then
    
    # Demande obligatoire de l'URL SAS Viya
    ENDPOINT=""
    while [ -z "$ENDPOINT" ]; do
        read -p " URL de l'API SAS Viya (ex: https://viya.monsite.com) : " ENDPOINT
        if [ -z "$ENDPOINT" ]; then
            echo -e "${RED} L'URL de SAS Viya est obligatoire pour initialiser le profil.${NC}"
        fi
    done
    
    echo -e " Application de la configuration..."
    sas-viya --profile "$SAS_PROFILE" profile set-endpoint "$ENDPOINT" >/dev/null
    sas-viya --profile "$SAS_PROFILE" profile toggle-color on >/dev/null
    sas-viya --profile "$SAS_PROFILE" profile set-output text >/dev/null
    
    echo -e "${GREEN} Endpoint configuré $ENDPOINT${NC}"
else
    echo -e "${GREEN} Ce profil pointe dé sur : $CURRENT_ENDPOINT${NC}"
fi

# 4. Authentification
echo -e "\n${YELLOW} Authentification requise pour le profil '$SAS_PROFILE'.${NC}"
echo -e "Lancement de 'sas-viya auth login'..."
echo -e "---------------------------------------------------"

# Lancement de la commande interactive de login (demande user/mdp)
sas-viya --profile "$SAS_PROFILE" auth login

if [ $? -eq 0 ]; then
    echo -e "---------------------------------------------------"
    echo -e "${GREEN} Connexion résie ! Le token est stockén toute séritéar le CLI.${NC}"
    echo -e "\n${YELLOW} Astuce :${NC} Pour exéter des commandes avec ce profil, utilisez l'option --profile :"
    echo -e "   ${CYAN}sas-viya --profile $SAS_PROFILE compute servers list${NC}"
else
    echo -e "---------------------------------------------------"
    echo -e "${RED} Éhec de la connexion ou annulation.${NC}"
    exit 1
fi

echo -e "\n"#
