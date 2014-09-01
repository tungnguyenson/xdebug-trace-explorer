<!DOCTYPE html>
<html>
<head>
    <title>Xdebug Trace Explorer</title>
    <style type="text/css">
        body {font-family: Arial}
        .hidden {display:none}
        .fn-tree {padding-left:60px;padding-right:80px}
        .fn-tree li {list-style: none}
        .fn-sub {padding-left: 20px}
        .fn-id {font-weight:bold;position: absolute;left:0;width:50px;text-align: right}
        .fn-time {position:absolute;right:0}
        .fn-file {color:#666}
        .fn-name {font-weight:bold;color:blue}
        .fn-line {cursor:hand;cursor:pointer;}
        .fn-line:hover {background:lightcyan}
        .fn-params {font-weight:normal;color:#666}
    </style>
</head>

<body>
<h3>Xdebug Trace Explorer</h3>
<p>Read <a href="https://github.com/tungbi/xdebug-trace-explorer/blob/master/README.md" target="_blank">README.md</a> for
    more
information</p>

<form>
    Trace file path (*.xt): <input type="text" name="filePath" style="width:400px" value="<?php if(isset($traceFile))
        echo
    $traceFile?>"/> <input type="submit" value="Render"/>
</form>
<br />
<hr />

<?php

?>
<ul class="fn-tree">
<?php $traceExplorer->render()?>
</ul>

<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('.fn-line').click(function(){
            var $expand = $(this).find('> .fn-expand');
            var fnId = $expand.attr('id');
            $('#sub-'+fnId).toggleClass('hidden');
            $expand.html($('#sub-'+fnId).hasClass('hidden')?'[+] ':'[-] ');
        });
    });
</script>
</body>
</html>