<?php
namespace Rap2hpoutre\LaravelLogViewer;

use Psr\Log\LogLevel;

/**
 * Class LaravelLogViewer
 * @package Rap2hpoutre\LaravelLogViewer
 */
class LaravelLogViewer
{
    /**
     * @var string file
     */
    private static $file;

    private static $levels_classes = [
        'debug' => 'info',
        'info' => 'info',
        'notice' => 'info',
        'warning' => 'warning',
        'error' => 'danger',
        'critical' => 'danger',
        'alert' => 'danger',
        'emergency' => 'danger',
        'processed' => 'info',
    ];

    private static $levels_imgs = [
        'debug' => 'info',
        'info' => 'info',
        'notice' => 'info',
        'warning' => 'warning',
        'error' => 'warning',
        'critical' => 'warning',
        'alert' => 'warning',
        'emergency' => 'warning',
        'processed' => 'info'
    ];

    /**
     * Log levels that are used
     * @var array
     */
    private static $log_levels = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
        'processed'
    ];

    const MAX_FILE_SIZE = 52428800; // Why? Uh... Sorry

    /**
     * 设置log的目录
     */
    public static function logpath()
    {
        return $logsPath = config('log-viewer.log_path') ?: storage_path('logs');
    }

    /**
     * @param string $file
     */
    public static function setFile($file)
    {
        $file = self::pathToLogFile($file);

        if (app('files')->exists($file)) {
            self::$file = $file;
        }
    }

    /**
     * @param string $file
     * @return string
     * @throws \Exception
     */
    public static function pathToLogFile($file)
    {
        $logsPath = self::logpath();

        if (app('files')->exists($file)) { // try the absolute path
            return $file;
        }
        
        $file = $logsPath . '/' . $file;

        // check if requested file is really in the logs directory
//        if (dirname($file) !== $logsPath) {
//            throw new \Exception('No such log file');
//        }

        return $file;
    }

    /**
     * @return string
     */
    public static function getFileName()
    {
        return basename(self::$file);
    }

    /**
     * 获取执行操作时的文件名
     * @return mixed
     */
    public static function getActionFileName()
    {
        return str_replace(self::logpath().'/', '', self::$file);
    }

    /**
     * @return array
     */
    public static function all()
    {
        $log = array();

        $pattern = '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\].*/';

        if (!self::$file) {
            $log_file = self::getFiles();
            if(!count($log_file)) {
                return [];
            }
            self::$file = $log_file[0];
        }

        if (app('files')->size(self::$file) > self::MAX_FILE_SIZE) return null;

        $file = app('files')->get(self::$file);

        preg_match_all($pattern, $file, $headings);

        if (!is_array($headings)) return $log;

        $log_data = preg_split($pattern, $file);

        if ($log_data[0] < 1) {
            array_shift($log_data);
        }

        foreach ($headings as $h) {
            for ($i=0, $j = count($h); $i < $j; $i++) {
                foreach (self::$log_levels as $level) {
                    if (strpos(strtolower($h[$i]), '.' . $level) || strpos(strtolower($h[$i]), $level . ':')) {

                        preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\](?:.*?(\w+)\.|.*?)' . $level . ': (.*?)( in .*?:[0-9]+)?$/i', $h[$i], $current);
                        if (!isset($current[3])) continue;

                        $log[] = array(
                            'context' => $current[2],
                            'level' => $level,
                            'level_class' => self::$levels_classes[$level],
                            'level_img' => self::$levels_imgs[$level],
                            'date' => $current[1],
                            'text' => $current[3],
                            'in_file' => isset($current[4]) ? $current[4] : null,
                            'stack' => preg_replace("/^\n*/", '', $log_data[$i])
                        );
                    }
                }
            }
        }
        return array_reverse($log);
    }

    /**
     * @param bool $basename
     * @return array
     */
    public static function getFiles($basename = false)
    {
        $files = array();
        $path = self::logpath();
        $files = LaravelLogViewer::getFileArray($path);//dd($files);
//        $files = glob(storage_path() . '/logs/*.log');
        $files = array_reverse($files);
        $files = array_filter($files, 'is_file');
        if ($basename && is_array($files)) {
            foreach ($files as $k => $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) != 'log') {
                    unset($files[$k]);
                }else{
                    $files[$k] = str_replace($path.'/', '', $file);//.basename($file);
                }
            }
        }
        return array_values($files);
    }

    public static function getFileArray($path)
    {
        $files = [];
        if(!is_dir($path)) {
            return $files;
        }
        $handle = opendir($path);
        if($handle) {
            while(false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    $filename = $path . "/"  . $file;
                    if(is_file($filename)) {
                        $files[] = $filename;
                    }else {
                        $files = array_merge($files, LaravelLogViewer::getFileArray($filename));
                    }
                }
            }   //  end while
            closedir($handle);
        }
        return $files;
    }
// 该方法为备份，为修改成下拉列表显示准备的
    public static function getFileArrayBack($path, $level=0)
    {
        $files = [];
        if(!is_dir($path)) {
            return $files;
        }
        $handle = opendir($path);
        if($handle) {
            while(false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    $filename = $path . "/"  . $file;
                    if(is_file($filename)) {
                        $files[$level][] = $filename;
                    }else {
                        $files = array_merge($files, LaravelLogViewer::getFileArrayBack($filename, $level++));
                    }
                }
            }   //  end while
            closedir($handle);
        }
        return $files;
    }
}
