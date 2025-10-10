# tiny_replace_with_picture

Filter that allows you to skip converting page content `<img>` tags into `<picture>` elements. Returning `false` disables the feature completely for that request.
The filter will fire on `init` with a default priority of `10`. Register your filter during or before `init` with a smaller priority than `10` to take effect.

**Location:** `src/class-tiny-plugin.php`  
**Since:** 3.7.0

## Arguments

1. `bool $should_replace` — boolean to control wether `<img>` elements on the page should be replaced by `<picture>` elements if they have an optimized version. Defaults to true. Return `false` to skip all `<picture>` replacements.

## Example

```php
add_filter(
	'tiny_replace_with_picture',
	function ( $should_replace ) {
		// Disable picture replacement on RSS feeds.
		if ( is_feed() ) {
			return false;
		}

		return $should_replace;
	}
);
```
