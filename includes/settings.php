<?php
if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Display the settings page content
function abr_settings_backup_page_content()
{
    // Fetch current backup settings from the options table
    $backups = get_option('abr_backup_options', []);

    // Ensure the backups are always an array
    if (!is_array($backups)) {
        $backups = [];
    }

    ?>
    <div class="wrap">
        <h1>Backup Settings</h1>

        <!-- Add New Backup Setting -->
        <h2>Add New Backup Setting</h2>
        <form method="post" action="">
            <?php wp_nonce_field('abr_add_backup', 'abr_backup_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="backup_name">Backup Name</label></th>
                    <td><input type="text" name="backup_name" id="backup_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="backup_interval">Backup Interval</label></th>
                    <td>
                        <select name="backup_interval" id="backup_interval" required>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button('Add Backup'); ?>
        </form>

        <!-- Display Existing Backup Settings -->
        <h2>Current Backup Settings</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th>Name</th>
                <th>Interval</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($backups)) : ?>
                <?php foreach ($backups as $id => $backup) : ?>
                    <tr>
                        <td><?php echo esc_html($backup['name']); ?></td>
                        <td><?php echo esc_html(ucfirst($backup['interval'])); ?></td>
                        <td><?php echo $backup['enabled'] ? 'Enabled' : 'Disabled'; ?></td>
                        <td>
                            <!-- Enable/Disable Link -->
                            <a href="<?php echo esc_url(add_query_arg([
                                'action' => 'toggle_backup',
                                'backup_id' => $id,
                                '_wpnonce' => wp_create_nonce('abr_toggle_backup')
                            ])); ?>">
                                <?php echo $backup['enabled'] ? 'Disable' : 'Enable'; ?>
                            </a> |

                            <!-- Delete Link -->
                            <a href="<?php echo esc_url(add_query_arg([
                                'action' => 'delete_backup',
                                'backup_id' => $id,
                                '_wpnonce' => wp_create_nonce('abr_delete_backup')
                            ])); ?>" class="delete">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4">No backup settings found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}



// Handle form submissions and actions
add_action('admin_init', function () {
    // Handle adding a new backup setting
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['abr_backup_nonce']) && wp_verify_nonce($_POST['abr_backup_nonce'], 'abr_add_backup')) {
        $backups = get_option('abr_backup_options', []);
        $id = uniqid('', true);

        $backups[$id] = [
            'name' => sanitize_text_field($_POST['backup_name']),
            'interval' => sanitize_text_field($_POST['backup_interval']),
            'enabled' => true,
        ];

        update_option('abr_backup_options', $backups);
        wp_redirect(admin_url('admin.php?page=abr_settings_backup'));
        exit;
    }

    // Handle enabling/disabling a backup
    if (isset($_GET['action'], $_GET['backup_id'], $_GET['_wpnonce']) && $_GET['action'] === 'toggle_backup' && wp_verify_nonce($_GET['_wpnonce'], 'abr_toggle_backup')) {
        $backups = get_option('abr_backup_options', []);
        $id = sanitize_text_field($_GET['backup_id']);

        if (isset($backups[$id])) {
            $backups[$id]['enabled'] = !$backups[$id]['enabled'];
            update_option('abr_backup_options', $backups);
        }

        wp_redirect(admin_url('admin.php?page=abr_settings_backup'));
        exit;
    }

    // Handle deleting a backup
    if (isset($_GET['action'], $_GET['backup_id'], $_GET['_wpnonce']) && $_GET['action'] === 'delete_backup' && wp_verify_nonce($_GET['_wpnonce'], 'abr_delete_backup')) {
        $backups = get_option('abr_backup_options', []);
        $id = sanitize_text_field($_GET['backup_id']);

        if (isset($backups[$id])) {
            unset($backups[$id]);
            update_option('abr_backup_options', $backups);
        }

        wp_redirect(admin_url('admin.php?page=abr_settings_backup'));
        exit;
    }
});
