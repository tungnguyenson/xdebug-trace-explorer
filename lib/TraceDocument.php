<?php

namespace vuzonp\XTracer;

/**
 * Represents a xdebug trace file.
 */
class TraceDocument extends \SplFileInfo {

    /** Character used for separating columns in file. */
    protected $delimiter = "\t";

    /**
     * Ordered list of columns of a trace.
     * @var array
     */
    protected $offsets = array(
        'level'       => 0,
        'id'          => 1,
        'entry'       => 2,
        'time'        => 3,
        'memory'      => 4,
        'func_name'   => 5,
        'func_type'   => 6,
        'inc_file'    => 7,
        'filename'    => 8,
        'line'        => 9,
        'param_count' => 10,
    );

    /**
     * Root node of the document.
     * @var null | \vuzonp\XTracer\Trace
     */
    private $root;


    /**
     * Constructor of the class.
     *
     * @param string $filename The file to parse as document.
     * @param array $offsets Ordored list of columns in the document.
     * @param string $delimiter Character used for separating columns in file.
     */
    public function __construct($filename, array $offsets = null, $delimiter = "\t") {
        parent::__construct($filename);
        if ($offsets !== null) {
            $this->offsets = $offsets;
        }
        $this->delimiter = (string) $delimiter;
    }

    /**
     * Parses the document and orders the traces in a tree.
     * @throws \LogicException While the document is invalid.
     * @uses self::addNewTrace For create new node.
     * @uses \vuzonp\XTracer\Trace Represents each node of the tree.
     */
    protected function parse()
    {
        if (!$this->isFile() || !$this->isReadable()) {
            throw new \LogicException(
                sprintf(
                    _("The file `%s` does not exists or is not readable"),
                    $file
                )
            );
        }

        $prevTrace = null;
        $fileObject = $this->openFile('r');

        // Loop applicated on each line of the file.
        while ($fileObject->eof() === false) {
            $csv = $fileObject->fgetcsv($this->delimiter);

            // Verifies if it is a valid csv line:
            if ($csv === false || !isset($csv[$this->offsets['id']])) {
                continue;
            }

            // The first found trace is used as main reference.
            if ($prevTrace === null) {
                $this->root = Trace::fromArray($csv, $this->offsets);
                $prevTrace = $this->root;
                continue;
            }

            // Checks if the trace is a new entry:
            if ($csv[$this->offsets['entry']] === '0') {
                // It's a new trace, creates the node,
                $trace = Trace::fromArray($csv, $this->offsets);
                // Then, defines the parameters of the function.
                $this->setParamsToTrace($trace, $csv);
                // After, attaches the trace to correct parent.
                $this->attachNewTrace($trace, $prevTrace);
                $prevTrace = $trace;
            } else {
                // The trace already exists.
                $prevTrace->benchAndClose(
                    $csv[$this->offsets['time']],
                    $csv[$this->offsets['memory']]
                );
            }

        }

        // If the trace is always empty, the file is considered as invalid.
        if ($this->root === null) {
            throw new \LogicException(
                sprintf(
                    _("The file `%s` is empty or not a valid trace document."),
                    $file
                )
            );
        }
    }

    /**
     * Adds attributes to a trace from csv.
     * 
     * @param \vuzonp\XTracer\Trace $trace The trace object to handle.
     * @param array $csv The raw trace stored in an array
     */
    private function setParamsToTrace(Trace $trace, array $csv)
    {
        if ($trace->hasParams()) {
            $trace->setParams(
                array_slice(
                    $csv,
                    -$csv[$this->offsets['param_count']]
                )
            );
        }
    }

    /**
     * Attaches a new trace node by linking it to a previous trace node.
     *
     * @param \vuzonp\XTracer\Trace $newTrace New trace to append.
     * @param \vuzonp\XTracer\Trace $prevTrace Previous trace node.
     */
    protected function attachNewTrace(Trace $newTrace, Trace $prevTrace)
    {
        // Back in the hierarchy when the previous trace is not in the valid branch.
        while($newTrace->level < $prevTrace->level) {
            $prevTrace = $prevTrace->parent;
        }

        if ($newTrace->level === $prevTrace->level) {
            // The trace is the same level as the previous,
            // so it attaches to the parent thereof.
            $prevTrace->parent->appendChild($newTrace);
        } else {
            // The trace is a child of the previous, it attaches to it.
            $prevTrace->appendChild($newTrace);
        }
    }

    /**
     * Returns the main trace of the document.
     * 
     * @uses self::parse
     * @return \vuzonp\XTracer\Trace The main trace.
     */
    public function getRoot()
    {
        // If the tree does not exist, tries to create it.
        if ($this->root === null) {
            $this->parse();
        }
        return $this->root;
    }

}
