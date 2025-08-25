<?php
/*
Plugin Name: Simple AJAX Shoutbox
Description: Left-aligned minimal shoutbox. AJAX posting (logged-in users only). Admin list with edit/delete/clear and options. All strings in English.
Version: 1.0.0
Author: Teo
Text Domain: simple-ajax-shoutbox
*/

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
define( 'SAS_TABLE', $wpdb->prefix . 'sas_shoutbox' );
define( 'SAS_OPTIONS_KEY', 'sas_options' );

/* ---------------- Activation: create table ---------------- */
register_activation_hook( __FILE__, function() {
    global $wpdb;
    $table = SAS_TABLE;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) NOT NULL,
        user_name VARCHAR(180) NOT NULL,
        message TEXT NOT NULL,
        time DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // set defaults
    if ( ! get_option( SAS_OPTIONS_KEY ) ) {
        update_option( SAS_OPTIONS_KEY, array(
            'enabled' => 1,
            'max_messages' => 50,
            'poll_interval' => 10,
        ) );
    }
});

/* ---------------- Defaults / helper ---------------- */
function sas_defaults() {
    return array(
        'enabled' => 1,
        'max_messages' => 50,
        'poll_interval' => 10,
    );
}

function sas_get_options() {
    return wp_parse_args( (array) get_option( SAS_OPTIONS_KEY, array() ), sas_defaults() );
}

/* ---------------- Admin menu & settings ---------------- */
add_action( 'admin_menu', function() {
    add_options_page( 'Simple Shoutbox', 'Simple Shoutbox', 'manage_options', 'simple-ajax-shoutbox', 'sas_admin_page' );
});

add_action( 'admin_init', function() {
    register_setting( 'sas_options_group', SAS_OPTIONS_KEY );
});

function sas_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
    global $wpdb;

    $options = sas_get_options();

    // Handle delete single (GET)
    if ( isset( $_GET['delete'] ) ) {
        $id = intval( $_GET['delete'] );
        if ( check_admin_referer( "sas_delete_{$id}" ) ) {
            $wpdb->delete( SAS_TABLE, array( 'id' => $id ), array( '%d' ) );
            echo '<div class="updated"><p>Message deleted.</p></div>';
        }
    }

    // Handle clear all (POST)
    if ( isset( $_POST['sas_clear_all'] ) && check_admin_referer( 'sas_clear_all_action' ) ) {
        $wpdb->query( "TRUNCATE TABLE " . SAS_TABLE );
        echo '<div class="updated"><p>All messages cleared.</p></div>';
    }

    // Handle edit (POST)
    if ( isset( $_POST['sas_edit_id'] ) && check_admin_referer( 'sas_edit_action' ) ) {
        $id = intval( $_POST['sas_edit_id'] );
        $msg = sanitize_textarea_field( $_POST['sas_edit_message'] );
        if ( $id && $msg !== '' ) {
            $wpdb->update( SAS_TABLE, array( 'message' => $msg ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
            echo '<div class="updated"><p>Message updated.</p></div>';
        }
    }

    // Save options (POST)
    if ( isset( $_POST['sas_save_opts'] ) && check_admin_referer( 'sas_save_opts' ) ) {
        $new = array();
        $new['enabled'] = isset( $_POST['sas_options']['enabled'] ) ? 1 : 0;
        $new['max_messages'] = max(1, intval( $_POST['sas_options']['max_messages'] ) );
        $new['poll_interval'] = max(1, intval( $_POST['sas_options']['poll_interval'] ) );
        update_option( SAS_OPTIONS_KEY, $new );
        $options = sas_get_options();
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    // Pagination for admin list
    $paged = isset( $_GET['paged'] ) ? max(1,intval($_GET['paged'])) : 1;
    $per_page = 20;
    $offset = ($paged - 1) * $per_page;
    $total = $wpdb->get_var( "SELECT COUNT(*) FROM " . SAS_TABLE );
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . SAS_TABLE . " ORDER BY time DESC LIMIT %d OFFSET %d", $per_page, $offset ) );

    // If editing a message (GET edit)
    $edit_row = null;
    if ( isset( $_GET['edit'] ) ) {
        $eid = intval( $_GET['edit'] );
        $edit_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . SAS_TABLE . " WHERE id = %d", $eid ) );
    }

    ?>
    <div class="wrap">
        <h1>Simple Shoutbox — Settings & Messages</h1>

        <h2>Settings</h2>
        <form method="post">
            <?php wp_nonce_field( 'sas_save_opts' ); ?>
            <table class="form-table">
                <tr>
                    <th>Enable shoutbox</th>
                    <td><input type="checkbox" name="sas_options[enabled]" value="1" <?php checked(1, $options['enabled']); ?>></td>
                </tr>
                <tr>
                    <th>Max messages stored</th>
                    <td><input type="number" name="sas_options[max_messages]" value="<?php echo esc_attr($options['max_messages']); ?>" min="1"></td>
                </tr>
                <tr>
                    <th>Polling interval (seconds)</th>
                    <td><input type="number" name="sas_options[poll_interval]" value="<?php echo esc_attr($options['poll_interval']); ?>" min="1"></td>
                </tr>
            </table>
            <p><input type="submit" name="sas_save_opts" class="button button-primary" value="Save settings"></p>
        </form>

        <hr>

        <?php if ( $edit_row ): ?>
            <h2>Edit message #<?php echo intval($edit_row->id); ?></h2>
            <form method="post">
                <?php wp_nonce_field( 'sas_edit_action' ); ?>
                <input type="hidden" name="sas_edit_id" value="<?php echo intval($edit_row->id); ?>">
                <textarea name="sas_edit_message" rows="4" style="width:100%;"><?php echo esc_textarea( $edit_row->message ); ?></textarea>
                <p><?php submit_button( 'Save changes', 'primary', 'sas_edit_submit' ); ?></p>
            </form>
            <p><a href="<?php echo admin_url('options-general.php?page=simple-ajax-shoutbox'); ?>">&laquo; Back to list</a></p>

        <?php else: ?>

            <h2>Messages</h2>

            <form method="post" style="margin-bottom:10px;">
                <?php wp_nonce_field( 'sas_clear_all_action' ); ?>
                <input type="submit" name="sas_clear_all" class="button button-secondary" value="Clear All Messages" onclick="return confirm('Clear all messages?');">
            </form>

            <table class="widefat fixed striped">
                <thead><tr><th>ID</th><th>User</th><th>Message</th><th>Time</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ( $rows ): foreach ( $rows as $r ): ?>
                    <tr>
                        <td><?php echo intval($r->id); ?></td>
                        <td><?php echo esc_html($r->user_name); ?></td>
                        <td><?php echo esc_html($r->message); ?></td>
                        <td><?php echo esc_html($r->time); ?></td>
                        <td>
                            <a href="<?php echo wp_nonce_url( admin_url('options-general.php?page=simple-ajax-shoutbox&edit=' . $r->id), 'sas_edit_' . $r->id ); ?>">Edit</a>
                             |
                            <a href="<?php echo wp_nonce_url( admin_url('options-general.php?page=simple-ajax-shoutbox&delete=' . $r->id), 'sas_delete_' . $r->id ); ?>" onclick="return confirm('Delete this message?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5">No messages found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <?php
            $total_pages = ceil( $total / $per_page );
            if ( $total_pages > 1 ) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo paginate_links( array(
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $paged
                ) );
                echo '</div></div>';
            }
            ?>
        <?php endif; ?>
    </div>
    <?php
}

/* ---------------- Front-end: scripts & styles (inlined) ---------------- */
add_action( 'wp_enqueue_scripts', function() {
    // register style handle and add inline css
    wp_register_style( 'sas-style', false );
    wp_enqueue_style( 'sas-style' );
    $css = '
    /* Minimal left-aligned shoutbox (no boxes) */
    #sas-container { font-family: Arial, sans-serif; color: #fff; }
    #sas-container ul { list-style:none; padding-left:0; margin:0 0 0.6em 0; max-height:300px; overflow-y:auto; }
    #sas-container li { padding:4px 0; border-bottom:none; }
    #sas-container .sas-meta { font-size:12px; color:#666; margin-left:6px; }
    #sas-container form { margin-top:6px; display:flex; gap:8px; align-items:center; }
    #sas-container input[type="text"] { flex:1; color:#fff; padding:6px 8px; background:#333333; border:1px solid #191919; border-radius:3px; }
    #sas-container button { padding:6px 12px; background:#D31919; color:#fff; border:none; border-radius:3px; cursor:pointer; }
    #sas-container button:hover { background:#4FA510; }
    ';
    wp_add_inline_style( 'sas-style', $css );

    // js
    wp_register_script( 'sas-script', false, array( 'jquery' ), null, true );
    wp_enqueue_script( 'sas-script' );
    $opts = sas_get_options();
    $data = array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'simple_shoutbox_nonce' ),
        'poll' => intval( $opts['poll_interval'] ),
        'posts_to_show' => intval( $opts['max_messages'] ),
    );
    wp_add_inline_script( 'sas-script', '
        (function($){
            var SAS = ' . wp_json_encode( $data ) . ';

            function esc(s){ return $("<div/>").text(s).html(); }

            function renderMessages(listEl, data){
                var $list = $(listEl);
                $list.empty();
                if (!data || !data.length){
                    $list.append("<li>No messages yet.</li>");
                    return;
                }
                data.forEach(function(m){
                    var name = m.user_name || "User";
                    var text = m.message || "";
                    var time = m.time || "";
                    if (time.length >= 16) time = time.substr(11,5);
                    $list.append("<li><strong>" + esc(name) + ":</strong> " + esc(text) + " <span class=\'sas-meta\'>(" + esc(time) + ")</span></li>");
                });
                $list.scrollTop($list[0].scrollHeight);
            }

            function fetch(){
                $.post(SAS.ajax_url, { action: "sas_fetch", nonce: SAS.nonce }, function(resp){
                    if (resp && resp.success){
                        renderMessages("#sas-messages", resp.data);
                    }
                });
            }

            $(document).on("submit", "#sas-form", function(e){
                e.preventDefault();
                var $input = $("#sas_message");
                var val = $input.val();
                if (!val || !val.trim()) return;
                $.post(SAS.ajax_url, { action: "sas_post", nonce: SAS.nonce, message: val }, function(resp){
                    if (resp && resp.success){
                        $input.val("");
                        fetch();
                    } else {
                        var err = (resp && resp.data) ? resp.data : "Error posting message";
                        alert(err);
                    }
                });
            });

            // initial load and polling
            $(function(){
                fetch();
                setInterval(fetch, (parseInt(SAS.poll,10) || 10) * 1000);
            });

        })(jQuery);
    ' );
});

/* ---------------- Shortcode: frontend UI ---------------- */
add_shortcode( 'ajax_shoutbox', function() {
    $opts = sas_get_options();
    if ( empty( $opts['enabled'] ) ) return '<p>The shoutbox is currently disabled.</p>';

    ob_start();
    ?>
    <div id="sas-container">
        <ul id="sas-messages" aria-live="polite"></ul>

        <?php if ( is_user_logged_in() ) : ?>
            <form id="sas-form" autocomplete="off">
                <input type="text" id="sas_message" name="sas_message" placeholder="Write a message..." maxlength="300" required>
                <button type="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i></button>
            </form>
        <?php else: ?>
            <p>You must <a href="<?php echo esc_url( wp_login_url() ); ?>">log in</a> to post messages.</p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
});

/* ---------------- AJAX: post & fetch ---------------- */
add_action( 'wp_ajax_sas_post', function(){
    check_ajax_referer( 'simple_shoutbox_nonce', 'nonce' );

    $opts = sas_get_options();
    if ( empty( $opts['enabled'] ) ) wp_send_json_error( 'Shoutbox disabled.' );
    if ( ! is_user_logged_in() ) wp_send_json_error( 'You must be logged in to post.' );

    $msg = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '';
    if ( $msg === '' ) wp_send_json_error( 'Empty message.' );

    global $wpdb;
    $user = wp_get_current_user();
    $name = $user->display_name ? $user->display_name : $user->user_login;

    $wpdb->insert( SAS_TABLE, array(
        'user_id' => $user->ID,
        'user_name' => $name,
        'message' => $msg,
        'time' => current_time( 'mysql' ),
    ), array( '%d','%s','%s','%s' ) );

    // Keep only latest max_messages rows
    $max = max(1, intval( $opts['max_messages'] ));
    $count = $wpdb->get_var( "SELECT COUNT(*) FROM " . SAS_TABLE );
    if ( $count > $max ) {
        $to_delete = $count - $max;
        // delete oldest safely
        $wpdb->query( $wpdb->prepare( "DELETE FROM " . SAS_TABLE . " WHERE id IN ( SELECT id FROM ( SELECT id FROM " . SAS_TABLE . " ORDER BY time ASC LIMIT %d ) x )", $to_delete ) );
    }

    wp_send_json_success();
});

add_action( 'wp_ajax_sas_fetch', 'sas_ajax_fetch' );
add_action( 'wp_ajax_nopriv_sas_fetch', 'sas_ajax_fetch' );
function sas_ajax_fetch() {
    check_ajax_referer( 'simple_shoutbox_nonce', 'nonce' );

    $opts = sas_get_options();
    if ( empty( $opts['enabled'] ) ) wp_send_json_error( 'Shoutbox disabled.' );

    global $wpdb;
    $max = max(1, intval( $opts['max_messages'] ));
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT user_name, message, time FROM " . SAS_TABLE . " ORDER BY time DESC LIMIT %d", $max ), ARRAY_A );
    $rows = array_reverse( $rows ); // show oldest first
    wp_send_json_success( $rows );
}
