$(document).ready(function () {
    $("#accordion").accordion({
        header: 'h3',
        collapsible: true,
        heightStyle: "content"
    });
});

function pasoAOrdenar(elemento, pagina) {
    var ordenElementos = $('#' + elemento).sortable("toArray").toString();
    var parametros = {
        "elementos": ordenElementos,
        "accion": 2
    };
    $.ajax({
        data: parametros,
        url: pagina,
        type: 'post',
        success: function (response) {
            if (response) {
                var obj = jQuery.parseJSON(response);
                console.log(obj.MENSAJE);
                if (obj.ERROR == 0) {
                    var box = bootbox.alert(obj.MENSAJE);
                    setTimeout(function () {
                        // be careful not to call box.hide() here, which will invoke jQuery's hide method
                        box.modal('hide');
                    }, 200);
                } else {
                    alert('Error: ' + obj.ERROR + ', ' + obj.MENSAJE);
                }
            }
        },
        fail: function (response) {
            console.log(response);
        }
    });

}
