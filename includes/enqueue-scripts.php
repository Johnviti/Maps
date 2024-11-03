<?php
function mapa_concursos_enqueue_scripts() {
    // Adiciona estilo customizado do plugin
    wp_enqueue_style('mapa-concursos-style', plugin_dir_url(__FILE__) . '../assets/css/style.css');
    
    // Adiciona Leaflet.js (exemplo de biblioteca de mapas)
    wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', [], '1.7.1', true);
    
    // Adiciona o script principal do plugin
    wp_enqueue_script('mapa-concursos-js', plugin_dir_url(__FILE__) . '../assets/js/mapa.js', ['leaflet-js'], '1.0', true);
}
add_action('wp_enqueue_scripts', 'mapa_concursos_enqueue_scripts');
