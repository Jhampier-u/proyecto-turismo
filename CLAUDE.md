# Reglas de trabajo en este repositorio

Este fichero existe para que las reglas de operación **viajen con el código**.
Antes vivían solo en la memoria local de una máquina, así que al retomar el
trabajo en otro equipo se perdían y había que redescubrirlas.

## 1. Nunca se tocan los contenedores de Docker

**No se borra, para ni modifica ningún contenedor existente.** Nada de
`docker rm`, `docker stop`, `docker container prune`, `docker volume prune` ni
`docker rmi`. Lo único admisible es `docker run --rm`, que se lleva lo suyo al
terminar y no toca lo ajeno.

**Por qué:** en esta máquina hay contenedores de otros proyectos y un borrado
en falso cuesta trabajo de terceros.

## 2. PHP es nativo: no hace falta Docker para nada del proyecto

PHP 8.2.33 y Composer están instalados de forma nativa.

```bash
php artisan test     # la suite entera, ~40 s
npm run build        # los assets, un par de segundos
```

Docker **solo** si hace falta PostgreSQL para verificar una migración, y
siempre con `docker run --rm` (ver la regla 1).

**Por qué importa decirlo:** una nota antigua afirmaba lo contrario y llegó a
provocar que un revisor marcara como desviación de proceso el uso —correcto—
de `php artisan test`.

## 3. Al terminar algo, se sube a `main` con el contexto al día

**Fusionar se pregunta; subir no.** Una vez fusionado, se sube, y se sube
**junto con el contexto**, no solo el código:

- `docs/ESTADO-PROYECTO.md` — el documento de traspaso, que acumula. Lleva el
  recuento de tests, una entrada por rama y la lista de lo que queda.
- `.superpowers/sdd/progress.md` — la bitácora de la rama en curso. **Se
  sobrescribe al empezar cada rama**, así que lo que merezca sobrevivir hay que
  volcarlo antes al traspaso.
- Los `*-report.md` de cada tarea.

Y antes de dar nada por cerrado: **correr la suite sobre el resultado
fusionado**, no solo sobre la rama.

**Por qué:** el trabajo se retoma desde dos máquinas distintas. Una rama
fusionada pero sin subir, o un traspaso una rama por detrás, hacen que la otra
empiece con información falsa. Ha pasado: el traspaso llegó a dar 524 tests
cuando `main` tenía 553, y a listar como pendiente algo que ya estaba hecho.

## 4. `package-lock.json` no entra en ningún commit

Se regenera al instalar en Windows, y el `npm ci` de la imagen de producción
usa el generado en Linux. Si aparece modificado:

```bash
git checkout -- package-lock.json
```

## Cómo se trabaja aquí

Las ramas siguen un mismo camino, y las cuatro últimas salieron así: se
brainstormea hasta un diseño aprobado, se escribe un plan con el código
completo de cada tarea, se ejecuta tarea a tarea con revisión entre ellas, y
al final se revisa la rama entera antes de fusionar.

**Las revisiones no son ceremonia.** En las cuatro últimas ramas encontraron
defectos que ningún test veía: una tabla de resultados que enseñaba doce de
dieciocho criterios, una franja que pintaba en verde un estado bloqueado, un
contenedor anidado que duplicaba el padding, y una pantalla de login que se
quedó fuera del rediseño mientras se convertía un componente que no usaba
nadie. Tres de esos defectos estaban en el plan, escritos por quien lo
escribió.

## Dónde está todo lo demás

`docs/ESTADO-PROYECTO.md` es el documento de traspaso: qué es el proyecto, cómo
se levanta en una máquina nueva, qué hizo cada rama y qué queda por hacer.
**Empieza por ahí.**
