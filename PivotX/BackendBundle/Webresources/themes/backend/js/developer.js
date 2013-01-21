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
        case 'tablename':
        case 'variable':
            out_value = out_value.replace(/[^a-zA-Z0-9_]+/g, '_');
            break;

        case 'identifier':
            out_value = out_value.replace(/[^a-zA-Z0-9_-]+/g, '-');
            break;

        case 'slug':
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

    $(document).on('click', 'a.snippets-button', function(e){
        e.preventDefault();

        var snippets_el = this;
        var id = $(this).attr('href');
        var title = $(this).attr('data-modal-title');
        var text = $(id).html();
        text = text.replace(/data-become-/g, '');
        var dm_el = modalAll(title, text);
        dm_el.addClass('modal-snippets');
        var btn_el = $('a[data-modal-kind="ok"]', dm_el);
        btn_el.show().on('click', function(e){
            e.preventDefault();
            dm_el.modal('hide');
            dm_el.removeClass('modal-snippets');
        });
        // @todo below doesn't work
        dm_el.on('hide', function(){
            $(snippets_el).removeClass('active').closest('div').removeClass('on-row-active');
        });

        $(dm_el).modal();
    });
});
