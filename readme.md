# Ws Puertos

- [Introducción](#introduccion)
- [Requisitos](#requisitos)
- [Instalación](#instalacion)
- [Optimizaciones](#optimizaciones)


<a name="introduccion"></a>
## Introducción

Este es el webservice que utilian los puertos para realizar distintas tareas como la de el [reenvío de reportes CAESSAT][reenvios-caessat] a distintas plataformas.

<a name="requisitos"></a>
## Requisitos

- git
- composer
- npm
- [redis][redis]

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

<a name="optimizaciones"></a>
## Optimizaciones

Debido a que en ocasiones la API no llega a procesar todas las request cuando llegan al mismo tiempo, es neceario saber las optimizaciones que aún quedan por realizar en la aplicación o en el server.

#### Migrar a Lumen

Laravel realiza un montón de procesamiento pensado para servir una web. Debido a que la API sólo necesitaría enviar datos, Lumen sería lo ideal ya que quita mucha funcionalidad innecesaria para una API.

#### Migrar a php 7

Con la versión 7 de php, se mejoró considerablemente la velocidad de procesamiento (x2) y el consumo de recursos (/2).

#### Usar Nginx en vez de Apache

Nginx tiene un modo muy diferente a Apache para procesar las request, que hace que trate los threads de manera más óptima y más segura. Esto hace que se puedan procesar muchas más request por segundo debido a su procesamiento asíncrono y concurrente.

#### Separar proceso node y API en distintos servers

Dado que ambos son procesos que están continuamente procesando datos, analizar de separarlos si llegan a consumir muchos recursos.

 [reenvios-caessat]: /Reenvios-de-posiciones
 [guia-instalacion]: /Web/Creacion-proyecto-web#instalacion-proyectos
 [redis]: /Software-de-terceros/Redis#instalacion
