jQuery(document).ready(function ($) {
   // Backup creation with progress indication
   $('input[name="abr_create_backup"]').on("click", function (e) {
      var $button = $(this);
      var originalText = $button.val();

      // Disable button and show progress
      $button.prop("disabled", true).val(abr_ajax.strings.backup_in_progress);

      // Show progress indicator if it exists
      $(".abr-progress").show();

      // Re-enable button after form submission
      setTimeout(function () {
         if ($button.prop("disabled")) {
            $button.prop("disabled", false).val(originalText);
            $(".abr-progress").hide();
         }
      }, 30000); // 30 seconds timeout
   });

   // Confirm restore action
   $(".abr-restore-btn").on("click", function (e) {
      if (!confirm(abr_ajax.strings.confirm_restore)) {
         e.preventDefault();
         return false;
      }
   });

   // Confirm delete action
   $(".button-link-delete").on("click", function (e) {
      if (!confirm(abr_ajax.strings.confirm_delete)) {
         e.preventDefault();
         return false;
      }
   });

   // Auto-hide notices after 5 seconds
   setTimeout(function () {
      $(".notice.is-dismissible").fadeOut();
   }, 5000);

   // Settings form validation
   $("form").on("submit", function (e) {
      var backupTypes = $('input[name="backup_types[]"]:checked');
      if (backupTypes.length === 0) {
         alert("Please select at least one backup type.");
         e.preventDefault();
         return false;
      }

      var maxBackups = parseInt($('input[name="max_backups"]').val());
      if (maxBackups < 1 || maxBackups > 50) {
         alert("Maximum backups must be between 1 and 50.");
         e.preventDefault();
         return false;
      }
   });

   // Toggle notification email field based on checkbox
   $('input[name="email_notifications"]')
      .on("change", function () {
         var $emailField = $('input[name="notification_email"]').closest("tr");
         if ($(this).is(":checked")) {
            $emailField.show();
         } else {
            $emailField.hide();
         }
      })
      .trigger("change");

   $("#abr-backup-now-form").on("submit", function (e) {
      e.preventDefault();
      $("#abr-create-backup-btn").prop("disabled", true);
      $("#abr-backup-loader").show();

      var ajaxRequest = $.post(
         ajaxurl,
         {
            action: "abr_create_backup_now",
            abr_nonce: $('#abr-backup-now-form input[name="abr_nonce"]').val(),
         },
         function (response) {
            $("#abr-create-backup-btn").prop("disabled", false);
            $("#abr-backup-loader").hide();
            if (response.success) {
               location.reload();
            } else {
               alert(response.data || "Backup failed.");
            }
         }
      );

      // Add a timeout in case the request hangs
      setTimeout(function () {
         if (ajaxRequest.readyState !== 4) {
            ajaxRequest.abort();
            $("#abr-create-backup-btn").prop("disabled", false);
            $("#abr-backup-loader").hide();
            alert("Backup timed out. Please try again or check your server logs.");
         }
      }, 120000); // 2 minutes
   });
});
