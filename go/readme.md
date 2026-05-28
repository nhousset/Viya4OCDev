docker run --rm -v "$PWD":/app -w /app golang:latest sh -c "go install mvdan.cc/garble@latest && go mod tidy && CGO_ENABLED=0 GOOS=linux GOARCH=amd64 garble -tiny -literals build -o viya main.go"
