<?php
class Map_Helper {
    public static function get_concursos_by_category($categoria) {
        $args = [
            'post_type' => 'product',
            'meta_key' => '_concurso_categoria',
            'meta_value' => $categoria
        ];
        return new WP_Query($args);
    }

    public static function render_mapa_concursos($categoria) {
        $concursos = self::get_concursos_by_category($categoria);
        
        echo '<div id="mapa_' . esc_attr($categoria) . '" style="width: 100%; height: 400px;"></div>';
        echo '<script>
            var map = L.map("mapa_' . esc_attr($categoria) . '").setView([-9.6658, -35.735], 8);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);';

        while ($concursos->have_posts()) {
            $concursos->the_post();
            $latitude = get_post_meta(get_the_ID(), '_concurso_latitude', true);
            $longitude = get_post_meta(get_the_ID(), '_concurso_longitude', true);
            
            if ($latitude && $longitude) {
                echo 'L.marker([' . esc_js($latitude) . ', ' . esc_js($longitude) . ']).addTo(map)
                      .bindPopup("<b>' . esc_js(get_the_title()) . '</b>");';
            }
        }

        echo '</script>';
    }
}
