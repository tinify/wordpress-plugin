# CLI

TinyPNG provides several CLI commands allowing you to automate actions on your website.

## Commands

### `wp tiny optimize`

Optimizes images in the WordPress media library.  
When run without arguments, all images that have not yet been optimized are processed. You can also target specific attachments by providing a comma-separated list of attachment IDs.

**Options**

| Option | Description |
|---|---|
| `--attachments=<ids>` | Comma-separated list of attachment IDs to optimize. Omit to optimize all unoptimized images. |

**Examples**

Optimize all unoptimized images:

```bash
wp tiny optimize
```

Optimize specific attachments by ID:

```bash
wp tiny optimize --attachments=532,603,705
```

**Output**

Skipped attachments (invalid or unsupported file types) are reported as warnings. When finished, a summary shows how many images were successfully optimized.

```
Optimizing 3 images.
Optimizing images: 100% (3 of 3)
Success: Done! Optimized 3 of 3 images.
```
