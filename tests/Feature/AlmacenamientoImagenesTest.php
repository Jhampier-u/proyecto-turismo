<?php

namespace Tests\Feature;

use App\Models\Inventario;
use App\Models\InventarioImagen;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Las imágenes se guardan y se leen siempre a través del disco por defecto
 * (config filesystems.default), nunca contra 'public' fijo ni con
 * asset('storage/...'). Eso permite cambiar a almacenamiento externo
 * definiendo FILESYSTEM_DISK=s3, sin tocar código.
 */
class AlmacenamientoImagenesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $jefe;
    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        // Disco simulado: verifica que se usa el por defecto, no uno fijo.
        Storage::fake(config('filesystems.default'));

        $this->admin = $this->usuarioCon('admin');
        $this->jefe  = $this->usuarioCon('jefe_zona');

        $this->zona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Zona con fotos',
        ]);
    }

    /**
     * create() en vez de image(): no requiere la extensión GD, así que los
     * tests corren en cualquier entorno. Lo que se verifica aquí es dónde
     * termina el archivo, no su contenido.
     */
    private function imagenFalsa(string $nombre): UploadedFile
    {
        return UploadedFile::fake()->create($nombre, 40, 'image/jpeg');
    }

    private function usuarioCon(string $rol): User
    {
        return User::factory()->create([
            'role_id' => Role::where('nombre', $rol)->value('id'),
        ]);
    }

    private function datosInventario(array $extra = []): array
    {
        return array_merge([
            'nombre_recurso'      => 'Cascada de prueba',
            'categoria_id'        => DB::table('categorias_recurso')->whereNotNull('parent_id')->value('id'),
            'propietario_id'      => DB::table('tipos_propietario')->value('id'),
            'descripcion'         => 'Una descripción cualquiera.',
            'estado_conservacion' => 'Bueno',
        ], $extra);
    }

    public function test_las_fotos_se_guardan_en_el_disco_por_defecto(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/inventarios",
            $this->datosInventario([
                'fotos' => [
                    $this->imagenFalsa('cascada1.jpg'),
                    $this->imagenFalsa('cascada2.jpg'),
                ],
            ])
        )->assertSessionHasNoErrors();

        $rutas = InventarioImagen::pluck('ruta_archivo');

        $this->assertCount(2, $rutas);

        foreach ($rutas as $ruta) {
            Storage::assertExists($ruta);
            $this->assertStringStartsWith('inventarios/', $ruta);
        }
    }

    public function test_borrar_un_inventario_borra_sus_archivos(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/inventarios",
            $this->datosInventario(['fotos' => [$this->imagenFalsa('foto.jpg')]])
        );

        $ruta = InventarioImagen::value('ruta_archivo');
        Storage::assertExists($ruta);

        $inventario = Inventario::firstOrFail();

        $this->actingAs($this->jefe)
            ->delete("/operativo/zona/{$this->zona->id}/inventarios/{$inventario->id}");

        Storage::assertMissing($ruta);
    }

    public function test_borrar_una_zona_borra_las_fotos_de_sus_inventarios(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/inventarios",
            $this->datosInventario(['fotos' => [$this->imagenFalsa('foto.jpg')]])
        );

        $ruta = InventarioImagen::value('ruta_archivo');
        Storage::assertExists($ruta);

        // La cascada de la BD borra las filas; los archivos hay que borrarlos.
        $this->actingAs($this->admin)->delete("/admin/zonas/{$this->zona->id}");

        Storage::assertMissing($ruta);
    }

    public function test_no_se_admiten_mas_de_quince_fotos_por_peticion(): void
    {
        $fotos = [];
        for ($i = 0; $i < 16; $i++) {
            $fotos[] = $this->imagenFalsa("foto{$i}.jpg");
        }

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/inventarios",
            $this->datosInventario(['fotos' => $fotos])
        )->assertSessionHasErrors('fotos');

        $this->assertDatabaseCount('inventario_imagenes', 0);
    }

    public function test_la_imagen_de_zona_se_sirve_desde_el_disco_configurado(): void
    {
        $this->actingAs($this->admin)->post('/admin/zonas', [
            'nombre'       => 'Zona con imagen',
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'imagen'       => $this->imagenFalsa('zona.jpg'),
        ])->assertRedirect(route('admin.zonas.index'));

        $ruta = Zona::where('nombre', 'Zona con imagen')->value('imagen_path');

        Storage::assertExists($ruta);

        // La vista construye la URL con Storage::url(), no con asset('storage/…').
        $this->actingAs($this->admin)
            ->get('/admin/zonas')
            ->assertOk()
            ->assertSee(Storage::url($ruta), false);
    }

    public function test_una_imagen_ausente_no_rompe_la_vista(): void
    {
        // Fila que apunta a un archivo inexistente: el caso de todas las fotos
        // perdidas en redespliegues anteriores.
        $this->zona->update(['imagen_path' => 'zonas/desaparecida.jpg']);

        $this->actingAs($this->admin)->get('/admin/zonas')->assertOk();
        $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk();
    }

    /**
     * AUDITORIA.md M9: enviar una imagen nueva y marcar "quitar imagen" en la
     * misma petición guardaba el archivo nuevo y luego anulaba la referencia,
     * dejándolo huérfano en disco y perdiendo el cambio. ZonaController::update()
     * ahora resuelve la petición contradictoria a favor de la imagen nueva
     * (ver el comentario "Gana la imagen nueva").
     */
    public function test_subir_imagen_nueva_y_marcar_quitar_imagen_a_la_vez_conserva_la_nueva(): void
    {
        $vieja = $this->imagenFalsa('vieja.jpg')->store('zonas');
        $this->zona->update(['imagen_path' => $vieja]);

        $this->actingAs($this->admin)->put("/admin/zonas/{$this->zona->id}", [
            'nombre'         => $this->zona->nombre,
            'lugar_id'       => $this->zona->lugar_id,
            'jefe_user_id'   => $this->jefe->id,
            'imagen'         => $this->imagenFalsa('nueva.jpg'),
            'quitar_imagen'  => '1',
        ])->assertRedirect(route('admin.zonas.index'));

        $rutaFinal = $this->zona->fresh()->imagen_path;

        // Gana la imagen nueva: no queda null.
        $this->assertNotNull($rutaFinal);
        $this->assertNotSame($vieja, $rutaFinal);
        Storage::assertExists($rutaFinal);

        // La vieja se borra, no queda huérfana en disco.
        Storage::assertMissing($vieja);
    }
}
