function correctInputType(element, kind)
{
    var in_value = $(element).val();
    var allow_uppercase = false;
    var out_value = in_value;

    if ($(element).data('identifier-allow') == 'uppercase') {
        allow_uppercase = true;
    }

    out_value = out_value.trim();

    switch (kind) {
        case 'variable':
            out_value = out_value.replace(/[^a-zA-Z0-9_]+/g, '_');
            break;

        case 'identifier':
            out_value = out_value.replace(/[^a-zA-Z0-9_-]+/g, '-');
            break;

        case 'uri':
            out_value = out_value.replace(/[^a-zA-Z0-9_-]+/g, '-');
            break;

        case 'host':
            out_value = out_value.replace(/[^a-zA-Z0-9_.-]+/g, '-');
            break;

        default:
            out_value = out_value.replace(/[^a-zA-Z0-9_]+/g, '_');
            break;
    }

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

    $(document).on('blur', 'input.correct-type', function(e){
        var type = $(this).attr('data-type');

        correctInputType(this, type);
    });
});

$(window).load(function(){
    prettyPrint();
});
