function correctIdentifierType(element)
{
    var in_value = $(element).val();
    var allow_uppercase = false;
    var out_value = in_value;

    if ($(element).data('identifier-allow') == 'uppercase') {
        allow_uppercase = true;
    }

    out_value = out_value.replace(/^ +/, '');
    out_value = out_value.replace(/ +$/, '');
    out_value = out_value.replace(/[^a-zA-Z0-9_]+/g, '_');

    if (!allow_uppercase) {
        out_value = out_value.toLowerCase();
    }

    if (in_value != out_value) {
        $(element).val(out_value);
    }
}

$(function(){
    $('table.developer .btn-group a').on('click', function(e){
        e.preventDefault();
        var show = $(this).attr('data-show');
        var el = $(this).closest('td').find('.'+show);

        if (el.is(':visible')) {
            $(this).closest('div').removeClass('on-row-active');
            $(this).closest('div').find('a').removeClass('active');
            $(this).closest('td').find('.toggles').hide();
            el.hide();
        }
        else {
            $(this).closest('div').addClass('on-row-active');
            $(this).closest('div').find('a').removeClass('active');
            $(this).addClass('active');
            $(this).closest('td').find('.toggles').hide();
            el.show();
        }
    });

    $(document).on('blur', 'input.identifier-type', function(e){
        correctIdentifierType(this);
    });
});

$(window).load(function(){
    prettyPrint();
});
