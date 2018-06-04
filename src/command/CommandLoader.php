<?php
namespace liuguang\mvc\command;

use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class CommandLoader implements CommandLoaderInterface
{

    private $factories;

    /**
     *
     * @param callable[] $factories
     *            Indexed by command names
     */
    public function __construct(array $factories)
    {
        $this->factories = $factories;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function has($name)
    {
        return isset($this->factories[$name]);
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function get($name)
    {
        if (! isset($this->factories[$name])) {
            throw new CommandNotFoundException(sprintf('Command "%s" does not exist.', $name));
        }
        $factory = $this->factories[$name];
        return $factory();
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getNames()
    {
        return array_keys($this->factories);
    }

    /**
     * 设置命令回调
     *
     * @param string $name
     *            命令名称
     * @param callable $callback            
     * @return void
     */
    public function set(string $name, callable $callback)
    {
        $this->factories[$name] = $callback;
    }

    /**
     * 删除命令回调
     *
     * @param string $name
     *            命令名称
     * @return void
     */
    public function remove(string $name)
    {
        unset($this->factories[$name]);
    }
}