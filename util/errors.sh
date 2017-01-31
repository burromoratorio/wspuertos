#!/bin/bash
# script de impresion de errores por consola

print_help() {
    cat << EOF
Modo de uso: errors [OPCION]...
    Si no se especifica ninguna opcion, imprime los errores del dia
    OPCIONES:
        -y  imprime los errores del dia anterior
        -h  esta ayuda
EOF
}

while getopts "yh" option; do
    case $option in
        y) DAY=$(date +%Y-%m-%d -d "yesterday");;
        h) print_help && exit;;
    esac
done

grep --text --no-filename -E "lumen.ERROR" /var/www/wspuertos/storage/logs/lumen.log 2> /dev/null
