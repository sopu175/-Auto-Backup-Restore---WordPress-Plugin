/* Auto Backup & Restore Pro - Modern JavaScript by Saif Islam */

jQuery(document).ready(function ($) {
   var backupInProgress = false;
   var progressInterval;

   // Create custom modal HTML
   function createModal() {
      if (!$("#abr-modal").length) {
         var modalHTML = `
            <div id="abr-modal" class="abr-modal">
               <div class="abr-modal-content">
                  <div class="abr-modal-header" id="abr-modal-header">
                     <span id="abr-modal-title">Confirmation</span>
                  </div>
                  <div class="abr-modal-body" id="abr-modal-body">
                     <p id="abr-modal-message">Are you sure?</p>
                  </div>
                  <div class="abr-modal-footer">
                     <button type="button" class="button button-secondary" id="abr-modal-cancel">Cancel</button>
                     <button type="button" class="button button-primary" id="abr-modal-confirm">Confirm</button>
                  </div>
               </div>
            </div>
         `;
         $("body").append(modalHTML);

         // Close modal on outside click
         $("#abr-modal").on("click", function (e) {
            if (e.target === this) {
               closeModal();
            }
         });

         // Close modal on cancel
         $("#abr-modal-cancel").on("click", closeModal);
      }
   }

   function showModal(title, message, type, onConfirm) {
      createModal();

      $("#abr-modal-title").text(title);
      $("#abr-modal-message").text(message);
      $("#abr-modal-header")
          .removeClass("warning success error")
          .addClass(type || "warning");

      $("#abr-modal-confirm")
          .off("click")
          .on("click", function () {
             closeModal();
             if (onConfirm) onConfirm();
          });

      $("#abr-modal").fadeIn(300);
   }

   function closeModal() {
      $("#abr-modal").fadeOut(300);
   }

   function showNotification(title, message, type) {
      createModal();

      $("#abr-modal-title").text(title);
      $("#abr-modal-message").text(message);
      $("#abr-modal-header")
          .removeClass("warning success error")
          .addClass(type || "success");
      $("#abr-modal-confirm").text("OK").off("click").on("click", closeModal);
      $("#abr-modal-cancel").hide();

      $("#abr-modal").fadeIn(300);

      // Auto close after 3 seconds for success messages
      if (type === "success") {
         setTimeout(closeModal, 3000);
      }
   }

   // AJAX backup creation with real progress tracking
   $("#abr-create-backup-btn").on("click", function (e) {
      e.preventDefault();

      if (backupInProgress) {
         showNotification(
             "⚠️ Backup In Progress",
             "A backup is already in progress. Please wait for it to complete.",
             "warning",
         );
         return false;
      }

      // Basic validation before starting
      if (
          !confirm(
              "Are you sure you want to create a backup? This may take several minutes depending on your site size.",
          )
      ) {
         return false;
      }

      var $button = $(this);
      var originalText = $button.text();

      // Start backup process
      startBackup($button, originalText);
   });

   function startBackup($button, originalText) {
      backupInProgress = true;

      // Update button state
      $button.prop("disabled", true).text("⏳ Creating Backup...");

      // Show and reset progress bar
      $("#abr-progress").show().removeClass("success error processing");
      updateProgress(0, "Initializing backup...");

      // Start progress polling
      progressInterval = setInterval(pollProgress, 1000);

      // Make AJAX request to start backup
      $.post(abr_ajax.ajax_url, {
         action: "abr_create_backup",
         nonce: abr_ajax.nonce,
      })
          .done(function (response) {
             console.log("Backup response:", response);
             handleBackupComplete(response, $button, originalText);
          })
          .fail(function (xhr, status, error) {
             console.log("Backup failed:", xhr.responseText, status, error);
             handleBackupError(
                 "AJAX Error: " + status + " - " + error,
                 $button,
                 originalText,
             );
          });
   }

   function pollProgress() {
      $.post(abr_ajax.ajax_url, {
         action: "abr_backup_progress",
         nonce: abr_ajax.nonce,
      })
          .done(function (response) {
             try {
                var result =
                    typeof response === "string" ? JSON.parse(response) : response;
                if (result.success && result.data) {
                   var progress = result.data;
                   if (progress.percent !== undefined) {
                      updateProgress(progress.percent, progress.message);
                   }
                }
             } catch (e) {
                console.log("Progress polling error:", e, response);
             }
          })
          .fail(function (xhr, status, error) {
             console.log("Progress polling failed:", error);
          });
   }

   function updateProgress(percent, message) {
      $("#abr-progress-fill").css("width", percent + "%");
      $("#abr-progress-percent").text(Math.round(percent) + "%");
      $("#abr-progress-message").text(message);

      // Add processing class for animation
      if (percent > 0 && percent < 100) {
         $("#abr-progress").addClass("processing").removeClass("success error");
      }
   }

   function handleBackupComplete(response, $button, originalText) {
      clearInterval(progressInterval);
      backupInProgress = false;

      try {
         var result =
             typeof response === "string" ? JSON.parse(response) : response;

         if (result.success === true) {
            // Ensure progress shows 100%
            updateProgress(100, "Backup completed successfully!");
            $("#abr-progress").addClass("success");

            var message =
                result.data && result.data.message
                    ? result.data.message
                    : "Backup completed successfully!";

            // Add file size info if available
            if (result.data && result.data.size) {
               message += " (" + result.data.size + ")";
            }

            showNotification("✅ Backup Successful", message, "success");

            // Immediately refresh data to show new backup
            refreshBackupData();

            // Reset button after showing success
            setTimeout(function () {
               resetButton($button, originalText);
            }, 2000);
         } else {
            updateProgress(0, "Backup failed");
            $("#abr-progress").addClass("error");
            var errorMessage = result.data || result.message || "Backup failed";
            showNotification("❌ Backup Failed", errorMessage, "error");
            resetButton($button, originalText);
         }
      } catch (e) {
         console.log("Response parsing error:", e, response);
         handleBackupError(
             "Invalid response: " + e.message,
             $button,
             originalText,
         );
      }
   }

   function handleBackupError(error, $button, originalText) {
      clearInterval(progressInterval);
      backupInProgress = false;

      updateProgress(0, "Backup failed");
      $("#abr-progress").addClass("error");

      // More specific error messages
      var errorMessage = "Backup failed";
      if (error.includes("Security check failed")) {
         errorMessage =
             "Security check failed. Please refresh the page and try again.";
      } else if (error.includes("disk space")) {
         errorMessage =
             "Insufficient disk space. Please free up space and try again.";
      } else if (error.includes("permission")) {
         errorMessage =
             "File permission error. Please contact your hosting provider.";
      } else if (error) {
         errorMessage = "Backup failed: " + error;
      }

      showNotification("❌ Backup Failed", errorMessage, "error");
      resetButton($button, originalText);
   }

   function resetButton($button, originalText) {
      $button.prop("disabled", false).text("🚀 Create Backup Now");
      $("#abr-progress").fadeOut(500);
   }

   // AJAX restore functionality with custom modal
   $(document).on("click", ".abr-restore-btn", function (e) {
      e.preventDefault();

      var $button = $(this);
      var backupFile = $button.attr("data-backup-file");

      if (!backupFile) {
         showNotification(
             "❌ Error",
             "Could not determine backup file name",
             "error",
         );
         return false;
      }

      showModal(
          "🔄 Restore Backup",
          "Are you sure you want to restore this backup? This will overwrite current files and database. This action cannot be undone.",
          "warning",
          function () {
             startRestore($button, backupFile);
          },
      );
   });

   function startRestore($button, backupFile) {
      var originalText = $button.text();
      var restoreInProgress = true;

      // Update button state
      $button.prop("disabled", true).text("🔄 Restoring...");

      // Show and reset progress bar
      $("#abr-progress").show().removeClass("success error processing");
      updateProgress(0, "Starting restore...");

      // Start progress polling for restore
      var restoreProgressInterval = setInterval(function () {
         pollRestoreProgress();
      }, 1000);

      // Make AJAX request to start restore
      $.post(abr_ajax.ajax_url, {
         action: "abr_restore_backup",
         nonce: abr_ajax.nonce,
         backup_file: backupFile,
      })
          .done(function (response) {
             console.log("Restore response:", response);
             handleRestoreComplete(
                 response,
                 $button,
                 originalText,
                 restoreProgressInterval,
             );
          })
          .fail(function (xhr, status, error) {
             console.log("Restore failed:", xhr.responseText, status, error);
             handleRestoreError(
                 "AJAX Error: " + status + " - " + error,
                 $button,
                 originalText,
                 restoreProgressInterval,
             );
          });
   }

   function pollRestoreProgress() {
      $.post(abr_ajax.ajax_url, {
         action: "abr_restore_progress",
         nonce: abr_ajax.nonce,
      })
          .done(function (response) {
             try {
                var result =
                    typeof response === "string" ? JSON.parse(response) : response;
                if (result.success && result.data) {
                   var progress = result.data;
                   if (progress.percent !== undefined) {
                      updateProgress(progress.percent, progress.message);
                   }
                }
             } catch (e) {
                console.log("Restore progress polling error:", e, response);
             }
          })
          .fail(function (xhr, status, error) {
             console.log("Restore progress polling failed:", error);
          });
   }

   function handleRestoreComplete(
       response,
       $button,
       originalText,
       progressInterval,
   ) {
      clearInterval(progressInterval);

      try {
         var result =
             typeof response === "string" ? JSON.parse(response) : response;

         if (result.success === true) {
            updateProgress(100, "Restore completed successfully!");
            $("#abr-progress").addClass("success");
            var message =
                result.data && result.data.message
                    ? result.data.message
                    : "Restore completed successfully!";
            showNotification("✅ Restore Successful", message, "success");

            // Immediately refresh data
            refreshBackupData();

            // Reset button and hide progress
            setTimeout(function () {
               resetRestoreButton($button, originalText);
               $("#abr-progress").hide();
            }, 2000);
         } else {
            updateProgress(0, "Restore failed");
            $("#abr-progress").addClass("error");
            var errorMessage = result.data || result.message || "Restore failed";
            showNotification("❌ Restore Failed", errorMessage, "error");
            resetRestoreButton($button, originalText);
         }
      } catch (e) {
         console.log("Restore response parsing error:", e, response);
         handleRestoreError(
             "Invalid response: " + e.message,
             $button,
             originalText,
             progressInterval,
         );
      }
   }

   function handleRestoreError(error, $button, originalText, progressInterval) {
      clearInterval(progressInterval);

      updateProgress(0, "Restore failed");
      showNotification("❌ Restore Failed", "Restore failed: " + error, "error");
      resetRestoreButton($button, originalText);
   }

   function resetRestoreButton($button, originalText) {
      $button.prop("disabled", false).html("↻ Restore");
      $("#abr-progress").fadeOut(500);
   }

   // Initial attachment of event handlers
   attachEventHandlers();

   // Settings form validation
   $("form").on("submit", function (e) {
      var backupTypes = $('input[name="backup_types[]"]:checked');
      if (
          backupTypes.length === 0 &&
          $(this).find('input[name="backup_types[]"]').length > 0
      ) {
         showNotification(
             "❌ Validation Error",
             "Please select at least one backup type.",
             "error",
         );
         e.preventDefault();
         return false;
      }

      var maxBackupsField = $('input[name="max_backups"]');
      if (maxBackupsField.length > 0) {
         var maxBackups = parseInt(maxBackupsField.val());
         if (maxBackups < 1 || maxBackups > 50) {
            showNotification(
                "❌ Validation Error",
                "Maximum backups must be between 1 and 50.",
                "error",
            );
            e.preventDefault();
            return false;
         }
      }
   });

   // Toggle notification email fields based on checkbox
   $('input[name="email_notifications"]')
       .on("change", function () {
          var $emailFields = $(".email-settings-row");
          if ($(this).is(":checked")) {
             $emailFields.show();
          } else {
             $emailFields.hide();
          }
       })
       .trigger("change");

   // Refresh backup data function
   function refreshBackupData() {
      // Add loading state to statistics
      $(".abr-stat-number").addClass("loading");

      $.post(abr_ajax.ajax_url, {
         action: "abr_refresh_data",
         nonce: abr_ajax.nonce,
      })
          .done(function (response) {
             try {
                var result =
                    typeof response === "string" ? JSON.parse(response) : response;

                if (result.success === true && result.data) {
                   // Update statistics with animation
                   $(".abr-stat-box")
                       .eq(0)
                       .addClass("updated")
                       .find(".abr-stat-number")
                       .removeClass("loading")
                       .fadeOut(200, function () {
                          $(this).text(result.data.backup_count).fadeIn(200);
                       });
                   $(".abr-stat-box")
                       .eq(1)
                       .addClass("updated")
                       .find(".abr-stat-number")
                       .removeClass("loading")
                       .fadeOut(200, function () {
                          $(this).text(result.data.total_size).fadeIn(200);
                       });

                   // Remove animation class after animation completes
                   setTimeout(function () {
                      $(".abr-stat-box").removeClass("updated");
                   }, 600);

                   // Update backup list with fade effect
                   $(".wp-list-table tbody").fadeOut(200, function () {
                      $(this).html(result.data.backup_list_html).fadeIn(200);

                      // Reattach event handlers for new elements
                      attachEventHandlers();
                   });

                   console.log("Backup data refreshed successfully");
                }
             } catch (e) {
                console.log("Failed to refresh backup data:", e);
                $(".abr-stat-number").removeClass("loading");
             }
          })
          .fail(function (xhr, status, error) {
             console.log("Refresh backup data failed:", error);
             $(".abr-stat-number").removeClass("loading");
          });
   }

   // Function to attach event handlers to dynamically created elements
   function attachEventHandlers() {
      // Remove existing handlers to prevent duplicates
      $(".abr-restore-btn").off("click");
      $(".abr-delete-btn").off("click");

      // Reattach restore handlers
      $(".abr-restore-btn").on("click", function (e) {
         e.preventDefault();

         var $button = $(this);
         var backupFile = $button.attr("data-backup-file");

         if (!backupFile) {
            showNotification(
                "❌ Error",
                "Could not determine backup file name",
                "error",
            );
            return false;
         }

         showModal(
             "🔄 Restore Backup",
             "Are you sure you want to restore this backup? This will overwrite current files and database. This action cannot be undone.",
             "warning",
             function () {
                startRestore($button, backupFile);
             },
         );
      });

      // Reattach delete handlers
      $(".abr-delete-btn").on("click", function (e) {
         e.preventDefault();

         var $button = $(this);
         var backupFile = $button.attr("data-backup-file");
         var originalText = $button.text();

         if (!backupFile) {
            showNotification(
                "❌ Error",
                "Could not determine backup file name",
                "error",
            );
            return false;
         }

         showModal(
             "🗑️ Delete Backup",
             "Are you sure you want to delete this backup? This action cannot be undone.",
             "warning",
             function () {
                // Update button state
                $button.prop("disabled", true).text("🗑️ Deleting...");

                $.post(abr_ajax.ajax_url, {
                   action: "abr_delete_backup",
                   nonce: abr_ajax.nonce,
                   backup_file: backupFile,
                })
                    .done(function (response) {
                       var result =
                           typeof response === "string" ? JSON.parse(response) : response;

                       if (result.success === true) {
                          showNotification(
                              "✅ Delete Successful",
                              result.data.message,
                              "success",
                          );
                          refreshBackupData();
                       } else {
                          showNotification(
                              "❌ Delete Failed",
                              result.data || "Failed to delete backup",
                              "error",
                          );
                          $button.prop("disabled", false).text(originalText);
                       }
                    })
                    .fail(function (xhr, status, error) {
                       showNotification(
                           "❌ Delete Failed",
                           "Delete failed: " + error,
                           "error",
                       );
                       $button.prop("disabled", false).text(originalText);
                    });
             },
         );
      });
   }

   // Manual refresh button functionality
   $(document).on("click", ".abr-refresh-btn", function (e) {
      e.preventDefault();
      var $button = $(this);
      var $icon = $button.find(".dashicons-update");
      var originalText = $button.find("text").text() || "Refresh";

      // Add loading state
      $button.prop("disabled", true);
      $icon.addClass("spinning");

      refreshBackupData();

      // Reset button state after refresh
      setTimeout(function () {
         $button.prop("disabled", false);
         $icon.removeClass("spinning");
      }, 1000);
   });

   // Email test functionality
   $(document).on("click", "#abr-test-email-btn", function (e) {
      e.preventDefault();

      var $button = $(this);
      var originalText = $button.text();
      var $result = $("#abr-email-test-result");

      // Check if email is configured
      var emailField = $('input[name="notification_email"]').val();
      if (!emailField || !emailField.includes("@")) {
         $result.html(
             '<div style="color: #dc3232; font-weight: bold;">❌ Please enter a valid email address first.</div>',
         );
         return;
      }

      // Update button state
      $button.prop("disabled", true).text("📧 Sending...");
      $result.html('<div style="color: #0073aa;">📤 Sending test email...</div>');

      $.post(abr_ajax.ajax_url, {
         action: "abr_test_email",
         nonce: abr_ajax.nonce,
      })
          .done(function (response) {
             var result =
                 typeof response === "string" ? JSON.parse(response) : response;

             if (result.success === true) {
                $result.html(
                    '<div style="color: #00a32a; font-weight: bold;">✅ ' +
                    result.data.message +
                    "</div>",
                );
             } else {
                $result.html(
                    '<div style="color: #dc3232; font-weight: bold;">❌ ' +
                    (result.data || "Email test failed") +
                    "</div>",
                );
             }
          })
          .fail(function (xhr, status, error) {
             $result.html(
                 '<div style="color: #dc3232; font-weight: bold;">❌ Error: ' +
                 error +
                 "</div>",
             );
          })
          .always(function () {
             // Reset button
             $button.prop("disabled", false).text(originalText);
          });
   });

   // Auto-hide WordPress notices after 5 seconds
   setTimeout(function () {
      $(".notice.is-dismissible").fadeOut();
   }, 5000);

   // Show brand footer
   if ($(".wrap").length && !$(".abr-brand-footer").length) {
      $(".wrap").append('<div class="abr-brand-footer"></div>');
   }
});
