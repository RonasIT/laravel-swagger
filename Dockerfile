FROM webdevops/php-nginx-dev:8.1

RUN wget -O "/usr/local/bin/go-replace" "https://github.com/webdevops/go-replace/releases/download/22.9.0/go-replace.linux.arm64" \
    && chmod +x "/usr/local/bin/go-replace" \
    && "/usr/local/bin/go-replace" --version