<?php
class Map_Helper {
    public static function get_concurso_icon_url() {
        $icon_url = get_option('mapa_icon_url');
        return $icon_url ? esc_url($icon_url) : PLUGIN_URL . 'assets/images/concurso-icon.png';
    }

    public static function get_user_icon_url() {
        $icon_url = get_option('user_icon_url');
        return $icon_url ? esc_url($icon_url) : PLUGIN_URL . 'assets/images/user-icon.png';
    }
    
    public static function get_concursos_by_filter($tag = '', $mais_procurados = false) {
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1, 
        ];

        if (!empty($tag)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_tag', 
                    'field' => 'slug',          
                    'terms' => $tag,
                ],
            ];
        }

        if ($mais_procurados) {
            $args['meta_key'] = 'total_views'; 
            $args['orderby'] = 'meta_value_num'; 
            $args['order'] = 'DESC';
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
    
            $titulo_edital = get_field('titulo_edital'); 
            $texto_edital = get_field('texto_edital');
            $nivel_meta = get_field('nivel');
            $nivel = $nivel_meta ? $nivel_meta['texto_edital'] : '';
            $vagas_meta = get_field('vagas');
            $vagas = $vagas_meta ? $vagas_meta['texto_edital'] : '';
            $inscricoes_meta = get_field('inscricoes');
            $inscricoes = $inscricoes_meta ? $inscricoes_meta['texto_edital'] : '';

    
            $link = get_permalink();
    
            if ($latitude && $longitude) {
                echo '
                    var productMarker = L.marker([' . esc_js($latitude) . ', ' . esc_js($longitude) . '], { icon: concursoIcon }).addTo(map)
                        .bindPopup(`
                            <div>
                                <h3>' . esc_js(get_the_title()) . '</h3>
                                <p><strong>Edital:</strong> ' . esc_js($texto_edital) . '</p>
                                <p><strong>Nível:</strong> ' . esc_js($nivel) . '</p>
                                <p><strong>Vagas:</strong> ' . esc_js($vagas) . '</p>
                                <p><strong>Inscrições:</strong> ' . esc_js($inscricoes) . '</p>
                                <a href="' . esc_js($link) . '" class="btn btn-primary" target="_blank">Ver mais</a>
                            </div>
                        `);
                    bounds.extend(productMarker.getLatLng());
                    hasProducts = true;
                ';
            }
        }
    
        echo '
            navigator.geolocation.getCurrentPosition(function(position) {
                var userLat = position.coords.latitude;
                var userLng = position.coords.longitude;
    
                var userMarker = L.marker([userLat, userLng], { icon: userIcon }).addTo(map);
    
                bounds.extend(userMarker.getLatLng());

                // Configuração do raio inicial e máximo
                var radius = 5000; // 5km de início
                var maxRadius = 2000000; // 2000km
                var incrementStep = 10000; // Aumenta em 10km inicialmente

                function adjustZoomToRadius(radius) {
                    map.setView(userMarker.getLatLng(), map.getBoundsZoom(bounds));

                    // Verifica se há produtos visíveis no raio atual
                    var productsInView = bounds.contains(userMarker.getLatLng());

                    // Se ao menos um produto estiver visível, mantemos o zoom atual
                    if (productsInView && hasProducts) {
                        return;
                    }

                    // Ajusta o raio se nenhum produto estiver visível
                    if (!productsInView && radius <= maxRadius) {
                        if (radius < 500000) {
                            incrementStep = 50000; // Aumenta em 50km se o raio for menor que 500km
                        } else {
                            incrementStep = 250000; // Aumenta em 250km se o raio for maior que 500km
                        }

                        radius += incrementStep;
                        adjustZoomToRadius(radius);
                    }
                }

                if (hasProducts) {
                    adjustZoomToRadius(radius);
                } else {
                    map.setView(userMarker.getLatLng(), 12); // Centraliza no usuário se não houver produtos
                }
            }, function(error) {
                console.log("Erro ao obter a localização do usuário:", error.message);
                
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


