<?php
function mapa_concursos_enqueue_scripts() {
    if (!is_admin() && has_shortcode(get_post_field('post_content', get_the_ID()), 'mapa_concursos')) {
        wp_enqueue_style('mapa-concursos-style', plugins_url('../assets/css/style.css', __FILE__));
        wp_enqueue_script('mapa-concursos-js', plugins_url('../assets/js/mapa.js', __FILE__), ['leaflet-js'], '1.0', true);
    }
}
add_action('wp_enqueue_scripts', 'mapa_concursos_enqueue_scripts');
