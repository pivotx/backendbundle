
$(function(){
    $('a.pivotx-script').on('click', function(e){
        var context_element = $(this);

        e.preventDefault();

        $.ajax({
            type: 'POST',
            url: $(context_element).attr('href'),
            dataType: 'script',
            success: function(data, textStatus, jqXHR){
                // do nothing
            },
            error: function(data, textStatus, jqXHR){
                // should do something
            }
        });
    });
});

