jQuery(document).ready(function() {

    let copyTable = function () {
        jQuery.ajax({
            url: ajax.ajax_url,
            data: { action: 'multisite_content_copier_copy_table' }
        }).done(function (data) {
            let results = jQuery('#results');
            if (data) {
                results.html(results.html() + '<p>' + data + '</p>');
                copyTable();
            } else {
                results.html(results.html() + '<p><strong>Content copying complete!</strong></p>');
                deleteFiles();
            }
        });
    };

    let deleteFiles = function () {
        jQuery.ajax({
            url: ajax.ajax_url,
            data: { action: 'multisite_content_copier_delete_files' }
        }).done(function (data) {
            let results = jQuery('#results');
            if (data) {
                results.html(results.html() + '<p>' + data + '</p>');
                deleteFiles();
            } else {
                results.html(results.html() + '<p><strong>Removal of old uploads complete!</strong></p>');
                copyFiles();
            }
        });
    };

    let copyFiles = function () {
        jQuery.ajax({
            url: ajax.ajax_url,
            data: { action: 'multisite_content_copier_copy_files' }
        }).done(function (data) {
            let results = jQuery('#results');
            if (data) {
                results.html(results.html() + '<p>' + data + '</p>');
                copyFiles();
            } else {
                results.html(results.html() + '<p><strong>Uploads copying complete!</strong></p>');
                jQuery('#spinner').hide();
            }
        });
    };

    copyTable();
});

