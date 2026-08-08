{{--
    El aviso de «guardado» que devuelve el controlador.

    Existe porque los formularios de las matrices no pintaban session('success')
    en ninguna parte: el mensaje del guardado parcial —«Llevas 3 de 34
    criterios»— se generaba, viajaba en la sesión y no lo veía nadie. El test
    que lo cubría solo miraba la sesión, así que tampoco saltaba.
--}}

@if(session('success'))
    <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded shadow-sm">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded shadow-sm">
        {{ session('error') }}
    </div>
@endif
