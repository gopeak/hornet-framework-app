<?php

namespace main\app\ctrl;

use main\app\protocol\ajax;

/**
 * 控制器基类
 *
 * @author user
 *
 */
class BaseCtrl
{


    /**
     * 模板引擎对象
     * @var
     */
    protected $tpl;

    /**
     * 模板引擎加载器
     * @var
     */
    protected $loader;

    /**
     * 错误数组
     * @var array
     */
    protected $error = [];

    /**
     * 全局模板变量数组
     * @var array
     */
    public $gTplVars = [];

    /**
     * 页面标题.
     * @var string
     */
    public $page_title = '';


    public function __construct()
    {
        if (count($_GET) > 100) {
            throw new \Exception('GET参数过多', 300);
        }

        if (count($_POST) > 100) {
            throw new \Exception('POST参数过多', 400);
        }

        if (count($_COOKIE) > 50) {
            throw new \Exception('COOKIE参数过多', 500);
        }

    }

    public function addGVar($key, $value)
    {
        $this->gTplVars[$key] = $value;
    }

    public function render($tpl, $datas = [], $partial = false)
    {
        // 向视图传入通用的变量
        $this->addGVar('site_url', ROOT_URL);
        $this->addGVar('public_url', PUBLIC_URL);
        $this->addGVar('version', VERSION);
        $this->addGVar('app_name', SITE_NAME);

        $datas = array_merge($this->gTplVars, $datas);
        ob_start();
        ob_implicit_flush(false);
        extract($datas, EXTR_PREFIX_SAME, 'tpl_');
        require_once VIEW_PATH . $tpl;
        if (!$partial && ENBALE_DEBUG) {

            $sql_logs = \main\lib\MyPdo::$sql_logs;
            include_once VIEW_PATH . 'debug.php';
            unset($sql_logs);
        }
        echo ob_get_clean();
        exit;
    }


    /**
     * 重定向到一个新的url
     * @param  string $url
     */
    public function redirect($url)
    {
        $this->cleanOutput();
        header('Location:' . $url);
        exit;
    }

    public function cleanOutput()
    {
        for ($level = ob_get_level(); $level > 0; --$level) {
            if (!@ob_end_clean()) {
                ob_clean();
            }
        }
    }

    /**
     * 通过ajax 协议返回格式
     * @param array $data
     * @param string $msg
     * @param int $code
     */
    public function ajaxSuccess($msg='', $data = [], $code=200)
    {
        global $framework;
        $ajax_protocol_class = sprintf("main\\%s\\protocol\\%s", $framework->currentApp, $framework->ajaxProtocolClass);
        if (class_exists($ajax_protocol_class)) {
            $ajaxProtocol = new $ajax_protocol_class();
        } else {
            $ajaxProtocol = new \framework\Protocol\Ajax();
        }
        $ajaxProtocol->builder($code, $data, $msg);
        $result = $ajaxProtocol->getResponse();

        if ($framework->enableReflectMethod) {
            $function = '';
            $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            if (isset($traces[1]['class'])) {
                $function = $traces[1]['function'];
            }
            $reflectMethod = new \ReflectionMethod($this, $function);
            $return_obj = json_decode(json_encode($data));
            $this->validReturnJson($reflectMethod, $ajaxProtocol, $return_obj, $result);
        }

        @header('Content-Type:application/json');
        echo $result;
    }

    /**
     * 检验返回值
     *
     * @param \ReflectionMethod $reflectMethod 反射方法
     * @param object $returnObj Object
     * @param string $jsonStr Match Json string
     *
     * @return void
     */
    private function validReturnJson($reflectMethod, $ajaxProtocol, $returnObj, &$jsonStr)
    {
        // 检查属性是否存在并且类型一致
        $commentString = $reflectMethod->getDocComment();
        if (!$commentString) {
            return;
        }
        $pattern = "#@require_type\s+([^*/].*)#";
        preg_match_all($pattern, $commentString, $matches, PREG_PATTERN_ORDER);
        if (isset($matches[1][0])) {
            $requireObj = json_decode($matches[1][0]);
            if ($requireObj !== null) {
                list($validRet, $validMsg) = $this->compareReturnJson($requireObj, $returnObj);
                if (!$validRet) {
                    $ajaxProtocol->builder('600', ['key' => 'return_type_err', 'value' => $validMsg]);
                    $jsonStr = $ajaxProtocol->getResponse();
                }
            }
        }
    }

    /**
     * 检查返回值是否符合格式要求
     *
     * @param object $requireTypeObj type object
     * @param object $returnObj return object
     *
     * @return array
     */
    private function compareReturnJson($requireTypeObj, $returnObj)
    {
        // 检查属性是否存在并且类型一致
        if (empty($requireTypeObj)
            && gettype($requireTypeObj) != gettype($returnObj)
        ) {
            return [false, 'expect  type is ' . gettype($returnObj) . ', but get ' . gettype($requireTypeObj)];
        }
        if (!empty($requireTypeObj) && is_object($requireTypeObj)) {
            foreach ($requireTypeObj as $k => $v) {
                if (!isset($returnObj->$k)) {
                    return [false, 'property:' . $k . ' not exist'];
                }
                if (gettype($v) != gettype($returnObj->$k)) {
                    return [false, 'expect ' . $k . ' type is ' . gettype($v) . ', but get ' . gettype($returnObj->$k)];
                }
                if (!empty($returnObj->$k) && (is_array($returnObj->$k) || is_object($returnObj->$k))) {
                    list($ret, $msg) = $this->compareReturnJson($v, $returnObj->$k);
                    if (!$ret) {
                        return [$ret, $msg];
                    }
                }
            }
        }
        return [true, ''];
    }

    /**
     * 通过ajax 协议返回异常格式
     * @param $msg
     * @param array $data
     * @param int $code
     */
    public function ajaxFailed($msg, $data = [], $code = 0)
    {
        header('Content-Type:application/json');
        $ajaxProtocol = new ajax();
        $ajaxProtocol->builder($code, $data, $msg);
        echo $ajaxProtocol->getResponse();
        exit;
    }

    /**
     * 跳转至信息展示页面
     * @param string $title 标题
     * @param string $content 内容
     * @param array $links 链接
     * @param string $icon 图标样式
     */
    public function info($title = '信息提示',
                         $content = '',
                         $links = ['type' => 'link', 'link' => ROOT_URL, 'title' => '回到首页'],
                         $icon = 'icon-font-ok')
    {
        $arr = [];

        $arr['_title'] = $title;
        $arr['links'] = $links;
        $arr['content'] = $content;
        $arr['icon'] = $icon;
        $this->render('common/com-info.php', $arr);
    }

    /**
     * 跳转至警告页面
     * @param string $title 标题
     * @param string $content 内容
     * @param array $links 链接
     */
    public function warn($title = '警告!',
                         $content = '',
                         $links = ['type' => 'link', 'link' => ROOT_URL, 'title' => '回到首页'])
    {
        $this->info($title, $content, $links, 'icon-font-fail');
    }

    /**
     * 跳转至错误页面
     * @param string $title 标题
     * @param string $content 内容
     * @param array $links 链接
     */
    public function error($title = '错误提示!',
                          $content = '',
                          $links = ['type' => 'link', 'link' => ROOT_URL, 'title' => '回到首页'])
    {
        $this->info($title, $content, $links, 'icon-font-fail');
    }


}