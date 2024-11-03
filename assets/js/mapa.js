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
