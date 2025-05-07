<?php
/**
 * Plugin Name: WP Mood Music
 * Description: Reproduce música según la hora del día al hacer clic en un botóooooooon.
 * Version: 1.0
 * Author: Ángela
 */

// Función para encolar el script y el CSS dentro del archivo PHP
function wpmood_cargar_script() {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById('wpmood-button');
            if (!button) return;

            button.addEventListener('click', function () {
                const hour = new Date().getHours();
                let audioFile = '';

                // Decidir qué archivo de audio reproducir según la hora
                if (hour >= 6 && hour < 12) {
                    audioFile = 'morning.mp3'; // mañana
                } else if (hour >= 12 && hour < 18) {
                    audioFile = 'afternoon.mp3'; // tarde
                } else if (hour >= 18 && hour < 22) {
                    audioFile = 'evening.mp3'; // noche
                } else {
                    audioFile = 'night.mp3'; // madrugada
                }

                // Reproducir el audio correspondiente
                const audio = new Audio('<?php echo plugin_dir_url(__FILE__); ?>audio/' + audioFile);
                audio.loop = true;
                audio.play().catch(function(error) {
                    console.error("Error al intentar reproducir el audio:", error);
                });
            });
        });
    </script>
    <?php
}
add_action('wp_head', 'wpmood_cargar_script');

// Shortcode para mostrar el botón
function wpmood_music_button_shortcode() {
    return '<button id="wpmood-button">🎵 Activar música</button>';
}
add_shortcode('mood_music_button', 'wpmood_music_button_shortcode');
