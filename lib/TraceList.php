<?php

namespace vuzonp\XTracer;

/**
 * Collection of traces.
 */
class TraceList implements \Iterator, \Countable
{
    /** @var \SplDoublyLinkedList */
    private $traces;

    /**
     * Constructor of the class.
     */
    public function __construct()
    {
        $this->traces = new \SplDoublyLinkedList;
    }

    /**
     * Adds new trace to the collection.
     * @param \vuzonp\XTracer\Trace $trace
     * @return \vuzonp\XTracer\Trace
     */
    public function add(Trace $trace)
    {
        $this->traces->push($trace);
        return $trace;
    }

    /**
     * Counts traces of the collection.
     * @return int
     */
    public function count()
    {
        return $this->traces->count();
    }

    /**
     * Checks whether the collection is empty.
     * @return boolean
     */
    public function isEmpty()
    {
        return $this->traces->isEmpty();
    }

    /**
     * Returns the current trace.
     * @return \vuzonp\XTracer\Trace
     */
    public function current()
    {
        return $this->traces->current();
    }

    /**
     * Returns the current trace index.
     * @return int
     */
    public function key()
    {
        return $this->traces->key();
    }

    /**
     * Moves to next trace.
     */
    public function next()
    {
        $this->traces->next();
    }

    /**
     * Rewinds iterator back to the start.
     */
    public function rewind()
    {
        $this->traces->rewind();
    }

    /**
     * Checks whether the list contains more traces.
     * @return boolean
     */
    public function valid()
    {
        return $this->traces->valid();
    }
}