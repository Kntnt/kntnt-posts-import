<?php /** @noinspection PhpUndefinedVariableInspection */ ?>
<div class="wrap">
    <h2><?php echo $title; ?></h2>
    <?php foreach ( $messages as $message ): ?>
        <?php $color = 'ERROR' == $message['context'] ? 'red' : 'green'; ?>
        <p style="color:<?php echo $color; ?>"><?php echo $message['message'] ?></p>
    <?php endforeach; ?>
    <form method="post" enctype="multipart/form-data">
        <?php echo wp_nonce_field( $ns ); ?>
        <input type="file" name="import_file" id="import_file">
        <?php submit_button( $submit_button_text, 'primary' ); ?>
    </form>
</div>
