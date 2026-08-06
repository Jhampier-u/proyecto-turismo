<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * El seeder solía crear admin@turismo.com con la contraseña "password" y el
 * entrypoint de Docker lo ejecutaba en producción. Estos tests fijan que los
 * datos de demostración no salgan nunca del entorno local.
 */
class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_se_crean_usuarios_de_demostracion_fuera_de_local(): void
    {
        // El entorno de tests no es 'local'.
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_el_demo_seeder_se_niega_a_ejecutarse_fuera_de_local(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_el_administrador_inicial_sale_de_la_configuracion(): void
    {
        config([
            'turismo.admin_email'    => 'coordinacion@ejemplo.test',
            'turismo.admin_password' => 'una-contrasena-larga',
            'turismo.admin_nombre'   => 'Coordinación',
        ]);

        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'coordinacion@ejemplo.test')->first();

        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('una-contrasena-larga', $admin->password));
        $this->assertSame('admin', $admin->role->nombre);
    }

    public function test_el_administrador_no_se_recrea_si_ya_existe(): void
    {
        config([
            'turismo.admin_email'    => 'coordinacion@ejemplo.test',
            'turismo.admin_password' => 'la-original',
        ]);

        $this->seed(DatabaseSeeder::class);

        // La contraseña se cambia desde la aplicación...
        $admin = User::where('email', 'coordinacion@ejemplo.test')->first();
        $admin->update(['password' => 'cambiada-por-el-usuario']);

        // ...y un segundo arranque no debe revertirla.
        $this->artisan('db:seed', ['--class' => \Database\Seeders\AdminSeeder::class]);

        $this->assertTrue(
            Hash::check('cambiada-por-el-usuario', $admin->fresh()->password)
        );
        $this->assertSame(1, User::where('email', 'coordinacion@ejemplo.test')->count());
    }
}
