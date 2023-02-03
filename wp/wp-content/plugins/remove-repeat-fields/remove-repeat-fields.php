<?php
/*
Plugin Name: Remove Repeat Fields
Description: Remove repeat fields for ACF (Advanced Custom Fields)
Version: 1.0
Author: Evgeniy Zhukov
*/


if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('RemoveRepeatFields')) {

    $remove_repeat_fields = new RemoveRepeatFields();
    $remove_repeat_fields->register();
}

/**
 * Class RemoveRepeatFields
 */
class RemoveRepeatFields
{
    public function register()
    {

        add_action('add_meta_boxes', [$this, 'post_views_counter_add_metabox']);

        add_action('wp_ajax_remove_selected_repeat_fields', [$this, 'remove_selected_repeat_fields'], 10, 2);
    }

    public function post_views_counter_add_metabox()
    {

        add_meta_box(
            'remove-repeat-fields',
            __('Удаляем выборочно подкасты', 'remove-repeat-fields'),
            [$this, 'display_remove_repeat_fields_meta_box'],
            'post',
            'side',
            'default'
        );
    }

    public function remove_selected_repeat_fields()
    {
        $post_id = $_POST['post_id'];
        $type = $_POST['type'];
        $items = $_POST['items'];

        if (empty($type) || empty($items)) {
            return;
        }

        $post_id = intval($post_id);
        $field_type = sanitize_text_field($type);
        $field_indexes = sanitize_text_field($_POST['items']);

        $field = get_field($field_type, $post_id);
        if (!$field) {
            return;
        }

        foreach ($field as $index => $row) {

            if ($row['podborka_item_type'] == $field_indexes) {
                unset($field[$index]);
            }
        }

        update_field($field_type, $field, $post_id);
    }

    public function display_remove_repeat_fields_meta_box($post)
    {
        $podkast_types = get_field_objects();
        $podborka_delete_types = get_field('podborka_projects');
?>
        <div id="remove-repeat-fields-container">

            <label for="remove-repeat-fields-type">Выбрать подборки:</label>
            <select id="remove-repeat-fields-type" name="remove-repeat-fields-type">
                <option value="">-- Выберите тип подкаста --</option>
                <?php foreach ($podkast_types as $podkast_type) : ?>
                    <option value="<?php echo $podkast_type['name']; ?>">
                        <?php echo $podkast_type['label']; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="remove-repeat-delete-fields-type">Выбрать подборки:</label>
            <select id="remove-repeat-delete-fields-type" name="remove-repeat-delete-fields-type">
                <option value="">-- Выберите какие удаляем --</option>
                <?php foreach ($podborka_delete_types as $key => $podborka_delete_type) : ?>
                    <option value="<?php echo $podborka_delete_type['podborka_item_type']; ?>">
                        <?php echo $podborka_delete_type['podborka_item_type']; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button id="remove-repeat-fields-button">Удалить выбранные</button>

        </div>
        <script>
            /* <![CDATA[ */

            jQuery(document).ready(function($) {

                var seen = {};
                $("#remove-repeat-delete-fields-type option").each(function() {
                    var txt = $(this).val();
                    if (seen[txt]) {
                        $(this).remove();
                    } else {
                        seen[txt] = true;
                    }
                });

                $('#remove-repeat-fields-button').click(function(e) {
                    e.preventDefault();

                    var type = $('#remove-repeat-fields-type').val();
                    var items = $('#remove-repeat-delete-fields-type').val();

                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'post',
                        data: {
                            action: 'remove_selected_repeat_fields',
                            post_id: <?php echo $post->ID; ?>,
                            type: type,
                            items: items
                        },
                        success: function(response) {
                            console.log('OK' + response);
                            location.reload()
                        },
                        error: function(request, error) {
                            console.log(error);
                        }
                    });

                });
            });
            /* ]]> */
        </script>
<?php
    }
}
