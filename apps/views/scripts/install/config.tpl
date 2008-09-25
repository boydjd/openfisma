
[database]
adapter=mysqli 
params.host=<?php echo $this->dsn['host']; ?> 
params.port=<?php echo $this->dsn['port'];?> 
params.username=<?php echo $this->dsn['name_c'];?> 
params.password=<?php echo $this->dsn['pass_c'];?> 
params.dbname=<?php echo $this->dsn['dbname'];?> 

[host]
hostUrl=<?php echo "{$this->hostUrl}\n"; ?>

[debug]
; DBG=1, RELEASE=0
level=0
; Following takes effect only if level>0
xdebug.collect_params=0
xdebug.collect_return=1
xdebug.trace_options=1
xdebug.trace_output_name="sws.%u"
xdebug.auto_trace=0

