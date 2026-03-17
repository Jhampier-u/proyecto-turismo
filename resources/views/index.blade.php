<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'UDAExplore') }} - Análisis de Potencial Turístico</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @vite(['resources/css/estilo.css'])
</head>

<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>UDAExplore</span>
                </div>
                <div class="nav-links">
                    <a href="#inicio">Inicio</a>
                    <a href="#mision-vision">Misión & Visión</a>
                    <a href="#proceso">Proceso</a>
                    <a href="#contacto">Contacto</a>
                    
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn-login">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn-login">
                                Iniciar Sesión
                            </a>
                        @endauth
                    @endif
                </div>
                <div class="celu-menu">
                    <i class="fas fa-bars"></i>
                </div>
            </nav>
        </div>
    </header>

    <!-- Sección de imagen -->
    <section class="start" id="inicio">
        <div class="container">
            <h1>Descubre el Potencial Turístico del Ecuador</h1>
            <p>Analizamos y evaluamos lugares con potencial turístico para impulsar el desarrollo económico local y
                promover el turismo sostenible.</p>
            <div class="hero-actions">
                <a href="#mision-vision" class="btn btn-primary">Conoce Más</a>
                @if (Route::has('login'))
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-secondary">Acceder al Sistema</a>
                    @endguest
                @endif
            </div>
        </div>
    </section>

    <!-- Misión/Visión -->
    <section class="mision-vision" id="mision-vision">
        <div class="container">
            <div class="titulo">
                <h2>Nuestro Propósito</h2>
                <p id="texto_grande">Transformamos lugares con potencial en destinos turísticos sostenibles y exitosos
                </p>
            </div>
            <div class="cartas">
                <div class="carta">
                    <div class="carta-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="contenido-carta">
                        <h3>Misión</h3>
                        <p>Identificar, analizar y desarrollar el potencial turístico de lugares subestimados,
                            implementando estrategias sostenibles que beneficien a las comunidades locales, preserven el
                            patrimonio cultural y natural, y generen oportunidades económicas a largo plazo.</p>
                    </div>
                </div>
                <div class="carta">
                    <div class="carta-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="contenido-carta">
                        <h3>Visión</h3>
                        <p>Ser la organización líder en la identificación y desarrollo de destinos turísticos
                            emergentes, reconocida por nuestro enfoque innovador, sostenible y comunitario,
                            contribuyendo a un mundo donde cada lugar con potencial turístico pueda florecer de manera
                            responsable.</p>
                    </div>
                </div>
                <div class="carta">
                    <div class="carta-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="contenido-carta">
                        <h3>Valores</h3>
                        <p><strong>Sostenibilidad:</strong> Desarrollo responsable con el medio ambiente.<br>
                            <strong>Innovación:</strong> Uso de tecnología para análisis precisos.<br>
                            <strong>Comunidad:</strong> Beneficio e inclusión de poblaciones locales.<br>
                            <strong>Calidad:</strong> Excelencia en cada proyecto emprendido.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Proceso -->
    <section class="process" id="proceso">
        <div class="container">
            <div class="titulo">
                <h2>Nuestro Proceso de Análisis</h2>
                <p>Metodología integral para evaluar el potencial turístico</p>
            </div>
            <div class="pasos-proceso">
                <div class="pasos">
                    <div class="pasos-numero">1</div>
                    <h3>Evaluación Inicial</h3>
                    <p>Análisis de ubicación, accesibilidad, recursos naturales y culturales disponibles.</p>
                </div>
                <div class="pasos">
                    <div class="pasos-numero">2</div>
                    <h3>Investigación de Mercado</h3>
                    <p>Estudio de tendencias turísticas, competencia y perfil de visitantes potenciales.</p>
                </div>
                <div class="pasos">
                    <div class="pasos-numero">3</div>
                    <h3>Análisis de Impacto</h3>
                    <p>Evaluación de impacto ambiental, social y económico en la comunidad local.</p>
                </div>
                <div class="pasos">
                    <div class="pasos-numero">4</div>
                    <h3>Plan de Desarrollo</h3>
                    <p>Elaboración de estrategias personalizadas para el desarrollo turístico sostenible.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contacto">
        <div class="container">
            <table class="footer-table">
                <tr>
                    <td class="footer-info">
                        <h3>UDAExplore</h3>
                        <p>Especialistas en análisis y desarrollo de potencial turístico sostenible.</p>
                        <div class="social-links">
                            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </td>

                    <td class="footer-contact">
                        <h3>Contacto</h3>
                        <p><i class="fas fa-map-marker-alt"></i> Av. 24 de Mayo, Cuenca, Ecuador</p>
                        <p><i class="fas fa-phone"></i> +593 7 408 8000</p>
                        <p><i class="fas fa-envelope"></i> info@uazuay.edu.ec</p>
                    </td>
                </tr>
            </table>
            <div class="copyright">
                <p>&copy; {{ date('Y') }} Universidad del Azuay. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        // Menu móvil toggle
        document.querySelector('.celu-menu').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('active');
        });

        // Smooth scroll para los enlaces de navegación
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    // Cerrar menú móvil si está abierto
                    document.querySelector('.nav-links').classList.remove('active');
                }
            });
        });
    </script>
</body>

</html>
