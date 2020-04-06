<?php
/**
 * @link        http://www.phpcorner.net
 * @author      qingbing<780042175@qq.com>
 * @copyright   Chengdu Qb Technology Co., Ltd.
 */

namespace Zf\Cache;


use Zf\Cache\Abstracts\ACache;
use Zf\Cache\Traits\TMultiCache;
use Zf\Helper\File;

/**
 * 定义缓存目录
 */
defined("ZF_RUNTIME") or define("ZF_RUNTIME", dirname(realpath('.')) . '/runtime');

/**
 * @author      qingbing<780042175@qq.com>
 * @describe    文件缓存管理
 *
 * Class FileCache
 * @package Zf\Cache
 */
class FileCache extends ACache
{
    /**
     * 通用的多缓存管理
     */
    use TMultiCache;

    /**
     * @describe    缓存命名空间
     *
     * @var string
     */
    public $namespace = 'cache';
    /**
     * @describe    id前缀
     *
     * @var string
     */
    public $prefix = 'zf_';
    /**
     * @describe    缓存文件后缀
     *
     * @var string
     */
    public $suffix = 'bat';
    /**
     * @describe    describe
     *
     * @var string
     */
    private $_path;

    /**
     * @describe    获取缓存存放路径
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->_path;
    }

    /**
     * @describe    设置缓存存放路径
     *
     * @param string $path
     *
     * @return $this
     */
    public function setPath(string $path)
    {
        $this->_path = $path;
        return $this;
    }

    /**
     * @describe    属性赋值后执行函数
     *
     * @throws \Zf\Helper\Exceptions\Exception
     */
    public function init()
    {
        if (null === $this->_path) {
            $this->_path = ZF_RUNTIME . '/' . $this->namespace;
            if (!is_dir($this->_path)) {
                File::mkdir($this->_path);
            }
        }
    }

    /**
     * @describe    获取缓存文件路径
     *
     * @param string $id
     *
     * @return string
     */
    protected function getFile(string $id): string
    {
        return $this->getPath() . '/' . $id . '.' . $this->suffix;
    }

    /**
     * @describe    获取缓存id
     *
     * @param mixed $key
     *
     * @return string
     */
    protected function buildId($key): string
    {
        return md5($this->prefix . (is_string($key) ? $key : json_encode($key)));
    }

    /**
     * @describe    通过缓存id获取信息
     *
     * @param string $id
     *
     * @return mixed
     */
    protected function getValue($id)
    {
        $file = $this->getFile($id);
        if (!file_exists($file)) {
            return null;
        }
        if (filemtime($file) < time()) { // filemtime : 文件最后修改时间
            @unlink($file);
            return null;
        }
        return file_get_contents($file);
    }

    /**
     * @describe    设置缓存id的信息
     *
     * @param string $id
     * @param string $value
     * @param int $ttl
     *
     * @return bool
     */
    protected function setValue(string $id, $value, $ttl): bool
    {
        $file = $this->getFile($id);
        if (file_put_contents($file, $value, LOCK_EX)) {
            $ttl = $ttl + time();
            @chmod($file, 0777);
            if (-1 === $ttl) {
                return true;
            }
            if ($ttl > 0) {
                return @touch($file, $ttl);
            }
            if (file_exists($file)) {
                return File::unlink($file);
            }
        }
        return false;
    }

    /**
     * @describe    删除缓存信息
     *
     * @param string $id
     *
     * @return bool
     */
    protected function deleteValue(string $id): bool
    {
        $file = $this->getFile($id);
        if (file_exists($file)) {
            return File::unlink($file);
        }
        return true;
    }

    /**
     * @describe    清理当前命名空间的缓存
     *
     * @return bool
     */
    protected function clearValues(): bool
    {
        return File::rmdir($this->_path, true);
    }

    /**
     * @describe    判断缓存是否存在
     *
     * @param string $id
     *
     * @return bool
     */
    protected function exist(string $id): bool
    {
        $file = $this->getFile($id);
        if (!file_exists($file)) {
            return false;
        }
        if (filemtime($file) < time()) { // filemtime : 文件最后修改时间
            @unlink($file);
            return false;
        }
        return true;
    }
}