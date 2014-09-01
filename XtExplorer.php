<?php

class XtExplorer {
    const MAX_LINE = 10000;
    const MAX_PARAM_LENGTH = 80;

    public $data = null;
    public $filterPrefix = null;

    protected  $_filePath = null;

    public function __construct($filePath) {
        $this->_filePath = $filePath;
        $this->data = $this->_parse();
    }

    /**
     * Parse Trace File content, line by line
     *
     * Each lines have the following format:
     *
     *      #level #func #entry #timeIndex #memory #funcName #7 #includeFile #fileName #lineNum #paramCount #params
     *
     * Explain:
     *      entry: 0=entry, 1=exit, R=return
     *      timeIndex: ms from start
     *      memory: bytes
     *      functionType: 0=user-defined, 1=internal
     */
    protected function _parse() {
        // TODO: add try catch
        $handle = fopen($this->_filePath, 'r');
        $i = 0;
        $isBody = false;
        $traceData = [];
        $prevFunction = null;
        while (($line = fgetcsv($handle, 0, "\t")) !== FALSE) {

            $i++;
            if ($i>self::MAX_LINE) break;

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

    public function render() {
        $this->_generateTree($this->data[0]);
    }

    private function _generateTree(&$node) {
        $hasChildren = count($node['children'])>0;
        $expandButton = $hasChildren?'<span class="fn-expand" id="fn-'.$node['functionId'].'">[+] </span>':'----';

        // parse params
        $params = '';
        if ($node['paramCount']>0) {
            $maxLen = XtExplorer::MAX_PARAM_LENGTH;
            $pArray = [];
            foreach ($node['params'] as $pItem) {
                $pArray[] = strlen($pItem) > $maxLen?substr($pItem, 0, $maxLen).'...':$pItem;
            }
            $params = implode(', ', $pArray);
        }
        if ($params=='' && $node['includeFile']!='')
            $params = $node['includeFile'];

        $filePath = $node['filePath'];
        if ($this->filterPrefix)
            $filePath = str_replace($this->filterPrefix, '', $filePath);

        $timeCost = isset($node['timeCost'])?number_format($node['timeCost'],5).'s':'';

        // render
        echo "<li><div class='fn-line'>".$expandButton.
            "<span class='fn-id'>#{$node['functionId']}</span> <span class='fn-time'>$timeCost</span><span
        class='fn-file'>$filePath [line {$node['lineNumber']}]</span>:
        <span class='fn-name'>{$node['functionName']}(<span class='fn-params'>$params</span>)</span></div>";
        if ($hasChildren) {
            echo "<ul class='fn-sub hidden' id='sub-fn-{$node['functionId']}'>";
            foreach ($node['children'] as &$item) {
                $this->_generateTree($item);
            }
            echo "</ul>";
        }
        echo "</li>";
    }

}