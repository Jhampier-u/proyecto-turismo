<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administrador inicial
    |--------------------------------------------------------------------------
    |
    | Credenciales con las que se crea la primera cuenta de administrador
    | cuando la base de datos está vacía. Se leen del entorno para que nunca
    | queden escritas en el repositorio.
    |
    | En Render se definen en Dashboard → Environment. Si no están definidas,
    | el seeder no crea ningún administrador y lo avisa por consola.
    |
    */

    'admin_email'    => env('ADMIN_EMAIL'),
    'admin_password' => env('ADMIN_PASSWORD'),
    'admin_nombre'   => env('ADMIN_NOMBRE', 'Administrador'),

];
