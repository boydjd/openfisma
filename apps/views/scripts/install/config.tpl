
[database]
adapter=mysqli 
params.host=<?php echo $this->dsn['host']; ?> 
params.port=<?php echo $this->dsn['port'];?> 
params.username=<?php echo $this->dsn['name_c'];?> 
params.password=<?php echo $this->dsn['pass_c'];?> 
params.dbname=<?php echo $this->dsn['dbname'];?> 
