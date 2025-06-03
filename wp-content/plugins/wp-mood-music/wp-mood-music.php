<?php
/**
 * Plugin Name: WP Mood Music
 * Description: Reproduce música según la hora del día o el clima al hacer clic en botones.
 * Version: 1.4
 * Author: Ángela
 */

// Shortcodes
function wpmood_music_button_time() {
    return '<button id="wpmood-button-time">🎵 Música por hora</button>';
}
function wpmood_music_button_weather(){
    return '<button id="wpmood-button-weather">🌦️ Música por clima</button>';

}
add_shortcode('mood_music_button_time', 'wpmood_music_button_time');
add_shortcode('mood_music_button_weather', 'wpmood_music_button_weather');

// Script 
function wpmoodmusic_cargar_script() {
    $audios_tiempo = [
        'morning' => get_option('moodmusic_audio_morning'),
        'afternoon' => get_option('moodmusic_audio_afternoon'),
        'evening' => get_option('moodmusic_audio_evening'),
        'night' => get_option('moodmusic_audio_night')
    ];

    $audios_clima = [
        'Clear' => get_option('moodmusic_weather_Clear'),
        'Clouds' => get_option('moodmusic_weather_Clouds'),
        'Rain' => get_option('moodmusic_weather_Rain'),
        'Snow' => get_option('moodmusic_weather_Snow'),
        'Mist' => get_option('moodmusic_weather_Mist'),
        'Thunderstorm' => get_option('moodmusic_weather_Thunderstorm')
    ];

    $api_key = get_option('moodmusic_api_key');
    ?>
    <style>
    /* Estilo para los botones de WP Mood Music */
    #wpmood-button-time,
    #wpmood-button-weather {
        background-color: #0073aa; /* Azul de WP */
        color: white;
        border: none;
        padding: 10px 18px;
        margin: 5px 10px 5px 0;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: background-color 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    #wpmood-button-time:hover,
    #wpmood-button-weather:hover {
        background-color: #005177; /* Azul más oscuro al pasar el ratón */
    }

    #wpmood-button-time:focus,
    #wpmood-button-weather:focus {
        outline: 2px solid #80bfff; /* foco accesible */
        outline-offset: 2px;
    }
</style>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const audioTiempo = <?php echo json_encode($audios_tiempo); ?>;
    const audioClima = <?php echo json_encode($audios_clima); ?>;
    const apiKey = "<?php echo $api_key; ?>";

    const buttonTime = document.getElementById('wpmood-button-time');
    const buttonWeather = document.getElementById('wpmood-button-weather');

    let currentAudio = null;

    function reproducirAudio(url) {
        if (currentAudio) {
            currentAudio.pause();
            currentAudio.currentTime = 0;
        }

        currentAudio = new Audio(url);
        currentAudio.loop = true;
        currentAudio.play().catch(err => console.error("Error al reproducir:", err));
    }

    if (buttonTime) {
        buttonTime.addEventListener('click', function () {
            const hour = new Date().getHours();
            let audioURL = '';

            if (hour >= 6 && hour < 12) {
                audioURL = audioTiempo.morning;
            } else if (hour >= 12 && hour < 18) {
                audioURL = audioTiempo.afternoon;
            } else if (hour >= 18 && hour < 22) {
                audioURL = audioTiempo.evening;
            } else {
                audioURL = audioTiempo.night;
            }

            if (audioURL) {
                reproducirAudio(audioURL);
            } else {
                alert("No hay audio configurado para esta hora.");
            }
        });
    }

    if (buttonWeather) {
        buttonWeather.addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert("La geolocalización no está disponible. Usando Madrid por defecto.");
            usarClimaPorDefecto();
        } else {
            navigator.geolocation.getCurrentPosition(function (position) {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                obtenerClimaYReproducir(lat, lon);
            }, function (err) {
                console.error("Error en geolocalización:", err);
                alert("No se pudo obtener la ubicación. Usando Madrid por defecto.");
                usarClimaPorDefecto();
            });
        }
    });
}

function usarClimaPorDefecto() {
    const latMadrid = 40.4168;
    const lonMadrid = -3.7038;
    obtenerClimaYReproducir(latMadrid, lonMadrid);
}

function obtenerClimaYReproducir(lat, lon) {
    fetch(`https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&units=metric&lang=es&appid=${apiKey}`)
        .then(res => res.json())
        .then(data => {
            const main = data.weather[0].main;
            const audioURL = audioClima[main];

            if (audioURL) {
                reproducirAudio(audioURL);
            } else {
                alert("No hay música asignada para este clima: " + main);
            }
        }).catch(err => {
            console.error("Error al obtener el clima:", err);
        });
}
});
</script>
    <?php
}
add_action('wp_head', 'wpmoodmusic_cargar_script');

//  ADMIN MENU 
add_action('admin_menu', 'moodmusic_crear_menu');

function moodmusic_crear_menu() {
    add_menu_page(
        'Mood Music',   // Título de la página (lo que aparece en la pestaña del navegador)
        'Mood Music',   //Texto del menú (lo que ves en la barra lateral del admin)
        'manage_options',  //Capacidad necesaria para ver esta página (solo admins)
        'mood-music-ajustes',  //Slug único para la URL
        'moodmusic_pagina_ajustes',  //Función que muestra el contenido de la página
        'dashicons-format-audio'   //Icono que aparece en el menú
    );
}

function moodmusic_pagina_ajustes() {
    if (isset($_POST['submit'])) {
        $momentos = ['morning', 'afternoon', 'evening', 'night'];
        $climas = ['Clear', 'Clouds', 'Rain', 'Snow', 'Mist', 'Thunderstorm'];

        if (!empty($_POST['api_key'])) {
            update_option('moodmusic_api_key', sanitize_text_field($_POST['api_key']));
        }

        foreach ($momentos as $momento) {
            if (!empty($_FILES[$momento]['tmp_name'])) {
                $subida = wp_handle_upload($_FILES[$momento], ['test_form' => false]);
                if (!isset($subida['error'])) {
                    update_option("moodmusic_audio_$momento", $subida['url']);
                }
            }
        }

        foreach ($climas as $clima) {
            if (!empty($_FILES[$clima]['tmp_name'])) {
                $subida = wp_handle_upload($_FILES[$clima], ['test_form' => false]);
                if (!isset($subida['error'])) {
                    update_option("moodmusic_weather_$clima", $subida['url']);
                }
            }
        }
        echo '<div class="updated"><p>Audios guardados correctamente.</p></div>';
    }

    ?>
    <style>
    .moodmusic-instrucciones {
        background-color: #f0f8ff; /* azul clarito */
        border-left: 4px solid #0073aa; /* azul WP admin */
        padding: 15px 20px;
        margin-bottom: 20px;
        font-family: Arial, sans-serif;
        color: #333;
        line-height: 1.5;
        border-radius: 4px;
    }
    .moodmusic-instrucciones ul {
        margin-top: 8px;
        margin-left: 20px;
    }
    .moodmusic-instrucciones strong {
        color: #0073aa;
    }
</style>
    <div class="wrap">
        <h1>Configuración de Mood Music</h1>
        <div class="moodmusic-instrucciones">
    Bienvenido a la configuración de <strong>WP Mood Music</strong>. Aquí puedes subir los archivos de audio que se reproducirán según la hora del día o el clima actual.
    <br>
    <strong>Cómo usar el plugin:</strong>
    <ul>
        <li>Sube un archivo de audio para cada tramo horario (mañana, tarde, noche y madrugada).</li>
        <li>Introduce tu API Key de OpenWeatherMap para que el plugin pueda obtener el clima actual.</li>
        <li>Sube un archivo de audio para cada tipo de clima que quieras soportar (despejado, nublado, lluvia, etc.).</li>
        <li>Luego, en tus páginas o entradas, inserta los shortcodes <code>[mood_music_button_time]</code> para reproducir música según la hora, o <code>[mood_music_button_weather]</code> para reproducir música según el clima.</li>
        <li>Al pulsar los botones en el frontend se reproducirá la música correspondiente.</li>
    </ul>
    ¡Disfruta de la música adaptada a tu estado de ánimo y entorno!
</div>
        <form method="post" enctype="multipart/form-data">
            
            <h2>Música por hora del día</h2>
            <table class="form-table">
                <?php
                $momentos = [
                    'morning' => 'Mañana (6h - 12h)',
                    'afternoon' => 'Tarde (12h - 18h)',
                    'evening' => 'Noche (18h - 22h)',
                    'night' => 'Madrugada (22h - 6h)'
                ];
                foreach ($momentos as $clave => $etiqueta) {
                    echo "<tr>
                        <th><label for='$clave'>$etiqueta</label></th>
                        <td>
                            <input type='file' name='$clave' accept='audio/*' />
                        </td>
                    </tr>";
                }
                ?>
            </table>
            <h2>API Key</h2>
                <table class="form-table">
            <tr>
                <th><label for="api_key">API Key de OpenWeatherMap</label></th>
                <td>
                    <input type="text" name="api_key" value="<?php echo esc_attr(get_option('moodmusic_api_key')); ?>" size="50" />
                    <p class="description">Introduce tu API key de OpenWeatherMap.</p>
                </td>
            </tr>
            </table>

            <h2>Música según el clima</h2>
            <table class="form-table">
                <?php
                $climas = ['Clear' => 'Despejado', 'Clouds' => 'Nublado', 'Rain' => 'Lluvia', 'Snow' => 'Nieve', 'Mist' => 'Niebla', 'Thunderstorm' => 'Tormenta'];
                foreach ($climas as $clave => $etiqueta) {
                    echo "<tr>
                        <th><label for='$clave'>$etiqueta</label></th>
                        <td>
                            <input type='file' name='$clave' accept='audio/*' />
                        </td>
                    </tr>";
                }
                ?>
            </table>

            <?php submit_button('Guardar audios'); ?>
        </form>
    </div>
    <?php
}