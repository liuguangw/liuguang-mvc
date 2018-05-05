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
        return array_key_exists($key, $this->configArray);
    }

    public function get(string $key, $default = null)
    {
        if ($this->has($key)) {
            return $this->configArray[$key];
        } else {
            return $default;
        }
    }

    public function set(string $key, $value): void
    {
        $this->configArray[$key] = $value;
    }

    public function delete(string $key): void
    {
        unset($this->configArray[$key]);
    }

    public function clear(): void
    {
        $this->configArray = [];
    }
}

