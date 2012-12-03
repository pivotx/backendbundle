

$(function(){
    $('button.add-entity-button').on('click', function(e){
        e.preventDefault();

        var title = 'Add entity';
        var text  = $('.add-entity').html();

        var dm_el = modalAll(title, text);

        var sav_el = $('a[data-modal-kind="save"]', dm_el);
        var can_el = $('a[data-modal-kind="cancel"]', dm_el);
        sav_el.on('click', function(e){
            e.preventDefault();

            sav_el.text('Saving');

            var form_el = $('#default-modal form.add-entity-form');

            form_el.submit();
        });

        sav_el.show();
        can_el.show();

        $(dm_el).modal();
    });

    $('table.siteadmin a.siteadmin-edit').on('click', function(e){
        e.preventDefault();

        var row_el = $(e.target).closest('tr');

        var action = 'Editing';
        if ($(e.target).hasClass('siteadmin-add')) {
            action = 'Adding';
        }

        var field_entity = $(row_el).closest('table').data('entity');
        var field_name = $('.field-name', row_el).text();
        var field_type = $('.field-type', row_el).text();
        var field_arguments = $('.field-arguments', row_el).text();
        var field_relation = $('.field-relation', row_el).text();

        var title = action + ' field for entity "' + field_entity + '"';
        var text  = $('.edit-field').html();

        var dm_el = modalAll(title, text);

        $('input[name="entity"]', dm_el).val(field_entity);
        $('input[name="original_name"]', dm_el).val(field_name);
        $('input[name="name"]', dm_el).val(field_name);
        $('input[name="arguments"]', dm_el).val(field_arguments);
        $('select[name="relation"]', dm_el).val(field_relation);
        $('select[name="type"]', dm_el).val(field_type);

        var sav_el = $('a[data-modal-kind="save"]', dm_el);
        var can_el = $('a[data-modal-kind="cancel"]', dm_el);
        sav_el.on('click', function(e){
            e.preventDefault();

            sav_el.text('Saving');

            var form_el = $('#default-modal form.edit-field-form');

            form_el.submit();
        });

        sav_el.show();
        can_el.show();

        $(dm_el).modal();
    });

    $('table.siteadmin a.siteadmin-delete').on('click', function(e){
        e.preventDefault();

        var entity = $(e.target).closest('table').data('entity');
        var field = $(e.target).closest('tr').find('.field-name').text();

        var title = 'Are you sure?';
        var text = 'You are about to delete the field "' + field + '" from entity "' + entity + '".';

        var del_url = $(e.target).attr('href');
        var post = '';
        post += 'action=delete_field';
        post += '&entity=' + escape(entity);
        post += '&name=' + escape(field);

        modalConfirm(title, text,
            function(){
                $.ajax({
                    type: 'POST',
                    url: del_url,
                    data: post,
                    success: function(data, textStatus, jqXHR){
                        // @todo should not be like this
                        document.location = document.location;
                    }
                });
            },
            function(){
            }
        );
    });
});
