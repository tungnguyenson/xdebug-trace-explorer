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
        .fn-line {cursor:hand;cursor:pointer;margin:8px 0}
        .fn-line:hover {background:lightcyan}
        .fn-params {font-weight:normal;color:#666}
    </style>
</head>

<body>
<h3>Xdebug Trace Explorer</h3>
<p>Read <a href="https://github.com/tungbi/xdebug-trace-explorer/blob/master/README.md" target="_blank">README.md</a> for
    more
information</p>

<form id="frm">
    Trace file path (*.xt):<br/>
    <input type="text" id="filePath" name="filePath" style="width:400px" value="<?php if(isset
    ($traceFile))
        echo
    $traceFile?>"/> <input type="submit" value="Render"/>
</form>
<?php if (count($traceFiles)>0):?>
    <p>or pick from lists we found at <?php echo $traceFolder?>:<br/>
    <select id="xt-select">
        <?php foreach ($traceFiles as $f):?>
            <option value="<?php echo "$traceFolder/$f"?>"<?php if ($traceFile == "$traceFolder/$f") echo 'selected'?>><?php echo $f?></option>        <?php endforeach?>
    </select>
    </p>
<?php endif;?>


<?php if ($traceFile != ''):?>
    <hr />
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

            $('#xt-select').change(function() {
                $('#filePath').val($('#xt-select').val());
                $('#frm').submit();
            });
        });
    </script>
<?php endif;?>

</body>
</html>