<?php
class Map_Helper {
    public static function get_concursos_by_filter($tag = '', $mais_procurados = false) {
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1, // Pega todos os produtos sem limite
        ];

        // Filtrar por tag, se fornecida
        if (!empty($tag)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_tag', // Taxonomia para tags de produtos
                    'field' => 'slug',          // Comparação pelo slug da tag
                    'terms' => $tag,
                ],
            ];
        }

        // Ordenar por mais procurados, se solicitado
        if ($mais_procurados) {
            $args['meta_key'] = 'total_views'; // Chave meta que armazena as visualizações
            $args['orderby'] = 'meta_value_num'; // Ordena pelo valor numérico da meta
            $args['order'] = 'DESC'; // Mais procurados primeiro
        }

        return new WP_Query($args);
    }

    public static function render_mapa_concursos($tag = '', $mais_procurados = false) {
        $concursos = self::get_concursos_by_filter($tag, $mais_procurados);
        $map_id = 'mapa_' . esc_attr($tag) . '_' . uniqid();

        echo '<div id="' . esc_attr($map_id) . '" style="width: 100%; height: 400px;"></div>';
        echo '<script>
            var map = L.map("' . esc_attr($map_id) . '").setView([-9.6658, -35.735], 8);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);
            
            var concursoIcon = L.icon({
                iconUrl: "' . PLUGIN_URL . 'assets/images/concurso-icon.png",
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            });
        ';

        while ($concursos->have_posts()) {
            $concursos->the_post();
            $latitude = get_post_meta(get_the_ID(), '_concurso_latitude', true);
            $longitude = get_post_meta(get_the_ID(), '_concurso_longitude', true);
            
            if ($latitude && $longitude) {
                echo 'L.marker([' . esc_js($latitude) . ', ' . esc_js($longitude) . '], { icon: concursoIcon }).addTo(map)
                      .bindPopup("<b>' . esc_js(get_the_title()) . '</b>");';
            }
        }

        echo 'navigator.geolocation.getCurrentPosition(function(position) {
                var userLat = position.coords.latitude;
                var userLng = position.coords.longitude;

                var userIcon = L.icon({
                    iconUrl: "' . PLUGIN_URL . 'assets/images/user-icon.png",
                    iconSize: [30, 30],
                    iconAnchor: [15, 30],
                    popupAnchor: [0, -30]
                });

                L.marker([userLat, userLng], { icon: userIcon }).addTo(map)
                    .bindPopup("Você está aqui").openPopup();

                map.setView([userLat, userLng], 12);
            }, function(error) {
                console.log("Erro ao obter a localização do usuário:", error.message);
            });
        ';
        echo '</script>';
    }
}

