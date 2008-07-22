
installed=true

[database]
adapter=mysqli 
params.host=<?php echo $this->dsn['host']; ?> 
params.port=<?php echo $this->dsn['port'];?> 
params.username=<?php echo $this->dsn['uname'];?> 
params.password=<?php echo $this->dsn['upass'];?> 
params.dbname=<?php echo $this->dsn['dbname'];?> 
