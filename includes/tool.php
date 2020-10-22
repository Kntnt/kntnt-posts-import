<div class="wrap">
    <h2><?php echo $title; ?></h2>
    <?php foreach ( $errors as $error ): ?><p style="color:red"><?php echo $error ?></p><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data">
        <?php echo wp_nonce_field( $ns ); ?>
        <input type="file" name="import_file" id="import_file">
        <?php submit_button( $submit_button_text, 'primary' ); ?>
    </form>
</div>
