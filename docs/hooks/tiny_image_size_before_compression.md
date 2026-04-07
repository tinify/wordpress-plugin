# tiny_image_size_before_compression

Action that is done before compressing an single image size.

**Location:** `src/class-tiny-image.php`  
**Since:** 3.7.0

## Arguments

1. `int        $attachment_id` - The attachment ID.
2. `int|string $size_name` - The image size name. 0 for the original.
3. `string     $filepath` - The file path to the image being compressed.

## Example

```php
add_filter(
	'tiny_image_size_before_compression',
	function ( $attachment_id, $size_name, $filename ) {
		// notify system of compression
	}
);
```
