<?php
function mapa_concursos_enqueue_scripts() {
    wp_enqueue_style('mapa-concursos-style', plugin_dir_url(__FILE__) . '../assets/css/style.css');

    wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css');
    wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', [], null, true);

    // wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', [], '1.7.1', true);
    
    wp_enqueue_script('mapa-concursos-js', plugin_dir_url(__FILE__) . '../assets/js/mapa.js', ['leaflet-js'], '1.0', true);
}
add_action('wp_enqueue_scripts', 'mapa_concursos_enqueue_scripts');
