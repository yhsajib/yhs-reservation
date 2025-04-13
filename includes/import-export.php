<?php
class RV_Order_Import_Export {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('restrict_manage_posts', [$this, 'add_import_export_buttons']);
        add_action('admin_post_rv_export_orders', [$this, 'export_orders_csv']);
        add_action('admin_post_rv_import_orders', [$this, 'rv_import_orders_from_csv']);
    }

    public function add_submenu_page() {
        add_submenu_page(
            'edit.php?post_type=rv_order',
            'Import/Export Orders',
            'Import/Export',
            'manage_options',
            'rv_order_import_export',
            [$this, 'render_import_export_page']
        );
    }

    public function add_import_export_buttons($post_type) {
        if ($post_type !== 'rv_order') return;

        $export_url = admin_url('admin-post.php?action=rv_export_orders');
        $import_url = admin_url('edit.php?post_type=rv_order&page=rv_order_import_export');
        ?>
        <style>
            .rv-import-export-btns {
                display: inline-flex;
                gap: 8px;
                margin-left: 10px;
                vertical-align: middle;
            }
            .rv-import-export-btns a.button {
                text-decoration: none;
            }
        </style>
        <div class="rv-import-export-btns">
            <a href="<?php echo esc_url($export_url); ?>" class="button">Export Orders</a>
            <a href="<?php echo esc_url($import_url); ?>" class="button">Import Orders</a>
        </div>
        <?php
    }

    public function export_orders_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $args = [
            'post_type'      => 'rv_order',
            'post_status'    => 'any',
            'posts_per_page' => -1,
        ];
        $orders = get_posts($args);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="rv_orders_export.csv"');
        $output = fopen('php://output', 'w');

        $meta_keys = [];
        foreach ($orders as $order) {
            $meta = get_post_meta($order->ID);
            $meta_keys = array_unique(array_merge($meta_keys, array_keys($meta)));
        }

        $headers = array_merge(['ID', 'Title', 'Date'], $meta_keys);
        fputcsv($output, $headers);

        foreach ($orders as $order) {
            $row = [
                $order->ID,
                $order->post_title,
                $order->post_date,
            ];
            foreach ($meta_keys as $key) {
                $value = get_post_meta($order->ID, $key, true);
                $row[] = is_array($value) ? json_encode($value) : $value;
            }
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    public function rv_import_orders_from_csv() {
        if (!current_user_can('manage_options') || !isset($_POST['rv_import_orders_nonce']) || !wp_verify_nonce($_POST['rv_import_orders_nonce'], 'rv_import_orders')) {
            wp_die('Unauthorized or invalid nonce.');
        }

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die('File upload failed.');
        }

        $file = fopen($_FILES['import_file']['tmp_name'], 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($headers, $row);

            $post_id = intval($data['ID']);
            $post_title = sanitize_text_field($data['Title']);

            $existing_post = get_post($post_id);

            if ($existing_post && $existing_post->post_title === $post_title && $existing_post->post_type === 'rv_order') {
                wp_update_post([
                    'ID'         => $post_id,
                    'post_title' => $post_title,
                    'post_date'  => $data['Date'],
                ]);
            } else {
                $post_id = wp_insert_post([
                    'post_type'   => 'rv_order',
                    'post_status' => 'publish',
                    'post_title'  => $post_title,
                    'post_date'   => $data['Date'],
                ]);
            }

            foreach ($data as $key => $value) {
                if (in_array($key, ['ID', 'Title', 'Date'])) continue;              
                update_post_meta($post_id, $key,$value);
            }
        }

        fclose($file);

        wp_redirect(admin_url('edit.php?post_type=rv_order&page=rv_order_import_export&import=success'));
        exit;
    }

    public function render_import_export_page() {
        ?>
        <div class="wrap">
            <h1>Import/Export Orders</h1>

            <?php if (isset($_GET['import']) && $_GET['import'] === 'success') : ?>
                <div class="notice notice-success"><p>Orders imported successfully.</p></div>
            <?php endif; ?>

            <h2>Export Orders</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="rv_export_orders">
                <?php submit_button('Download CSV'); ?>
            </form>

            <h2>Import Orders</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="rv_import_orders">
                <?php wp_nonce_field('rv_import_orders', 'rv_import_orders_nonce'); ?>
                <input type="file" name="import_file" required>
                <?php submit_button('Import CSV'); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize the class
RV_Order_Import_Export::get_instance();
