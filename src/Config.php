<?php
namespace liuguang\mvc;

class Config
{

    private $configArray;

    public function __construct(array $configArray)
    {
        $this->configArray = $configArray;
    }

    public static function loadFromPhpFile(string $path): Config
    {
        $configArray = [];
        if (is_file($path)) {
            $configArray = include $path;
        }
        return new self($configArray);
    }

    public function writeToPhpFile(string $path): void
    {
        $content = '<?php //created at ' . date('Y-m-d H:i:s') . PHP_EOL;
        $content .= ('return ' . var_export($this->configArray, true) . ' ;');
        file_put_contents($path, $content);
    }

    public function toArray(): array
    {
        return $this->configArray;
    }

    public function merge(Config $config): Config
    {
        $newArray = $config->toArray();
        $this->configArray = array_merge($this->configArray, $newArray);
        return $this;
    }

    public function has(string $key): bool
    {
        $keyNames = explode('.', $key);
        $arr = &$this->configArray;
        while (count($keyNames) > 1) {
            $currentKey = array_shift($keyNames);
            if (array_key_exists($currentKey, $arr)) {
                if (! is_array($arr[$currentKey])) {
                    return false;
                }
            } else {
                return false;
            }
            $arr = &$arr[$currentKey];
        }
        if (! is_array($arr)) {
            return false;
        } else {
            return array_key_exists(array_shift($keyNames), $arr);
        }
    }

    public function get(string $key, $default = null)
    {
        $keyNames = explode('.', $key);
        $arr = &$this->configArray;
        while (count($keyNames) > 1) {
            $currentKey = array_shift($keyNames);
            if (array_key_exists($currentKey, $arr)) {
                if (! is_array($arr[$currentKey])) {
                    return $default;
                }
            } else {
                return $default;
            }
            $arr = &$arr[$currentKey];
        }
        if (! is_array($arr)) {
            return $default;
        }
        $currentKey = array_shift($keyNames);
        if (array_key_exists($currentKey, $arr)) {
            return $arr[$currentKey];
        } else {
            return $default;
        }
    }

    public function set(string $key, $value): void
    {
        $keyNames = explode('.', $key);
        $arr = &$this->configArray;
        while (count($keyNames) > 1) {
            $currentKey = array_shift($keyNames);
            if (array_key_exists($currentKey, $arr)) {
                if (! is_array($arr[$currentKey])) {
                    $arr[$currentKey] = [];
                }
            } else {
                $arr[$currentKey] = [];
            }
            $arr = &$arr[$currentKey];
        }
        $arr[array_shift($keyNames)] = $value;
    }

    public function delete(string $key): void
    {
        $keyNames = explode('.', $key);
        $arr = &$this->configArray;
        while (count($keyNames) > 1) {
            $currentKey = array_shift($keyNames);
            if (array_key_exists($currentKey, $arr)) {
                if (! is_array($arr[$currentKey])) {
                    return;
                }
            } else {
                return;
            }
            $arr = &$arr[$currentKey];
        }
        unset($arr[array_shift($keyNames)]);
    }

    public function clear(): void
    {
        $this->configArray = [];
    }
}

