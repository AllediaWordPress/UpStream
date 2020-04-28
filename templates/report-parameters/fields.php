<?php

$fields = UpStream_Model_Project::fields();
$users = (array)upstream_project_users_dropdown();

foreach ($fields as $field_name => $field):
    $fname = 'project_' . $field_name;
?>
<div class="row">

    <?php echo esc_html($field['title']); ?>

    <?php if ($field['type'] === 'string' || $field['type'] === 'text'): ?>
        <input type="text" name="<?php print $fname ?>">
    <?php elseif ($field['type'] === 'user_id'): ?>
        <select name="<?php print $fname ?>" multiple>
            <?php foreach ($users as $user_id => $username): ?>
            <option value="<?php echo $user_id; ?>"><?php echo esc_html($username); ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

</div>
<?php

endforeach;