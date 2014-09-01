xdebug-trace-explorer
=====================

View XDebug Trace file .xt in treeview

# XDebug config

    ;mandatory, enable xdebug
    zend_extension=xdebug.so

    ;mandatory, enable computer readable format
    xdebug.trace_format = 1

    ;optional
    xdebug.trace_enable_trigger = On
    xdebug.trace_output_dir = /var/log/xdebug/trace
    xdebug.collect_params = 3

# How to use

Run `xdebug-trace-explorer` in your browser
