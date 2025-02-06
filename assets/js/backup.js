jQuery(document).ready(function($) {
    $('#abr-backup-button').on('click', function(e) {
        e.preventDefault();

        $.ajax({
            url: abr_backup.ajax_url,
            type: 'POST',
            data: {
                action: 'abr_ajax_backup',
                security: abr_backup.nonce
            },
            success: function(response) {
                alert('Backup completed: ' + response);
            },
            error: function(response) {
                alert('Backup failed: ' + response);
            }
        });
    });
});