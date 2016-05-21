<?php

	/**
     * 确定请求的完整 URL
     *
     * 几个示例：
     *
     * <ul>
     *   <li>请求 http://www.example.com/index.php?controller=posts&action=create</li>
     *   <li>返回 /index.php?controller=posts&action=create</li>
     * </ul>
     * <ul>
     *   <li>请求 http://www.example.com/news/index.php?controller=posts&action=create</li>
     *   <li>返回 /news/index.php?controller=posts&action=create</li>
     * </ul>
     * <ul>
     *   <li>请求 http://www.example.com/index.php/posts/create</li>
     *   <li>返回 /index.php/posts/create</li>
     * </ul>
     * <ul>
     *   <li>请求 http://www.example.com/news/show/id/1</li>
     *   <li>返回 /news/show/id/1</li>
     * </ul>
     *
     * 此方法参考 Zend Framework 实现。
     *
     * @return string 请求的完整 URL
     */
    function requestUri()
    {
        if (isset($_SERVER['HTTP_X_REWRITE_URL']))
        {
            $uri = $_SERVER['HTTP_X_REWRITE_URL'];
        }
        elseif (isset($_SERVER['REQUEST_URI']))
        {
            $uri = $_SERVER['REQUEST_URI'];
        }
        elseif (isset($_SERVER['ORIG_PATH_INFO']))
        {
            $uri = $_SERVER['ORIG_PATH_INFO'];
            if (! empty($_SERVER['QUERY_STRING']))
            {
                $uri .= '?' . $_SERVER['QUERY_STRING'];
            }
        }
        else
        {
            $uri = '';
        }
        return $uri;
    }

    /**
     * 返回不包含任何查询参数的 URI（但包含脚本名称）
     *
     * 几个示例：
     *
     * <ul>
     *   <li>请求 http://www.example.com/index.php?controller=posts&action=create</li>
     *   <li>返回 /index.php</li>
     * </ul>
     * <ul>
     *   <li>请求 http://www.example.com/news/index.php?controller=posts&action=create</li>
     *   <li>返回 /news/index.php</li>
     * </ul>
     * <ul>
     *   <li>请求 http://www.example.com/index.php/posts/create</li>
     *   <li>返回 /index.php</li>
     * </ul>
     * <ul>
     *   <li>请求 http://www.example.com/news/show/id/1</li>
     *   <li>返回 /news/show/id/1</li>
     *   <li>假设使用了 URL 重写，并且 index.php 位于根目录</li>
     * </ul>
     *
     * 此方法参考 Zend Framework 实现。
     *
     * @return string 请求 URL 中不包含查询参数的部分
     */
    function baseUri()
    {

        $filename = basename($_SERVER['SCRIPT_FILENAME']);

        if (basename($_SERVER['SCRIPT_NAME']) === $filename)
        {
            $url = $_SERVER['SCRIPT_NAME'];
        }
        elseif (basename($_SERVER['PHP_SELF']) === $filename)
        {
            $url = $_SERVER['PHP_SELF'];
        }
        elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename)
        {
            $url = $_SERVER['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
        }
        else
        {
            // Backtrack up the script_filename to find the portion matching
            // php_self
            $path = $_SERVER['PHP_SELF'];
            $segs = explode('/', trim($_SERVER['SCRIPT_FILENAME'], '/'));
            $segs = array_reverse($segs);
            $index = 0;
            $last = count($segs);
            $url = '';
            do
            {
                $seg = $segs[$index];
                $url = '/' . $seg . $url;
                ++ $index;
            } while (($last > $index) && (false !== ($pos = strpos($path, $url))) && (0 != $pos));
        }

        // Does the baseUrl have anything in common with the request_uri?
        $request_uri = requestUri();

        if (0 === strpos($request_uri, $url))
        {
            // full $url matches
            return $url;
        }

        if (0 === strpos($request_uri, dirname($url)))
        {
            // directory portion of $url matches
            return rtrim(dirname($url), '/') . '/';
        }

        if (! strpos($request_uri, basename($url)))
        {
            // no match whatsoever; set it blank
            return '';
        }

        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of baseUrl. $pos !== 0 makes sure it is not matching a value
        // from PATH_INFO or QUERY_STRING
        if ((strlen($request_uri) >= strlen($url))
            && ((false !== ($pos = strpos($request_uri, $url)))
            && ($pos !== 0)))
        {
            $url = substr($request_uri, 0, $pos + strlen($url));
        }

        return rtrim($url, '/') . '/';
    }

    /**
     * 返回请求 URL 中的基础路径（不包含脚本名称）
     *
     * 几个示例：
     *
     * <ul>
     *   <li>请求 http://www.example.com/index.php?controller=posts&action=create</li>
     *   <li>返回 /</li>
     * </ul>
     * <ul>
     *   <li>请求 http://www.example.com/news/index.php?controller=posts&action=create</li>
     *   <li>返回 /news/</li>
     * </ul>
     * <ul>
     *   <li>请求 http://www.example.com/index.php/posts/create</li>
     *   <li>返回 /</li>
     * </ul>
     * <ul>
     *   <li>请求 http://www.example.com/news/show/id/1</li>
     *   <li>返回 /</li>
     * </ul>
     *
     * @return string 请求 URL 中的基础路径
     */
    function baseDir()
    {

        $base_uri = baseUri();
        if (substr($base_uri, - 1, 1) == '/')
        {
            $base_dir = $base_uri;
        }
        else
        {
            $base_dir = dirname($base_uri);
        }
        return rtrim($base_dir, '/\\') . '/';
    }

    /**
     * 输出变量的内容
     *
     * 如果启用了 FirePHP 支持，将输出到浏览器的 FirePHP 窗口中，不影响页面输出。
     *
     * 可以使用 dump() 这个简写形式。
     *
     * @code php
     * dump($vars, '$vars current values');
     * @endcode
     *
     * @param mixed $vars 要输出的变量
     * @param string $label 标签
     * @param boolean $return 是否返回输出内容
     */
    function dump($vars, $label = null, $return = false)
    {

        if (ini_get('html_errors'))
        {
            $content = "<pre>\n";
            if ($label !== null && $label !== '')
            {
                $content .= "<strong>{$label} :</strong>\n";
            }
            $content .= htmlspecialchars(print_r($vars, true));
            $content .= "\n</pre>\n";
        }
        else
        {
            $content = "\n";
            if ($label !== null && $label !== '')
            {
                $content .= $label . " :\n";
            }
            $content .= print_r($vars, true) . "\n";
        }
        if ($return)
        {
            return $content;
        }

        echo $content;
        return null;
    }

    function redirectAlert($url, $message = '')
    {
        if( $message == '' )
        {
            $response = "<script>window.location.href='" . $url . "';</script>";
        } else {
            $response = "<script>alert('" . $message . "'); window.location.href='" . $url . "';</script>";
        }
        echo $response;
        exit();
    }

    function redirect($url)
    {
        $response = "<script>window.location.href='" . $url . "';</script>";
        echo $response;
        exit();
    }
    /**
     * 获得用户端的真实ip
     */
    function getRealIp()
    {
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && !empty($_SERVER["HTTP_X_FORWARDED_FOR"])) 
        {
            list($_SERVER["REMOTE_ADDR"]) = explode(',', $_SERVER["HTTP_X_FORWARDED_FOR"]);
            $_SERVER["REMOTE_ADDR"] = trim($_SERVER["REMOTE_ADDR"]);
        }
        
        return !empty($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '';
    }

        
    /**
    * bcaddFloat
    * 将二个高精确度数字相加。
    * @access   public
    * @param    $left_operand
    * @param    $right_operand
    * @param    $scale
    * @return   float
    */
    if ( ! function_exists('bcaddFloat'))
    {
        function bcaddFloat($left_operand, $right_operand, $scale = 0)
        {
            return floatval(bcadd($left_operand, $right_operand, $scale));
        }
    }

    /**
    * bcdivFloat
    * 将二个高精确度数字相除。
    * @access   public
    * @param    $left_operand
    * @param    $right_operand
    * @param    $scale
    * @return   float
    */
    if ( ! function_exists('bcdivFloat'))
    {
        function bcdivFloat($left_operand, $right_operand, $scale = 0)
        {
            return floatval(bcdiv($left_operand, $right_operand, $scale));
        }
    }

    /**
    * bcmulFloat
    * 将二个高精确度数字相乘。
    * @access   public
    * @param    $left_operand
    * @param    $right_operand
    * @param    $scale
    * @return   float
    */
    if ( ! function_exists('bcmulFloat'))
    {
        function bcmulFloat($left_operand, $right_operand, $scale = 0)
        {
            return floatval(bcmul($left_operand, $right_operand, $scale));
        }
    }

    /**
    * bcsubFloat
    * 将二个高精确度数字相减。
    * @access   public
    * @param    $left_operand
    * @param    $right_operand
    * @param    $scale
    * @return   float
    */
    if ( ! function_exists('bcsubFloat'))
    {
        function bcsubFloat($left_operand, $right_operand, $scale = 0)
        {
            return floatval(bcsub($left_operand, $right_operand, $scale));
        }
    }