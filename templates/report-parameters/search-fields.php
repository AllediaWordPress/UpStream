<?php

$fields = UpStream_Model_Project::fields();
$users = upstream_get_viewable_users();

foreach ($fields as $field_name => $field):
    $fname = 'project_' . $field_name;

    if (!$field['search']) continue;
?>
<div class="row">

    <?php echo esc_html($field['title']); ?>

    <?php if ($field['type'] === 'string' || $field['type'] === 'text'): ?>
        <input type="text" name="<?php print $fname ?>">
    <?php elseif ($field['type'] === 'user_id'): ?>
        <select name="<?php print $fname ?>[]" multiple>
            <?php foreach ($users as $user_id => $username): ?>
            <option value="<?php echo $user_id; ?>"><?php echo esc_html($username); ?></option>
            <?php endforeach; ?>
        </select>
    <?php elseif ($field['type'] === 'select'):
        ?>
        <select name="<?php print $fname ?>[]" multiple>
            <?php foreach (call_user_func($field['options_cb']) as $key => $value): ?>
                <option value="<?php echo $key; ?>"><?php echo esc_html($value); ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

</div>
<?php

endforeach;