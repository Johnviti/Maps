<?php
class Map_Helper {
    public static function get_concurso_icon_url() {
        $icon_url = get_option('mapa_icon_url');
        return $icon_url ? esc_url($icon_url) : PLUGIN_URL . 'assets/images/concurso-icon.png'; // Ícone padrão
    }

    public static function get_user_icon_url() {
        $icon_url = get_option('user_icon_url');
        return $icon_url ? esc_url($icon_url) : PLUGIN_URL . 'assets/images/user-icon.png'; // Ícone padrão
    }
    
    public static function get_concursos_by_filter($tag = '', $mais_procurados = false) {
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1, 
        ];

        if (!empty($tag)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_tag', // Taxonomia para tags de produtos
                    'field' => 'slug',          // Comparação pelo slug da tag
                    'terms' => $tag,
                ],
            ];
        }

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
    
        $concurso_icon_url = self::get_concurso_icon_url();
        $user_icon_url = self::get_user_icon_url();
    
        echo '<div id="' . esc_attr($map_id) . '" style="width: 100%; height: 400px;"></div>';
        echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>';
        echo '<script>
            var map = L.map("' . esc_attr($map_id) . '");
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);
            
            var concursoIcon = L.icon({
                iconUrl: "' . esc_js($concurso_icon_url) . '",
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            });
    
            var userIcon = L.icon({
                iconUrl: "' . esc_js($user_icon_url) . '",
                iconSize: [20, 20],
                iconAnchor: [15, 30],
                popupAnchor: [0, -30]
            });
    
            // Variáveis para controle de localização
            var bounds = L.latLngBounds();
            var hasProducts = false;
        ';
    
        while ($concursos->have_posts()) {
            $concursos->the_post();
            $latitude = get_post_meta(get_the_ID(), '_concurso_latitude', true);
            $longitude = get_post_meta(get_the_ID(), '_concurso_longitude', true);
    
            if ($latitude && $longitude) {
                echo '
                    var productMarker = L.marker([' . esc_js($latitude) . ', ' . esc_js($longitude) . '], { icon: concursoIcon }).addTo(map)
                        .bindPopup("<b>' . esc_js(get_the_title()) . '</b>");
                    bounds.extend(productMarker.getLatLng());
                    hasProducts = true;
                ';
            }
        }
    
        echo '
            // Tenta obter a localização do usuário e ajustar o mapa
            navigator.geolocation.getCurrentPosition(function(position) {
                var userLat = position.coords.latitude;
                var userLng = position.coords.longitude;
    
                var userMarker = L.marker([userLat, userLng], { icon: userIcon }).addTo(map);
    
                // Centraliza no usuário e ajusta os limites
                bounds.extend(userMarker.getLatLng());
                if (hasProducts) {
                    map.fitBounds(bounds); // Ajusta para incluir o usuário e os produtos
                } else {
                    map.setView(userMarker.getLatLng(), 12); // Centraliza no usuário, se não houver produtos
                }
            }, function(error) {
                console.log("Erro ao obter a localização do usuário:", error.message);
                
                // Fallback: centraliza nos produtos, se houver
                if (hasProducts) {
                    map.fitBounds(bounds);
                } else {
                    map.setView([-9.6658, -35.735], 8); // Posição padrão
                }
            });
        ';
        echo '</script>';
    }
    
}


