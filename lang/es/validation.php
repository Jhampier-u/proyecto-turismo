<?php

/**
 * Traducción de los mensajes de validación de Laravel.
 *
 * POR QUÉ EXISTE ESTE FICHERO: sin él, cualquier error de validación sale en
 * inglés y con el nombre de columna crudo -«The ep cambios tiempo field is
 * required»-, porque el framework no trae `lang/es` de fábrica (Laravel 12 ya
 * no publica `lang/` en el esqueleto nuevo; sin este fichero, cae directo al
 * `vendor/laravel/framework/.../lang/en/validation.php` interno). Antes casi
 * no se veía -los formularios mandaban todos los campos y un error era
 * raro-, pero con el guardado parcial y con matrices de más de cien campos
 * (Concentración tiene 113), «confirmar cuando falta algo» es un camino
 * normal, así que este mensaje pasa a ser frecuente.
 *
 * POR QUÉ NO ESTÁN LAS ~90 REGLAS QUE TRAE LARAVEL: se tradujeron solo las que
 * el proyecto usa de verdad -comprobado recorriendo cada controlador que
 * llama a validate()-, no las que Laravel ofrece pero nadie invoca aquí (no
 * hay `alpha`, `json`, `url`, `date`, `distinct`, `uuid`... en ningún
 * ->validate() del código). Traducir de más no aporta nada; traducir de menos
 * sí duele, porque una regla que se empiece a usar y no esté aquí no rompe
 * ningún test -Laravel no avisa-, simplemente ese mensaje cae al inglés del
 * fallback_locale en silencio. Si el día de mañana un controlador nuevo usa
 * una regla que no está en esta lista, añadirla aquí es la primera línea del
 * arreglo.
 *
 * POR QUÉ 'max' Y 'min' TRAEN LAS CUATRO VARIANTES (array/file/numeric/string)
 * aunque no todas se usen hoy: a diferencia de una regla suelta como 'email'
 * o 'array', 'max' y 'min' son con diferencia las más usadas del proyecto -en
 * los criterios de las nueve matrices y en medio centenar de campos de
 * formularios de admin- y el subtipo que dispara Laravel depende del resto de
 * reglas del campo (integer/numeric → numeric, array → array, un archivo →
 * file, cualquier otra cosa → string). Dejar solo las variantes de hoy sería
 * apostar a que nadie añade nunca un `array|max:N` o un campo de archivo
 * nuevo; las cuatro juntas cuestan lo mismo que traducir una y cierran esa
 * puerta entera.
 */

return [

    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'email' => 'El campo :attribute debe ser una dirección de correo electrónico válida.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'image' => 'El campo :attribute debe ser una imagen.',
    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'array' => 'El campo :attribute debe ser una lista.',
    // "El valor seleccionado para :attribute", no "El :attribute
    // seleccionado": :attribute puede ser femenino ("provincia", "categoría")
    // y Laravel no tiene forma de concordar género en un mensaje con
    // marcador -a diferencia de "El campo :attribute", donde "campo" es
    // siempre masculino y el problema no se plantea-. Esta redacción es
    // correcta pase lo que pase con el género de la palabra que sustituya a
    // :attribute.
    'in' => 'El valor seleccionado para :attribute no es válido.',
    'lowercase' => 'El campo :attribute debe estar en minúsculas.',
    'exists' => 'El valor seleccionado para :attribute no es válido.',
    'unique' => 'El campo :attribute ya ha sido registrado.',
    'current_password' => 'La contraseña es incorrecta.',

    'max' => [
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
        'file' => 'El campo :attribute no debe pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
    ],

    'min' => [
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
        'file' => 'El campo :attribute debe pesar al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser como mínimo :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Nombres de atributo
    |--------------------------------------------------------------------------
    |
    | Esto es solo para los campos genéricos de los formularios de admin
    | (usuarios, zonas, lugares, inventario) y de autenticación: nombres de
    | columna cortos que no tienen ningún «instrumento» detrás del que
    | derivarlos, así que traducirlos aquí una vez es la única fuente posible.
    |
    | Los campos de criterio de las nueve matrices NO están aquí a propósito:
    | sus etiquetas ya existen en App\Matrices\* o en el controlador de cada
    | matriz -Paisaje::todos(), Irritacion::ETIQUETAS, EvaluacionPercepcionController::$categorias,
    | etc.-, y se pasan al validador como atributos personalizados por
    | petición (ver MatrizPonderadaController::etiquetas()). Copiarlas también
    | aquí crearía exactamente la segunda fuente de verdad que este proyecto
    | lleva meses quitando: en cuanto alguien cambiara una etiqueta en el
    | instrumento, esta lista se quedaría con la vieja y ningún test lo vería.
    |
    */

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de la contraseña',
        'current_password' => 'contraseña actual',
        'token' => 'token',
        'role_id' => 'rol',
        'telefono' => 'teléfono',
        'nombre' => 'nombre',
        'descripcion' => 'descripción',
        'provincia_id' => 'provincia',
        'lugar_id' => 'lugar',
        'jefe_user_id' => 'jefe de zona',
        'equipo' => 'equipo',
        'imagen' => 'imagen',
        'nombre_recurso' => 'nombre del recurso',
        'categoria_id' => 'categoría',
        'propietario_id' => 'propietario',
        'ubicacion_detallada' => 'ubicación detallada',
        'accesibilidad' => 'accesibilidad',
        'equipamiento_servicios' => 'equipamiento y servicios',
        'estado_conservacion' => 'estado de conservación',
        'fotos' => 'fotos',
        'nuevas_fotos' => 'fotos nuevas',
    ],

];
