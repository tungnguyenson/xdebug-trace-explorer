<?php

namespace vuzonp\XTracer;

/**
 * Represents the trace of a function
 */
class Trace
{
    /**
     * Default ordered list of columns of a trace.
     * @var array
     */
    static protected $offsets = array(
        'level'       => 0,
        'id'          => 1,
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
     * Trace lock status.
     * @var boolean
     */
    protected $locked = false;

    /**
     * Nesting depth of the trace.
     * @var int
     */
    protected $level;

    /** 
     * Identifier of the trace.
     * @var int
     */
    protected $id;

    /** 
     * Time of execution of the function (in seconds)
     * @var float
     */
    protected $time;

    /** 
     * Memory consumption of the function (in bytes)
     * @var int
     */
    protected $memory;

    /**
     * Name of the function concerned by the trace.
     * @var string
     */
    protected $funcName;

    /**
     * Function type (1 for native function, 0 otherwise).
     * @var int
     */
    protected $funcType;

    /**
     * If not null, the file imported by the function.
     * @var string|null
     */
    protected $incFile;

    /**
     * File name of runtime
     * @var string
     */
    protected $filename;

    /**
     * Line number of runtime.
     * @var int
     */
    protected $line;

    /**
     * Number of parameters passed to the function.
     * @var int
     */
    protected $paramCount;

    /** 
     * List of of parameters passed to the function.
     * @var null | \SplFixedArray
     */
    protected $params;

    /**
     * Children traces of this one.
     * @var \vuzonp\XTracer\TraceList
     */
    protected $children;

    /**
     * Parent trace of this one.
     * @var \vuzonp\XTracer\Trace
     */
    protected $parent;


    /**
     * Converts an array to a trace object.
     *
     * @param array $csv The array to convert.
     * @return \vuzonp\XTracer\Trace
     */
    public static function fromArray(array $csv, array $offsets = null)
    {
        if ($offsets === null) {
            // If the offsets are not defined, uses the default positions.
            $offsets = self::$offsets;
        }
        // Creates the new trace.
        return new self(
            $csv[$offsets['level']],
            $csv[$offsets['id']],
            $csv[$offsets['time']],
            $csv[$offsets['memory']],
            $csv[$offsets['func_name']],
            $csv[$offsets['func_type']],
            $csv[$offsets['inc_file']],
            $csv[$offsets['filename']],
            $csv[$offsets['line']],
            $csv[$offsets['param_count']]
        );
    }

    /**
     * Defines new default values for ordered list of columns of a trace.
     * @param array $offsets
     */
    public static function defineArrOffsets(array $offsets)
    {
        self::$offsets = $offsets;
    }

    /**
     * Constructor of the Trace
     *
     * @param int $level The nesting depth of the trace.
     * @param int $id The identifier of the trace.
     * @param float $time Start time of execution of the function.
     * @param int $memory Memory state of the program at the beginning.
     * @param string $funcName Name of the function concerned by the trace.
     * @param int $funcType Function type (1 for native function, 0 otherwise).
     * @param string|null $incFile File imported by the function
     * @param string $filename File name of runtime.
     * @param int $line Line number of runtime.
     * @param int $paramCount Number of parameters passed to the function.
     */
    public function __construct(
        $level,
        $id,
        $time,
        $memory,
        $funcName,
        $funcType,
        $incFile,
        $filename,
        $line = -1,
        $paramCount = 0
    ) {
        $this->level = (int) $level;
        $this->id = (int) $id;
        $this->time = (float) $time;
        $this->memory = (int) $memory;
        $this->funcName = (string) $funcName;
        $this->funcType = (int) $funcType;
        $this->incFile = (string) $incFile;
        $this->filename = (string) $filename;
        $this->line = (int) $line;
        $this->paramCount = (int) $paramCount;
        $this->children = new TraceList();
        $this->parent = null;

        $this->locked = false;
    }

    public function __isset($key)
    {
        return isset($this->{$key});
    }

    public function __get($key)
    {
        return (isset($this->$key) === true) ?
            $this->$key :
            null;
    }

    /**
     * Is it a native function?
     * @return boolean
     */
    public function isNative()
    {
        return ($this->funcType === 1);
    }

    /**
     * The function includes a file to it?
     * @return boolean
     */
    public function hasIncludedFile()
    {
        return ($this->incFile !== null);
    }

    /**
     * Retrieves the file of the function as a `\SplFileInfo` object
     * @return \SplFileInfo
     */
    public function getFile()
    {
        return new \SplFileInfo($this->filename);
    }

    /**
     * Attaches a child to the trace.
     * @param \vuzonp\XTracer\Trace $child
     * @throws \DomainException When the child's level is not correct.
     */
    public function appendChild(Trace $child)
    {
        if ($this->locked === false) {
            if ($child->level > $this->level) {
                 $child->setParent($this);
            } else {
                throw new \DomainException(
                    sprintf(
                        _('The child\'s (#%s) level must be upper than the parent\'s (#%s) level'),
                        $child->id,
                        $this->id
                    )
                );
            }
        }
    }

    /**
     * Specifies the parent of the trace.
     * @param \vuzonp\XTracer\Trace $parent
     * @throws \LogicException When the trace has already a parent.
     */
    final protected function setParent(Trace $parent)
    {
        if ($this->parent !== null) {
            throw new \LogicException(
                sprintf(
                    _('The trace (#%s) has already a parent (#%s)'),
                    $this->id,
                    $this->parent->id
                )
            );
        }
        $this->parent = $parent;
        $this->parent->children->add($this);
    }

    /**
     * Indicates if two traces are the same trace
     * (but not necessary the same object).
     * @param \vuzonp\XTracer\Trace $trace
     * @return boolean
     */
    public function isSameTrace(Trace $trace)
    {
        return !!($this->id === $trace->id);
    }

    /**
     * The trace has parameters?
     * @return boolean
     */
    public function hasParams()
    {
        return !!($this->paramCount > 0);
    }

    /**
     * Attributes a list of parameters to the trace.
     * @param array $params An array of parameters
     */
    public function setParams(array $params)
    {
        if ($this->locked === false) {
            $this->params = \SplFixedArray::fromArray($params, false);
        }
    }

    /**
     *
     * @param type $endTime
     * @param type $endMemory
     */
    public function benchAndClose($endTime, $endMemory)
    {
        $this->benchmark($endTime, $endMemory);
        $this->close();
    }

    /**
     * Specifies the end time and end memory used by the trace for calculate benchmark.
     * @param float $endTime
     * @param int $endMemory
     */
    public function benchmark($endTime, $endMemory)
    {
        if ($this->locked === false) {
            $this->time = round($endTime - $this->time, 5);
            $this->memory = $endMemory - $this->memory;
        }
    }

    /**
     * The trace can be edited?
     * @return boolean
     */
    public function isClosed()
    {
        return $this->locked;
    }

    /**
     * Freezes the trace in its status.
     */
    public function close()
    {
        $this->locked = true;
    }

}
