FROM webdevops/php-nginx-dev:7.3

RUN if $(uname -m) == 'arm64';
    then wget -O "/usr/local/bin/go-replace" "https://github.com/webdevops/goreplace/releases/download/1.1.2/gr-arm64-linux" \
    && chmod +x "/usr/local/bin/go-replace" \
    && "/usr/local/bin/go-replace" --version;
    fi