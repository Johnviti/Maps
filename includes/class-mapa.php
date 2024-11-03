<?php
class Map_Helper {
    public static function get_concursos_by_category($categoria = '') {
        $args = [
            'post_type' => 'product',
        ];
        
        // Condicional para quando não houver categoria
        if (!empty($categoria)) {
            // 'meta_key' => '_concurso_categoria',
            $args['meta_value'] = $categoria;
        }

        return new WP_Query($args);
    }
    
    public static function render_mapa_concursos($categoria = '') {
        $concursos = self::get_concursos_by_category($categoria);
        
        echo '<div id="mapa_' . esc_attr($categoria) . '" style="width: 100%; height: 400px;"></div>';
        echo '<script>
            var map = L.map("mapa_' . esc_attr($categoria) . '").setView([-9.6658, -35.735], 8);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);
            
            // Ícone personalizado para concursos
            var concursoIcon = L.icon({
                iconUrl: "' . PLUGIN_URL . 'assets/images/concurso-icon.png",
                iconSize: [40, 40], // Ajuste o tamanho conforme necessário
                iconAnchor: [20, 40], // Ponto de ancoragem do ícone (metade da largura e altura)
                popupAnchor: [0, -40] // Ponto onde o popup aparecerá em relação ao ícone
            });
        ';
    
        // Adiciona os marcadores dos concursos com o ícone personalizado
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
    
                // Define o ícone personalizado (boneco)
                var userIcon = L.icon({
                    iconUrl: "' . PLUGIN_URL . 'assets/images/user-icon.png", 
                    iconSize: [30, 30], 
                    iconAnchor: [15, 30], 
                    popupAnchor: [0, -30] 
                });
    
                // Adiciona o marcador do usuário ao mapa
                L.marker([userLat, userLng], { icon: userIcon }).addTo(map)
                    .bindPopup("Você está aqui").openPopup();
    
                // Centraliza o mapa na posição do usuário
                map.setView([userLat, userLng], 12);
            }, function(error) {
                console.log("Erro ao obter a localização do usuário:", error.message);
            });
        ';
        echo '</script>';
    }    
}
