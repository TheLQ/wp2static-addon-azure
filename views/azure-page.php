<h2>Azure Deployment Options</h2>

<h3>Azure</h3>

<form
    name="wp2static-azure-save-options"
    method="POST"
    action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

    <?php wp_nonce_field( $view['nonce_action'] ); ?>
    <input name="action" type="hidden" value="wp2static_azure_save_options" />

<table class="widefat striped">
    <tbody>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['storageAccountName']->name; ?>"
                ><?php echo $view['options']['storageAccountName']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['storageAccountName']->name; ?>"
                    name="<?php echo $view['options']['storageAccountName']->name; ?>"
                    type="text"
                    value="<?php echo $view['options']['storageAccountName']->value !== '' ? $view['options']['storageAccountName']->value : ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['storageContainer']->name; ?>"
                ><?php echo $view['options']['storageContainer']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['storageContainer']->name; ?>"
                    name="<?php echo $view['options']['storageContainer']->name; ?>"
                    type="text"
                    value="<?php echo $view['options']['storageContainer']->value !== '' ? $view['options']['storageContainer']->value : ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['storageFolder']->name; ?>"
                ><?php echo $view['options']['storageFolder']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['storageFolder']->name; ?>"
                    name="<?php echo $view['options']['storageFolder']->name; ?>"
                    type="text"
                    value="<?php echo $view['options']['storageFolder']->value !== '' ? $view['options']['storageFolder']->value : ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['sasToken']->name; ?>"
                ><?php echo $view['options']['sasToken']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['sasToken']->name; ?>"
                    name="<?php echo $view['options']['sasToken']->name; ?>"
                    type="password"
                    value="<?php echo $view['options']['sasToken']->value !== '' ?
                        \WP2Static\CoreOptions::encrypt_decrypt('decrypt', $view['options']['sasToken']->value) :
                        ''; ?>"
                />
            </td>
        </tr>

    </tbody>
</table>

<br>

    <button class="button btn-primary">Save Azure Options</button>
</form>
