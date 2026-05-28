#!/bin/bash
# ==============================================================================
# Script de compilation par lot des plugins Go pour SAS Viya
# ==============================================================================

echo "🚀 Démarrage de l'environnement de compilation Docker..."

docker run --rm -v "$PWD":/app -w /app golang:latest sh -c '
  echo "📦 Vérification et mise à jour des dépendances (go mod tidy)..."
  go mod tidy
  
  echo "---------------------------------------------------"
  
  # Parcours de tous les fichiers se terminant par .go
  for file in *.go; do
    # Sécurité au cas où il n"y a aucun fichier .go
    [ -e "$file" ] || continue
    
    # Extraction du nom du binaire (on retire l"extension .go)
    binaire="${file%.go}"
    
    echo "🔨 Compilation de : $file -> $binaire"
    
    # La commande de compilation sécurisée et optimisée EDR
    CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build \
      -buildmode=pie \
      -ldflags="-s -w" \
      -trimpath \
      -o "$binaire" "$file"
      
    # Vérification du code retour de la compilation
    if [ $? -eq 0 ]; then
      echo "  ✅ Succès"
    else
      echo "  ❌ Échec"
    fi
  done
  
  echo "---------------------------------------------------"
  echo "🎉 Toutes les compilations sont terminées !"
'
