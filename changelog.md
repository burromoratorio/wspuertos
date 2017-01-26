## Changelog puertos

### v0.1.0 (18/10/2016)

3f259dc Fix chequeo de aviso notificable
b358f17 Realiza el publish del redis
8103021 Corrige nombre de campo
b36a1c4 Cambia dominio
dd60b5a Agrega constantes faltantes
8731118 Implementa método patch de avisos y se realizan mejoras
de62a3b Agrega pruebas, correcciones, factories, y chequeos
ea7aafe Versiona modelo Waypoint
c9d61ab Corrige tests
6bfaaea Se descomenta código comentado para pruebas
33eeb9e Finaliza API para eventos
4b3b649 Corrige código y mejora pruebas
0ad25d8 Mejora interacción con puertos
4992a2e Merge branch 'master' into envio-mail
071f51d Optimiza carga
7c67fb0 Mejora envoy
26758f1 Chequea si debe enviar mail

### v0.1.1 (19/10/2016)

8d512c0 Fix hora GMT

### v0.2.0 (24/01/2017)

a52370c Ordena tests (des)enganche
faa860e Testing
55c0d49 Utiliza eventos para el envío de mail para facilitar pruebas
c828655 Mejora menejo de nombres de archivo
e82ad3f Agrega evento, aviso de enganche, y docblocks
992fabf Refactoriza, agrega docblocks para mejorar lectura, implementa envío de desenganche
e4aa651 Contempla aviso de desenganche
8800349 se quita log
f677262 soporte para envío de posiciones por udp a dadores
0be2b23 Revert "integarcion con wsviajes"
82ed805 Revert "se agrega configuracion de database siac"
11d059a Agrego funcion que lista los avisos y creo la ruta para manejar la peticion
5c417d4 Creo algunas rutas y agrego controladores
6ef9f19 se agrega configuracion de database siac
0cc7d72 integarcion con wsviajes
0f5d631 Obtiene los tags en cada despliegue
bc5363e Actualiza changelog para agregar path: tag v0.1.1
20a3c42 Merge branch 'avisos-new'
fd876b4 Agrega test múltiples aviso_cliente_id
e8c9ae5 Fix bugs
d1aecae Soporta varias combinaciones cliente_id-aviso_tipo_id
999ce6c Merged branch master into master
64af31d Agrego controlador para destinatarios
479918a Agrego rutas para el modelo destinatarios
2a373da Modifico modelo

### v0.2.1 (26/01/2017)

513ddbe Reduce tamaño de error devuelto
001ec2f Quita acento del subject del mail
