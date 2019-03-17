# i2cserver
TCP interface to I2C busses.    
Features:
* list busses
* list devices on bus
* read data
* write data
* special handling for known devices (currently ccs811)
* i2ctool for cli usage

Todo:
* add router script for `php -s` mode to allow http requests
* add install scripts
* crosscompile helper tool (currently armv71)
* run server a daemon

Ideas:
* allow clients to define timers and conditional callbacks
