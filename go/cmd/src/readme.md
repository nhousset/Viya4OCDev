```
docker run --rm -v "$PWD":/app -w /app golang:latest sh -c "go mod tidy && CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -buildmode=pie -ldflags='-s -w' -trimpath -o limites_quotas limites_quotas.go"
```
