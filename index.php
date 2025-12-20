<?php
$output = shell_exec('python scrapy_detik.py 2>&1');
echo "<pre>$output</pre>";
?>