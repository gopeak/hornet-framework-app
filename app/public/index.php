<?php
/**
 * xphp bootstrap file
 */

require_once '../globals.php';
require_once '../../../hornet-framework/src/framework/bootstrap.php';
// 初始化开发框架基本设置
$config = new \stdClass();
$config->currentApp = APP_NAME;
$config->appPath = APP_PATH;
$config->appStatus = APP_STATUS;
$config->enableTrace = ENABLE_TRACE;
$config->enableXhprof = ENABLE_XHPROF;
$config->xhprofRoot = APP_PATH.'public/xhprof/';
$config->xhprofRate = XHPROF_RATE;
$config->enableWriteReqLog = WRITE_REQUEST_LOG;
$config->enableSecurityMap = SECURITY_MAP_ENABLE;
$config->exceptionPage = VIEW_PATH.'exception.php';
$config->ajaxProtocolClass  = 'ajax';
$config->enableReflectMethod = ENABLE_REFLECT_METHOD;

// 实例化开发框架对象
$framework = new  framework\HornetEngine($config);
// 执行路由分发
$framework->route();
