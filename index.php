<?php
include 'XtExplorer.php';

$traceFile = isset($_GET['filePath'])?$_GET['filePath']:'trace.sample.xt';

$traceExplorer = new XtExplorer($traceFile);

$traceExplorer->filterPrefix = '/var/www/tala';

include 'view.php';