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

define('PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PLUGIN_URL', plugin_dir_url(__FILE__));


require_once PLUGIN_PATH . 'includes/enqueue-scripts.php';
require_once PLUGIN_PATH . 'includes/class-mapa.php';

function mapa_concursos_init() {

    add_menu_page(
        'Mapa de Concursos',
        'Mapa de Concursos',
        'manage_options',
        'mapa-concursos',
        'mapa_concursos_page',
        PLUGIN_URL . 'assets/images/mapa-icon.png', 
        25
    );
}
add_action('admin_menu', 'mapa_concursos_init');


add_action('woocommerce_product_options_general_product_data', 'add_concurso_coordinates_fields');
function add_concurso_coordinates_fields() {

    woocommerce_wp_text_input([
        'id' => '_concurso_latitude',
        'label' => 'Latitude do Concurso',
        'placeholder' => 'Ex: -9.6487',
        'desc_tip' => true,
        'description' => 'Insira a latitude do local do concurso.'
    ]);

    woocommerce_wp_text_input([
        'id' => '_concurso_longitude',
        'label' => 'Longitude do Concurso',
        'placeholder' => 'Ex: -35.7370',
        'desc_tip' => true,
        'description' => 'Insira a longitude do local do concurso.'
    ]);
}

add_action('woocommerce_process_product_meta', 'save_concurso_coordinates_fields');
function save_concurso_coordinates_fields($post_id) {

    $latitude = isset($_POST['_concurso_latitude']) ? sanitize_text_field($_POST['_concurso_latitude']) : '';
    update_post_meta($post_id, '_concurso_latitude', $latitude);

    $longitude = isset($_POST['_concurso_longitude']) ? sanitize_text_field($_POST['_concurso_longitude']) : '';
    update_post_meta($post_id, '_concurso_longitude', $longitude);
}

// Função da página
function mapa_concursos_page() {
    echo '<h1>Configurações do Mapa de Concursos</h1>';
    echo '<p>Configure os mapas e informações dos concursos próximos ao usuário.</p>';
}

function shortcode_mapa_concursos($atts) {
    $atts = shortcode_atts(['categoria' => ''], $atts, 'mapa_concursos');
    ob_start();
    Map_Helper::render_mapa_concursos($atts['categoria']);
    return ob_get_clean();
}
add_shortcode('mapa_concursos', 'shortcode_mapa_concursos');
