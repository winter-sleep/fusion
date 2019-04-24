<?php
namespace Viol\Fusion\Route;

use Psr\Http\Message\RequestInterface;

class Router
{
    const METHOD_PATCH = 'patch';
    const METHOD_GET = 'get';
    const METHOD_POST = 'post';
    const METHOD_PUT = 'put';
    const METHOD_DELETE = 'delete';

    private static $routers = [];
    private static $namedRouters = [];
    private static $patternPrefix = '';
    public static $defaultHandle;

    public $handle = null;
    public $params = [];
    public $handleMethod;

    /**
     * 根据请求方法和模式实例化 Router 对象
     *
     * @param string $method
     * @param string $pattern
     * @return static
     */
    public static function map($method, $pattern)
    {
        $router = new static($method, $pattern);
        static::$routers[] = &$router;
        return $router;
    }

    public static function group($pattern, $callback)
    {
        static::$patternPrefix = $pattern;
        call_user_func($callback);
        static::$patternPrefix = '';
    }

    /**
     * 扫描路由表，激活回调函数
     *
     * @return response|null
     */
    public static function scan()
    {
        $pathInfo = array_key_exists('PATH_INFO', $_SERVER) ? $_SERVER['PATH_INFO'] : '';
        foreach (static::$routers as $router) {

            if (preg_match($router->getPattern(), $pathInfo, $matched)) {

                if ($_SERVER['REQUEST_METHOD'] == $router->getMethod()) {

                    array_shift($matched);
                    $router->params = $matched;

                    if ($router->handle instanceof \Closure) {
                        return call_user_func_array($router->handle, $router->params);
                    }

                    if (is_callable(static::$defaultHandle)) {
                        return call_user_func(static::$defaultHandle, $router);
                    }

                    return null;

                } else {
                    throw new \Exception('Method not alow.');
                }
            }

        }

        throw new \Exception('404 Not Found.');
    }

    public static function onMatch($callback)
    {
        static::$defaultHandle = $callback;
    }


    public function __construct($method, $pattern)
    {
        $this->method = $method;
        $this->pattern = static::$patternPrefix . $pattern;
    }

    /**
     * 解析并返回路由的匹配模式
     *
     * @return string
     */
    public function getPattern()
    {
        return '/' . str_replace(
            ['/', ':num', ':any'],
            ['\\/', '[0-9]+', '[a-zA-Z0-9]+'],
            $this->pattern
        ) . '\\/?$/';
    }

    public function getMethod()
    {
        return strtoupper($this->method);
    }

    /**
     * 设置路由名称，并储存在 routers 数组中
     *
     * @param string $name
     * @return static
     */
    public function name($name)
    {
        static::$namedRouters[$name] = &$this;
        return $this;
    }

    public function handle($callbackOrClass, $method = null)
    {
        $this->handle = $callbackOrClass;
        $this->handleMethod = $method;
    }
}
