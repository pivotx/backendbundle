var site_host_cache = {};

function siteAddTarget(context_el, enabled, name, description)
{
    var no_of_targets = $('tr.target', context_el).length;
    var target_number = no_of_targets + 1;

    var inner_html = $('.target-template', context_el).html();
    var html = '<tr class="target target-' + target_number+ '">' + inner_html + '</tr>';

    $('tr.target-template', context_el).before(html);

    $('tr.target-'+target_number).attr('data-number', target_number);
    $('tr.target-'+target_number+' td.number input', context_el).attr('checked', enabled);
    $('tr.target-'+target_number+' td.number span', context_el).html('#' + target_number);
    $('tr.target-'+target_number+' td.number input', context_el).attr('name', 'target_number_' + target_number);
    $('tr.target-'+target_number+' td.name input', context_el).val(name);
    $('tr.target-'+target_number+' td.name input', context_el).attr('name', 'target_name_' + target_number);
    $('tr.target-'+target_number+' td.description input', context_el).val(description);
    $('tr.target-'+target_number+' td.description input', context_el).attr('name', 'target_description_' + target_number);

    $('tr.target-'+target_number+' td.name input', context_el).focus();

}

function siteAddLanguage(context_el, enabled, name, locale, description)
{
    var no_of_languages = $('tr.language', context_el).length;
    var language_number = no_of_languages + 1;

    var inner_html = $('.language-template', context_el).html();
    var html = '<tr class="language language-' + language_number+ '">' + inner_html + '</tr>';

    $('tr.language-template', context_el).before(html);

    $('tr.language-'+language_number).attr('data-number', language_number);
    $('tr.language-'+language_number+' td.number input', context_el).attr('checked', enabled);
    $('tr.language-'+language_number+' td.number span', context_el).html('#' + language_number);
    $('tr.language-'+language_number+' td.number input', context_el).attr('name', 'language_number_' + language_number);
    $('tr.language-'+language_number+' td.name input', context_el).val(name);
    $('tr.language-'+language_number+' td.name input', context_el).attr('name', 'language_name_' + language_number);
    $('tr.language-'+language_number+' td input.locale', context_el).val(locale);
    $('tr.language-'+language_number+' td input.locale', context_el).attr('name', 'language_locale_' + language_number);
    $('tr.language-'+language_number+' td input.description', context_el).val(description);
    $('tr.language-'+language_number+' td input.description', context_el).attr('name', 'language_description_' + language_number);

    $('tr.language-'+language_number+' td.name input', context_el).focus();
}

function siteUpdateContinueButton(context_el)
{
    var have_domain = false;
    var have_target = false;
    var have_language = false;

    if ($('input.host', context_el).val() != '') {
        have_domain = true;
    }

    var no_of_targets = $('tr.target input:checked', context_el).length;
    var no_of_languages = $('tr.language input:checked', context_el).length;

    have_target = no_of_targets > 0;
    have_language = no_of_languages > 0;

    if (have_domain && have_target && have_language) {
        $('fieldset.basic tr.buttons button', context_el).removeClass('disabled btn-alert').addClass('btn-success');
    }
    else {
        $('fieldset.hosts').hide();
        if (!$('fieldset.basic tr.buttons button', context_el).hasClass('disabled')) {
            $('fieldset.basic tr.buttons button', context_el).addClass('disabled btn-alert').removeClass('btn-success');
        }
    }
}

function siteUpdateHosts(context_el)
{
    var html = '';
    var targets = [];
    var languages = [];

    $('fieldset.hosts').show();

    $('tr.target input:checked', context_el).each(function(){
        var tr_el = $(this).closest('tr');
        targets.push($('td.name input', tr_el).val());
    });
    $('tr.language input:checked', context_el).each(function(){
        var tr_el = $(this).closest('tr');
        languages.push($('td.name input', tr_el).val());
    });

    html += '<tr>';
    html += '<td class="span2"></td>';
    for(var idxt in targets) {
        var target = targets[idxt];
        html += '<td class="span2">' + target + '</td>';
    }
    html += '</tr>';

    for(var idxl in languages) {
        var language = languages[idxl];
        var is_primary_language = (idxl == 0);
        html += '<tr>';
        html += '<td class="span2">' + language + '</td>';
        for(var idxt in targets) {
            var target = targets[idxt];
            var name = 'hosts_' + language + '_' + target;
            var value = site_host_cache[name];

            if ((typeof value === 'undefined') || (value == '')) {
                var domain = $('input[name="domain"]', context_el).val().trim();
                if (domain == 'any') {
                    domain = '%request.host%';
                }


                value = 'http://' + domain;
                if (!is_primary_language) {
                    value += '/' + language;
                }
                if (target == 'desktop') {
                    // nothing
                }
                else if (target == 'mobile') {
                    value += '/m';
                }
                else {
                    value += '/' + target;
                }
                value += '/';

                site_host_cache[name] = value;
            }

            html += '<td class="span2">';
            html += '<textarea class="span12 hosts" name="' + name + '">' + value + '</textarea>';
            html += '</td>';
        }
        html += '</tr>';
    }

    html += '<tr class="buttons">';
    html += '<td></td>';
    html += '<td colspan="2"><button class="btn btn-success save" type="button">Save</button></td>';
    html += '</tr>';

    $('fieldset.hosts tbody').html(html);
}

function siteBuildArray(context_el)
{
    var setup = {
        site: '',
        domain: '',
        bundle: '',
        theme: '',
        targets: [],
        languages: [],
        hosts: {}
    };

    setup.site   = $('input[name="site"]', context_el).val().trim();
    setup.domain = $('input[name="domain"]', context_el).val().trim();
    setup.bundle = $('select[name="bundle"]', context_el).val().trim();
    setup.theme  = $('input[name="theme"]', context_el).val().trim();

    $('tr.target input:checked', context_el).each(function(){
        var tr_el = $(this).closest('tr');
        var target = {};
        target.name = $('td.name input', tr_el).val().trim();
        target.description = $('td.description input', tr_el).val().trim();
        setup.targets.push(target);
    });
    $('tr.language input:checked', context_el).each(function(){
        var tr_el = $(this).closest('tr');
        var language = {};
        language.name = $('td.name input', tr_el).val().trim();
        language.locale = $('input.locale', tr_el).val().trim();
        language.description = $('input.description', tr_el).val().trim();
        setup.languages.push(language);
    });

    for(var idxt in setup.targets) {
        var target = setup.targets[idxt].name;

        setup.hosts[target] = {};

        for(var idxl in setup.languages) {
            var language = setup.languages[idxl].name;

            var name = 'hosts_' + language + '_' + target;
            var value = site_host_cache[name];

            setup.hosts[target][language] = value;
        }
    }

    return setup;
}

function siteUpdateSetup(context_el, setup)
{
    var action = $(context_el).attr('action');

    var json_setup = JSON.stringify(setup);

    $.ajax({
        url: action,
        type: 'POST',
        data: { 'setup': json_setup },
        success: handleAjaxResponse
    });
}

function siteLoadSetup(context_el, setup)
{
    $('input[name="site"]').val(setup.site);
    $('input[name="domain"]').val(setup.domain);

    var sel_el = $('select[name="bundle"]');
    $(sel_el).val(setup.bundle);

    if ($(sel_el).val() != setup.bundle) {
        $(sel_el).closest('td').find('.bundle-is-missing').show();

        $(sel_el).append('<option value="' + setup.bundle + '">Previous value: ' + setup.bundle + '</option>');
        $(sel_el).val(setup.bundle);
    }
    else {
        $(sel_el).closest('td').find('.bundle-is-missing').hide();
    }

    for(var tidx in setup.targets) {
        var target = setup.targets[tidx];
        siteAddTarget(context_el, true, target.name, target.description);
        $('a.add-target[data-name="'+target.name+'"]', context_el).closest('span').hide();
    }
    for(var lidx in setup.languages) {
        var language = setup.languages[lidx];
        siteAddLanguage(context_el, true, language.name, language.locale, language.description);
        $('a.add-language[data-name="'+language.name+'"]', context_el).closest('span').hide();
    }
    for(var target in setup.hosts) {
        for(var language in setup.hosts[target]) {
            var name = 'hosts_' + language + '_' + target;
            var value = setup.hosts[target][language];

            site_host_cache[name] = value;
        }
    }

    siteUpdateContinueButton(context_el);
    siteUpdateHosts(context_el);
}

$(function(){
    $('form.site-form').each(function(){
        var form_el = this;

        $('a.add-target', form_el).on('click', function(e){
            e.preventDefault();

            var all = $(this).closest('span');
            var name = $(this).attr('data-name');
            var description = $(this).attr('data-description');

            siteAddTarget(form_el, (name != ''), name, description);

            if (name != '') {
                all.hide();
            }
        });
        $('a.add-language', form_el).on('click', function(e){
            e.preventDefault();

            var all = $(this).closest('span');
            var name = $(this).attr('data-name');
            var locale = $(this).attr('data-locale');
            var description = $(this).attr('data-description');

            siteAddLanguage(form_el, (name != ''), name, locale, description);

            if (name != '') {
                all.hide();

                siteUpdateContinueButton(form_el);
            }
        });

        $('fieldset.basic', form_el).on('change keyup', 'input[type="text"]', function(e){
            var tr_el = $(this).closest('tr');
            var number = tr_el.attr('data-number');
            $('td.number input', tr_el).attr('checked', true);
        });

        $('fieldset.basic', form_el).on('change', 'input', function(e){
            siteUpdateContinueButton(form_el);
        });

        $('fieldset.basic tr.buttons button.continue', form_el).on('click', function(e){
            e.preventDefault();

            if ($(this).hasClass('btn-success')) {
                siteUpdateHosts(form_el);
            }
        });

        $('fieldset.hosts', form_el).on('change keyup', 'textarea', function(e){
            var name = $(this).attr('name');
            var value = $(this).val();

            site_host_cache[name] = value;
        });

        $('fieldset.hosts').on('click', 'button.save', function(e){
            e.preventDefault();

            var setup = siteBuildArray(form_el);

            siteUpdateSetup(form_el, setup);
        });
    });
});
