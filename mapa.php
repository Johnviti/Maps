<?php
/*
Plugin Name: Mapa de Concursos 
Description: Exibe mapas com concursos públicos, categorizados e próximos do usuário.
Version: 1.0
Author: John Amorim
*/

if (!defined('ABSPATH')) {
    exit;
}

define('PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PLUGIN_URL', plugin_dir_url(__FILE__));


require_once PLUGIN_PATH . 'includes/enqueue-scripts.php';
require_once PLUGIN_PATH . 'includes/class-mapa.php';

function mapa_concursos_add_admin_page() {
    add_menu_page(
        'Mapa de Concursos',
        'Mapa de Concursos',
        'manage_options',
        'mapa_concursos_settings',
        'mapa_concursos_render_settings_page',
        'dashicons-location', 
        110
    );
}
add_action('admin_menu', 'mapa_concursos_add_admin_page');

function mapa_concursos_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Configurações do Mapa de Concursos</h1>
        <form method="post" action="options.php" enctype="multipart/form-data">
            <?php
            settings_fields('mapa_concursos_settings');
            do_settings_sections('mapa_concursos_settings');
            submit_button();
            ?>
        </form>
    </div>

    <!-- Script para upload de ícone usando a Media Library do WordPress -->
    <script type="text/javascript">
    function uploadIcon(inputId) {
        var custom_uploader = wp.media({
            title: 'Selecione o Ícone',
            button: {
                text: 'Usar este Ícone'
            },
            multiple: false
        }).on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            document.getElementById(inputId).value = attachment.url;
        }).open();
    }
    </script>
    <?php
}

function mapa_concursos_register_settings() {
    register_setting('mapa_concursos_settings', 'mapa_icon_url');
    register_setting('mapa_concursos_settings', 'user_icon_url');

    add_settings_section(
        'mapa_concursos_section',
        'Ícones do Mapa',
        null,
        'mapa_concursos_settings'
    );

    add_settings_field(
        'mapa_icon_url',
        'Ícone do Mapa',
        'mapa_concursos_icon_field_callback',
        'mapa_concursos_settings',
        'mapa_concursos_section',
        ['label_for' => 'mapa_icon_url']
    );

    add_settings_field(
        'user_icon_url',
        'Ícone do Usuário',
        'mapa_concursos_icon_field_callback',
        'mapa_concursos_settings',
        'mapa_concursos_section',
        ['label_for' => 'user_icon_url']
    );
}
add_action('admin_init', 'mapa_concursos_register_settings');

function mapa_concursos_icon_field_callback($args) {
    $option = get_option($args['label_for']);
    ?>
    <input type="text" id="<?php echo esc_attr($args['label_for']); ?>" name="<?php echo esc_attr($args['label_for']); ?>" value="<?php echo esc_url($option); ?>" />
    <input type="button" class="button" value="Upload Ícone" onclick="uploadIcon('<?php echo esc_attr($args['label_for']); ?>')" />
    <p class="description">URL do ícone. Deixe em branco para usar o ícone padrão.</p>
    <?php
}

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

    the_content();
}


function shortcode_mapa_concursos($atts) {
    $atts = shortcode_atts(['categoria' => ''], $atts, 'mapa_concursos');
    ob_start();
    Map_Helper::render_mapa_concursos($atts['categoria']);
    return ob_get_clean();
}
add_shortcode('mapa_concursos', 'shortcode_mapa_concursos');
