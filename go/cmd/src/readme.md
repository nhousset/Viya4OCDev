```
docker run --rm \
  -v "$PWD":/app \
  -w /app \
  golang:latest \
  sh -c "go mod init viya && go mod tidy && CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -o audit_stockage audit_stockage.go"

docker run --rm -v "$PWD":/app -w /app golang:latest sh -c "go install mvdan.cc/garble@latest && go mod tidy && CGO_ENABLED=0 GOOS=linux GOARCH=amd64 garble -tiny -literals build -o audit_limites audit_limites.go"
```
