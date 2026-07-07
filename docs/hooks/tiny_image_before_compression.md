# tiny_image_before_compression

Action that is done before compressing an single attachment

**Location:** `src/class-tiny-image.php`  
**Since:** 3.7.0

## Arguments

1. `int        $attachment_id` - The attachment ID.

## Example

```php
add_action(
	'tiny_image_before_compression',
	function ( $id ) {
		// notify system of compression
	}
);
```
