document.addEventListener('DOMContentLoaded', function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;
            console.log("Usuário localizado em:", userLat, userLng);
        });
    } else {
        console.log("Geolocalização não suportada pelo navegador.");
    }
});

// Definindo os ícones do mapa e do usuário com fallback para ícones padrão
const mapaIconUrl = "<?php echo esc_js(get_option('mapa_icon_url', 'URL_DO_ICONE_PADRAO_DO_MAPA')); ?>";
const userIconUrl = "<?php echo esc_js(get_option('user_icon_url', 'URL_DO_ICONE_PADRAO_DO_USUARIO')); ?>";

const mapaIcon = L.icon({
    iconUrl: mapaIconUrl,
    iconSize: [32, 32], // ajuste o tamanho do ícone conforme necessário
});

const userIcon = L.icon({
    iconUrl: userIconUrl,
    iconSize: [32, 32], // ajuste o tamanho do ícone conforme necessário
});
