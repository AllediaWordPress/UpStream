<?php

?>
<div class="row">

    <select name="upstream_report__display_fields[]" multiple>
        <?php foreach ($fields as $field_name => $field) {
            if ($field['display']) {
                ?>
                <option value="<?php echo $field_name; ?>"><?php echo esc_html($field['title']); ?></option>
                <?php
            }
        }
        ?>
    </select>

</div>
<?php
