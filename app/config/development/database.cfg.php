<?php 
 $_config = array (
  'database' => 
  array (
    'default' => 
    array (
      'driver' => 'mysql',
      'host' => '192.168.3.254',
      'port' => '3306',
      'user' => 'root',
      'password' => '123456',
      'db_name' => 'xphp_test',
      'charset' => 'utf8',
      'timeout' => 10,
      'show_field_info' => false,
    ),
    'log_db' => 
    array (
      'driver' => 'mysql',
      'host' => '192.168.3.254',
      'port' => '3306',
      'user' => 'root',
      'password' => '123456',
      'db_name' => 'xphp_test',
      'charset' => 'utf8',
      'timeout' => 10,
      'show_field_info' => false,
    ),
  ),
  'config_map_class' => 
  array (
    'default' => 
    array (
    ),
    'log_db' => 
    array (
      0 => 'UnitTestUnitModel',
      1 => 'UnitTestUnitModel',
    ),
  ),
);

return $_config;
