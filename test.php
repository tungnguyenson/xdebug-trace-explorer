<?php
// xdebug.trace_output_dir
/**
 * #level #func #entry #timeIndex #memory #funcName #7 #includeFile #fileName #lineNum #paramCount #params
 *
 *      entry: 0=entry, 1=exit, R=return
 *      timeIndex: ms from start
 *      memory: bytes
 *      functionType: 0=user-defined, 1=internal
 */
$file = 'trace.sample.xt';

function testParse($file) {
    $handle = fopen($file, 'r');
    $maxLine = 10000;
    $i = 0;
    $isBody = false;
    //$traceTree = [];
    $traceData = [];
    $prevFunction = null;
    while (($line = fgetcsv($handle, 0, "\t")) !== FALSE) {

        $i++;
        if ($i>$maxLine) break;

        if ($isBody && ($line[0]=='' || substr($line[0],0,strlen('TRACE END')) == 'TRACE END')) {
            $isBody = false;
        }

        if ($isBody) {
            // 1. Remapping
            $lineData = [
                'level'         => $line[0],
                'functionId'    => $line[1],
                'entry'         => $line[2],
                'timeIndex'     => $line[3],
                'memory'        => $line[4],
                'functionName'  => isset($line[5])?$line[5]:'',
                'functionType'  => isset($line[6])?$line[6]:'',
                'includeFile'   => isset($line[7])?$line[7]:'',
                'filePath'      => isset($line[8])?$line[8]:'',
                'lineNumber'    => isset($line[9])?$line[9]:'',
                'paramCount'    => isset($line[10])?$line[10]:0,
                'params'        => [],
                'children'      => [],
                'parentId'        => null
            ];

            if ($lineData['paramCount']>0) {
                for ($i=0;$i<$lineData['paramCount'];$i++) {
                    if (isset($line[$i+11])) {
                        $lineData['params'][] = $line[$i+11];
                    }
                }
            }

            if ($lineData['functionId'] == 15) {
                $i = 1;
            }


            // 2. Align to trace hierarchical
            $functionId = $lineData['functionId'];
            if (!isset($traceData[$functionId])) {
                $newItem = $lineData;
                /**
                 * Build tree node:
                 *    prevFunction = null  : 1st node
                 *    newLevel > prevLevel : new child of prevLevel
                 *    newLevel = prevLevel : new sibling of prevLevel (child of its parent)
                 *    newLevel < prevLevel : on return, do nothing, just update
                 */
                if ($prevFunction == null) {
                    //$traceTree[$functionId] = &lineData;
                }
                else {
                    $newLevel  = $newItem['level'];
                    $prevLevel = $prevFunction['level'];

                    if ($newLevel > $prevLevel) {
                        $parentId  = $prevFunction['functionId'];
                        $newItem['parentId'] = $parentId;
                        $prevFunction['children'][] = &$newItem;
                    }
                    else if ($newLevel==$prevLevel) {
                        $parentId  = $prevFunction['parentId'];
                        $parent = &$traceData[$parentId];
                        $newItem['parentId'] = $parentId;
                        $parent['children'][] = &$newItem;
                    }
                    else {
                        // do nothing
                    }
                }

                $traceData[$functionId] = &$newItem;
                $prevFunction = &$newItem;
            }
            else {
                $traceData[$functionId]['timeCost'] = $lineData['timeIndex']-$traceData[$functionId]['timeIndex'];
                $prevFunction = &$traceData[$functionId];
            }

            unset($newItem);

        }

        if (substr($line[0],0,strlen('TRACE START')) == 'TRACE START') {
            $isBody = true;
        }
    }
    fclose($handle);

    return $traceData;
}

function generateTree(&$node) {
    $hasChildren = count($node['children'])>0;
    $expandButton = $hasChildren?'<span class="fn-expand" id="fn-'.$node['functionId'].'">[+] </span>':'----';

    // parse params
    $params = '';
    if ($node['paramCount']>0) {
        $maxLen = 50;
        $pArray = [];
        foreach ($node['params'] as $pItem) {
            $pArray[] = strlen($pItem) > $maxLen?substr($pItem, 0, $maxLen).'...':$pItem;
        }
        $params = implode(', ', $pArray);
    }
    if ($params=='' && $node['includeFile']!='')
        $params = $node['includeFile'];

    $filePath = $node['filePath'];
    $filePath = str_replace('/var/www/tala', '', $filePath);

    $timeCost = isset($node['timeCost'])?number_format($node['timeCost'],5).'s':'';
    // render
    echo "<li><div class='fn-line'>".$expandButton.
        "<span class='fn-id'>#{$node['functionId']}</span> $timeCost: <span
        class='fn-file'>$filePath [line {$node['lineNumber']}]</span>:
        <span class='fn-name'>{$node['functionName']}(<span class='fn-params'>$params</span>)</span></div>";
    if ($hasChildren) {
        echo "<ul class='fn-sub hidden' id='sub-fn-{$node['functionId']}'>";
        foreach ($node['children'] as &$item) {
            generateTree($item);
        }
        echo "</ul>";
    }
    echo "</li>";
}

?>
<style type="text/css">
    .hidden {display:none}
    .fn-tree li {list-style: none}
    .fn-sub {padding-left: 20px;position: relative}
    .fn-id {font-weight:bold}
    .fn-file {color:#666}
    .fn-name {font-weight:bold;color:blue}
    .fn-line {cursor:hand;cursor:pointer}
    .fn-line:hover {background:lightcyan}
    .fn-params {font-weight:normal;color:#666}
</style>

<?php
$traceData = testParse($file);
//var_dump($traceData[0]['children']);
echo '<ul class="fn-tree">';
generateTree($traceData[0]);
echo '</ul>';
?>

<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('.fn-line').click(function(){
            var $expand = $(this).find('> .fn-expand');
            var fnId = $expand.attr('id');
            $('#sub-'+fnId).toggleClass('hidden');
            $expand.html($('#sub-'+fnId).hasClass('hidden')?'[+] ':'[-] ');
        });
        //$('#sub-fn-0').removeClass('hidden');
    });
</script>