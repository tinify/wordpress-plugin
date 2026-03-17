<?php
printf( '%s: ', esc_html__( $data['label'], 'tiny-compress-images' ) );
?>
<input
    type="number"
    id="<?php esc_attr_e($data['id']); ?>"
    name="<?php esc_attr_e($data['field']); ?>"
    value="<?php esc_attr_e($data['value']); ?>"
    size="5"
/>