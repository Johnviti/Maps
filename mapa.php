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

function mapa_concursos_menu() {
    add_menu_page(
        'Configurações do Mapa de Concursos',
        'Mapa de Concursos',
        'manage_options',
        'mapa_concursos_settings',
        'mapa_concursos_page',
        'dashicons-location',
        30
    );
}
add_action('admin_menu', 'mapa_concursos_menu');


function mapa_concursos_page() {
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

    register_setting('mapa_concursos_settings', 'mapa_tag');
    register_setting('mapa_concursos_settings', 'mapa_mais_procurados');

    add_settings_section(
        'mapa_concursos_section',
        'Configurações do Mapa de Concursos',
        null,
        'mapa_concursos_settings'
    );

    // Campo para a tag
    add_settings_field(
        'mapa_tag',
        'Tag do Concurso',
        'mapa_concursos_tag_field_callback',
        'mapa_concursos_settings',
        'mapa_concursos_section',
        ['label_for' => 'mapa_tag']
    );

    // Campo para "Mais Procurados"
    add_settings_field(
        'mapa_mais_procurados',
        'Exibir Mais Procurados',
        'mapa_concursos_mais_procurados_field_callback',
        'mapa_concursos_settings',
        'mapa_concursos_section',
        ['label_for' => 'mapa_mais_procurados']
    );

    // Campo para copiar o shortcode
    add_settings_field(
        'mapa_shortcode',
        'Shortcode Gerado',
        'mapa_concursos_shortcode_field_callback',
        'mapa_concursos_settings',
        'mapa_concursos_section'
    );

   
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

function mapa_concursos_enqueue_media_uploader() {
    if (isset($_GET['page']) && $_GET['page'] === 'mapa_concursos_settings') {
        wp_enqueue_media();
        
        $script_path = PLUGIN_PATH . 'custom-script.js';
        if (file_exists($script_path)) {
            wp_enqueue_script(
                'mapa_concursos_custom_script',
                PLUGIN_URL . 'custom-script.js',
                ['jquery'],
                null,
                true
            );
        } else {
            error_log('O arquivo custom-script.js não foi encontrado no diretório do plugin.');
        }
    }
}
add_action('admin_enqueue_scripts', 'mapa_concursos_enqueue_media_uploader');

function mapa_concursos_icon_field_callback($args) {
    $option = get_option($args['label_for']);
    ?>
    <input type="text" id="<?php echo esc_attr($args['label_for']); ?>" name="<?php echo esc_attr($args['label_for']); ?>" value="<?php echo esc_url($option); ?>" />
    <input type="button" class="button" value="Upload Ícone" onclick="uploadIcon('<?php echo esc_attr($args['label_for']); ?>')" />
    <p class="description">URL do ícone. Deixe em branco para usar o ícone padrão.</p>
    <?php
}

function mapa_concursos_tag_field_callback($args) {
    $option = get_option($args['label_for']);
    echo '<input type="text" id="mapa_tag" name="mapa_tag" value="' . esc_attr($option) . '" />';
    echo '<p class="description">Digite a tag do concurso (ex: previstos, abertos, autorizados). Deixe em branco para exibir todos os concursos.</p>';
}

// Função para o campo de "Mais Procurados"
function mapa_concursos_mais_procurados_field_callback($args) {
    $option = get_option($args['label_for']);
    echo '<input type="checkbox" id="mapa_mais_procurados" name="mapa_mais_procurados" value="1" ' . checked(1, $option, false) . ' />';
    echo '<p class="description">Selecione para exibir os concursos mais procurados.</p>';
}

// Função para exibir o shortcode gerado
function mapa_concursos_shortcode_field_callback() {
    $tag = get_option('mapa_tag');
    $mais_procurados = get_option('mapa_mais_procurados');
    $shortcode = '[mapa_concursos';

    if (!empty($tag)) {
        $shortcode .= ' categoria="' . esc_attr($tag) . '"';
    }
    
    if ($mais_procurados) {
        $shortcode .= ' mais_procurados="true"';
    }
    
    $shortcode .= ']';

    echo '<input type="text" readonly="readonly" value="' . esc_attr($shortcode) . '" id="mapa_shortcode_field" class="regular-text" />';
    echo '<p class="description">Copie o shortcode acima e cole onde deseja exibir o mapa de concursos.</p>';
}

// ======= Finalização do Menu do Plugin =======

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
// function mapa_concursos_page() {
//     echo '<h1>Configurações do Mapa de Concursos</h1>';
//     echo '<p>Configure os mapas e informações dos concursos próximos ao usuário.</p>';

//     the_content();
// }


function shortcode_mapa_concursos($atts) {
    $atts = shortcode_atts(
        [
            'tag' => '',              
            'mais_procurados' => 'false' 
        ],
        $atts,
        'mapa_concursos'
    );

    $mais_procurados = filter_var($atts['mais_procurados'], FILTER_VALIDATE_BOOLEAN);

    return Map_Helper::render_map($atts['tag'], $mais_procurados);
}
add_shortcode('mapa_concursos', 'shortcode_mapa_concursos');





function mapa_concursos_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=mapa_concursos_settings') . '">Configurações</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'mapa_concursos_settings_link');