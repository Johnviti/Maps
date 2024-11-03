<?php
/*
Plugin Name: Mapa de Concursos Alagoas
Description: Exibe mapas com concursos públicos em Alagoas, categorizados e próximos do usuário.
Version: 1.0
Author: Seu Nome
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}


define('MAPA_CONCURSOS_PLUGIN_PATH', plugin_dir_path(__FILE__));

require_once MAPA_CONCURSOS_PLUGIN_PATH . 'includes/enqueue-scripts.php';

function mapa_concursos_init() {
    // Código de inicialização
}
add_action('plugins_loaded', 'mapa_concursos_init');

require_once MAPA_CONCURSOS_PLUGIN_PATH . 'includes/class-mapa.php';


function shortcode_mapa_concursos($atts) {
    $atts = shortcode_atts(['categoria' => ''], $atts, 'mapa_concursos');
    ob_start();
    Map_Helper::render_mapa_concursos($atts['categoria']);
    return ob_get_clean();
}
add_shortcode('mapa_concursos', 'shortcode_mapa_concursos');
