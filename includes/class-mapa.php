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

    public static function render_map($tag = '', $mais_procurados = false) {
        $concursos = self::get_concursos_by_filter($tag, $mais_procurados);
        $map_id = 'mapa_' . esc_attr($tag);

        $concurso_icon_url = self::get_concurso_icon_url();
        $user_icon_url = self::get_user_icon_url();

        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

        ob_start();
        ?>
        <div id="<?php echo esc_attr($map_id); ?>" style="width: 100%; height: 400px;"></div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
            var map = L.map("<?php echo esc_js($map_id); ?>");

            function resizeMap() {
                var currentCenter = map.getCenter();
                var currentZoom = map.getZoom();    
                map.invalidateSize();
                map.setView(currentCenter, currentZoom);

                navigator.geolocation.getCurrentPosition(function(position) {
                    var userLat = position.coords.latitude;
                    var userLng = position.coords.longitude;

                    var userMarker = L.marker([userLat, userLng], { icon: userIcon }).addTo(map);

                    map.setView(userMarker.getLatLng(), 12);
                    userMarker.addTo(map);

                    function calculateDistance(lat1, lon1, lat2, lon2) {
                        var R = 6371e3;
                        var φ1 = lat1 * Math.PI / 180;
                        var φ2 = lat2 * Math.PI / 180;
                        var Δφ = (lat2 - lat1) * Math.PI / 180;
                        var Δλ = (lon2 - lon1) * Math.PI / 180;

                        var a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                            Math.cos(φ1) * Math.cos(φ2) *
                            Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
                        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

                        return R * c;
                    }

                    var radius = 5000;
                    var maxRadius = 2000000;
                    var incrementStep = 10000;

                    var nearestProductMarker = null;

                    function findNearestProduct() {
                        var nearestDistance = Infinity;

                        map.eachLayer(function(layer) {
                            if (layer instanceof L.Marker && layer !== userMarker) {
                                var productLatLng = layer.getLatLng();
                                var distance = calculateDistance(userLat, userLng, productLatLng.lat, productLatLng.lng);

                                if (distance <= radius && distance < nearestDistance) {
                                    nearestDistance = distance;
                                    nearestProductMarker = layer;
                                }
                            }
                        });
                    }

                    function adjustZoomToNearestProduct() {
                        findNearestProduct();

                        if (nearestProductMarker) {
                            map.setView(nearestProductMarker.getLatLng(), 12);
                            map.fitBounds(L.latLngBounds([userMarker.getLatLng(), nearestProductMarker.getLatLng()]));
                        } else if (radius <= maxRadius) {
                            if (radius < 500000) {
                                incrementStep = 50000;
                            } else {
                                incrementStep = 250000;
                            }

                            radius += incrementStep;
                            adjustZoomToNearestProduct();
                        }
                    }

                    if (hasProducts) {
                        adjustZoomToNearestProduct();
                    } else {
                        map.setView(userMarker.getLatLng(), 12);
                    }
                }, function(error) {
                    console.log("Erro ao obter a localização do usuário:", error.message);

                    if (hasProducts) {
                        map.fitBounds(bounds);
                    } else {
                        map.setView([-9.6658, -35.735], 8);
                    }
                })
            };

            var container = document.getElementById("<?php echo esc_js($map_id); ?>").parentElement;

            if (container && container.offsetWidth === 0) {
                var interval = setInterval(function() {
                    if (container.offsetWidth > 0) {
                        resizeMap(); 
                        clearInterval(interval);
                    }
                }, 100);
            } 


            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);

            var concursoIcon = L.icon({
                iconUrl: "<?php echo esc_js($concurso_icon_url); ?>",
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            });

            var userIcon = L.icon({
                iconUrl: "<?php echo esc_js($user_icon_url); ?>",
                iconSize: [20, 20],
                iconAnchor: [15, 30],
                popupAnchor: [0, -30]
            });

            var bounds = L.latLngBounds();
            var hasProducts = false;
        <?php
        while ($concursos->have_posts()) :
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

            if ($latitude && $longitude) :
        ?>
                var productMarker = L.marker([<?php echo esc_js($latitude); ?>, <?php echo esc_js($longitude); ?>], { icon: concursoIcon }).addTo(map)
                    .bindPopup(`
                        <div>
                            <h3><?php echo esc_js(get_the_title()); ?></h3>
                            <p><strong>Edital:</strong> <?php echo esc_js($texto_edital); ?></p>
                            <p><strong>Nível:</strong> <?php echo esc_js($nivel); ?></p>
                            <p><strong>Vagas:</strong> <?php echo esc_js($vagas); ?></p>
                            <p><strong>Inscrições:</strong> <?php echo esc_js($inscricoes); ?></p>
                            <a href="<?php echo esc_js($link); ?>" class="btn btn-primary" target="_blank">Ver mais</a>
                        </div>
                    `);
                bounds.extend(productMarker.getLatLng());
                hasProducts = true;
        <?php
            endif;
        endwhile;
        wp_reset_postdata();
        ?>
            navigator.geolocation.getCurrentPosition(function(position) {
                var userLat = position.coords.latitude;
                var userLng = position.coords.longitude;

                var userMarker = L.marker([userLat, userLng], { icon: userIcon }).addTo(map);

                map.setView(userMarker.getLatLng(), 12);
                userMarker.addTo(map);

                function calculateDistance(lat1, lon1, lat2, lon2) {
                    var R = 6371e3;
                    var φ1 = lat1 * Math.PI / 180;
                    var φ2 = lat2 * Math.PI / 180;
                    var Δφ = (lat2 - lat1) * Math.PI / 180;
                    var Δλ = (lon2 - lon1) * Math.PI / 180;

                    var a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                        Math.cos(φ1) * Math.cos(φ2) *
                        Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
                    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

                    return R * c;
                }

                var radius = 5000;
                var maxRadius = 2000000;
                var incrementStep = 10000;

                var nearestProductMarker = null;

                function findNearestProduct() {
                    var nearestDistance = Infinity;

                    map.eachLayer(function(layer) {
                        if (layer instanceof L.Marker && layer !== userMarker) {
                            var productLatLng = layer.getLatLng();
                            var distance = calculateDistance(userLat, userLng, productLatLng.lat, productLatLng.lng);

                            if (distance <= radius && distance < nearestDistance) {
                                nearestDistance = distance;
                                nearestProductMarker = layer;
                            }
                        }
                    });
                }

                function adjustZoomToNearestProduct() {
                    findNearestProduct();

                    if (nearestProductMarker) {
                        map.setView(nearestProductMarker.getLatLng(), 12);
                        map.fitBounds(L.latLngBounds([userMarker.getLatLng(), nearestProductMarker.getLatLng()]));
                    } else if (radius <= maxRadius) {
                        if (radius < 500000) {
                            incrementStep = 50000;
                        } else {
                            incrementStep = 250000;
                        }

                        radius += incrementStep;
                        adjustZoomToNearestProduct();
                    }
                }

                if (hasProducts) {
                    adjustZoomToNearestProduct();
                } else {
                    map.setView(userMarker.getLatLng(), 12);
                }
            }, function(error) {
                console.log("Erro ao obter a localização do usuário:", error.message);

                if (hasProducts) {
                    map.fitBounds(bounds);
                } else {
                    map.setView([-9.6658, -35.735], 8);
                }
            })
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
?>
