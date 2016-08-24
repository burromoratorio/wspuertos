# Ws Puertos

- [Introducción](#introduccion)
- [Requisitos](#requisitos)
- [Instalación](#instalacion)


<a name="introduccion"></a>
## Introducción

Este es el webservice que utilian los puertos para realizar distintas tareas como la de el [reenvío de reportes CAESSAT][reenvios-caessat] a distintas plataformas.

<a name="requisitos"></a>
## Requisitos

- git
- composer
- npm

<a name="instalacion"></a>
## Instalación

Se puede instalar facilmente siguiendo la guía de [instalación de proyecto web existente][guia-instalacion].

Crear alias `errors` para poder utilizar el chequeo de errores de la siguiente manera:

```bash
errors [-yh]
```
Para crear el alias, ejecutar el siguiente comando (sólo una vez) para que quede guardado en el **.bashrc** y que de estea manera esté disponible cada vez que se abra una cosola interactiva de bash:

```bash
echo 'alias errors="/var/www/wspuertos/util/errors.sh"' >> ~/.bashrc
```

 [reenvios-caessat]: /Reenvios-de-posiciones
 [guia-instalacion]: /Web/Creacion-proyecto-web#instalacion-proyectos
