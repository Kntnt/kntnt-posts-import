<div class="wrap">
    <h2><?php echo $title; ?></h2>
    <?php foreach ( $messages as $color => $m ): ?>
        <?php foreach ( $m as $message ): ?>
            <p style="color:<?php echo $color; ?>"><?php echo $message ?></p>
        <?php endforeach; ?>
    <?php endforeach; ?>
    <form method="post" enctype="multipart/form-data">
        <?php echo wp_nonce_field( $ns ); ?>
        <input type="file" name="import_file" id="import_file">
        <?php submit_button( $submit_button_text, 'primary' ); ?>
    </form>
</div>
