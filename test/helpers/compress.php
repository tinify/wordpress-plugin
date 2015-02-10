<?php
function compressTestFile($file) {
    if (!preg_match('#test(-(\S+))?[.](png|jpe?g)$#', $file, $match)) {
        throw new TinyPNGCompressException('Does not appear to be a PNG or JPEG file', 'BadSignature');
    }

    switch ($match[2]) {
        case "thumbnail":
            return array('input' => array('size' => 100), 'output' => array('size' => 81));
        case "medium":
            return array('input' => array('size' => 1000), 'output' => array('size' => 768));
        case "large":
            return array('input' => array('size' => 10000), 'output' => array('size' => 6789));
        case "post-thumbnail":
            return array('input' => array('size' => 1234), 'output' => array('size' => 1000));
        default:
            return array('input' => array('size' => 12345), 'output' => array('size' => 10000));
    }
}

function getTestMetadata() {
    return array(
        'file' => '14/01/test.png',
        'sizes' => array(
            'thumbnail'      => array('file' => 'test-thumbnail.png'),
            'medium'         => array('file' => 'test-medium.png'),
            'large'          => array('file' => 'test-large.png'),
            'post-thumbnail' => array('file' => 'test-post-thumbnail.png'),
        )
    );
}
