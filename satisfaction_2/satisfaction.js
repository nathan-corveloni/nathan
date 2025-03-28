/**
 * Função para carregar campos com base no tipo de questão selecionado
 *
 * @param val Tipo de questão selecionado
 * @param note Tipo "Nota"
 * @param emojiescale Tipo "Emoji Scale"
 */
function plugin_satisfaction_loadtype(val, note, emojiescale) {
    if (val == note) {
        $('#show_note').show();
    } else {
        $('#show_note').hide();
    }

    if (val == emojiescale) {
        $('#show_emojiescale').show();
    } else {
        $('#show_emojiescale').hide();
    }
}

/**
 * Função para carregar o valor padrão
 *
 * @param root_doc Caminho raiz do documento
 * @param default_value Valor padrão máximo permitido
 */
function plugin_satisfaction_load_defaultvalue(root_doc, default_value) {
    var value = $('input[name="default_value"]').val();

    if (value > default_value) {
        value = default_value;
    }

    $.ajax({
        url: root_doc + '/ajax/satisfaction.php',
        type: 'POST',
        data: '&action_default_value&default_value=' + default_value + '&value=' + value,
        dataType: 'html',
        success: function (code_html, statut) {
            $('#default_value').html(code_html);
        },
    });
}