<?php
function mapa_concursos_enqueue_scripts() {
    if (!is_admin() && has_shortcode(get_post_field('post_content', get_the_ID()), 'mapa_concursos')) {
        wp_enqueue_style('mapa-concursos-style', plugins_url('../assets/css/style.css', __FILE__));
        
        // Adiciona a versão mais recente do Leaflet com os atributos integrity e crossorigin
        wp_enqueue_style(
            'leaflet-css',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            null,
            'all'
        );
        
        wp_script_add_data('leaflet-css', 'integrity', 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=');
        wp_script_add_data('leaflet-css', 'crossorigin', 'anonymous');

        wp_enqueue_script(
            'leaflet-js',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            null,
            true
        );

        wp_script_add_data('leaflet-js', 'integrity', 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=');
        wp_script_add_data('leaflet-js', 'crossorigin', 'anonymous');

        wp_enqueue_script('mapa-concursos-js', plugins_url('../assets/js/mapa.js', __FILE__), ['leaflet-js'], '1.0', true);
    }
}
add_action('wp_enqueue_scripts', 'mapa_concursos_enqueue_scripts');
