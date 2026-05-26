```
docker run --rm -v "$($PWD.Path):/app" -w /app golang:latest bash -c "if [ ! -f go.mod ]; then go mod init audit_stockage; fi && go mod tidy && GOOS=linux GOARCH=amd64 go build -o audit_stockage audit_stockage.go"
```
