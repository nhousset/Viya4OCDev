#!/bin/bash

# Ensure script is run as root
if [ "$EUID" -ne 0 ]; then
  echo "Error: Please run as root (or use sudo)"
  exit 1
fi

echo "Removing existing container (if any)..."
docker rm --force viya4oc 2>/dev/null || true

echo "Building Docker image..."
docker build -t viya4oc -f webapp/Dockerfile .

echo "Starting Docker container..."
docker run -d \
  --name viya4oc \
  -p 7891:80 \
  -v /tmp/viya4OCdev:/var/www/conf \
  viya4oc

echo "OpsBuddy is now running on http://localhost:7891"