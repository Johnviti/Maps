function uploadIcon(inputId) {
    var customUploader = wp.media({
        title: 'Selecionar Ícone',
        button: {
            text: 'Usar este ícone'
        },
        multiple: false
    })
    .on('select', function() {
        var attachment = customUploader.state().get('selection').first().toJSON();
        document.getElementById(inputId).value = attachment.url;
    })
    .open();
}
