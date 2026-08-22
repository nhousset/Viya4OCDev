#!/bin/bash
# TITLE: OpenShift Connection Diagnostics

echo "=========================================="
echo "    OpenShift Connection Diagnostics      "
echo "=========================================="
echo ""

echo "[1/4] Checking OC CLI version..."
oc version --client
echo ""

echo "[2/4] Checking authentication status..."
who=$(oc whoami 2>&1)
if [ $? -eq 0 ]; then
    echo "SUCCESS: Logged in as: $who"
else
    echo "ERROR: Not logged in or token expired."
    echo "Details: $who"
    exit 1
fi
echo ""

echo "[3/4] Checking cluster connectivity..."
oc cluster-info 2>&1 | head -n 3
echo ""

echo "[4/4] Checking project/namespace access..."
current_project=$(oc project -q 2>&1)
if [ $? -eq 0 ]; then
    echo "Current active namespace: $current_project"
else
    echo "ERROR: Cannot determine project/namespace."
    echo "Details: $current_project"
fi

echo ""
echo "=========================================="
echo "       Diagnostics Completed              "
echo "=========================================="