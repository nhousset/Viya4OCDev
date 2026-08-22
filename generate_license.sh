#!/bin/bash
# TITLE: OpsBuddy License Generator
# Description: Generates a 1-year valid JSON license for OpsBuddy.

SECRET="OpsBuddy_Secret_2026_SecureKey!"

if [ -z "$1" ] || [ -z "$2" ]; then
    echo "========================================="
    echo "  OpsBuddy License Generator"
    echo "========================================="
    echo "Usage: $0 <Client_Name> <Client_ID>"
    echo "Example: $0 \"Acme Corp\" \"CUST-8472\""
    exit 1
fi

CLIENT_NAME="$1"
CLIENT_ID="$2"
EXP_DATE=$(date -d "+1 year" +%Y-%m-%d 2>/dev/null)

# Fallback for macOS date command if Linux date fails
if [ -z "$EXP_DATE" ]; then
    EXP_DATE=$(date -v+1y +%Y-%m-%d 2>/dev/null)
fi

PAYLOAD="${CLIENT_NAME}|${CLIENT_ID}|${EXP_DATE}"

# Generate SHA256 HMAC signature using openssl
SIG=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $NF}')

OUT_FILE="${CLIENT_ID}_license.json"

cat <<EOF > "$OUT_FILE"
{
    "client_name": "$CLIENT_NAME",
    "client_id": "$CLIENT_ID",
    "expiration_date": "$EXP_DATE",
    "signature": "$SIG"
}
EOF

echo "========================================="
echo " License successfully generated!"
echo " Client  : $CLIENT_NAME"
echo " ID      : $CLIENT_ID"
echo " Expires : $EXP_DATE"
echo " File    : $OUT_FILE"
echo "========================================="