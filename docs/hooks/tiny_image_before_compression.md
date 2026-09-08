# tiny_image_before_compression

Action that runs before compressing an single attachment.

**Location:** `src/class-tiny-image.php`  
**Since:** 3.7.0

## Arguments

1. `int        $attachment_id` - The attachment ID.
2. `array|null $wp_metadata` - The attachment metadata.

The metadata is passed along because WordPress has not necessarily stored it
yet. On upload the compression runs from within
`wp_generate_attachment_metadata`, so `wp_get_attachment_metadata()` can still
be empty at this point. Use the passed metadata instead of looking it up.

## Example

```php
add_action(
	'tiny_image_before_compression',
	function ( $id, $wp_metadata ) {
		// notify system of compression
	},
	10,
	2
);
```
