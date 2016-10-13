<?php


namespace RedisPlugin;


use \Exception;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\Model\Transaction\Manager;


class Service implements ServiceInterface
{

    /**
     * @var \Phalcon\Di\FactoryDefault
     */
    protected $di = null;

    /**
     * Service constructor.
     * @param \Phalcon\Di\FactoryDefault $di
     */
    public function __construct(\Phalcon\Di\FactoryDefault $di)
    {
        $this->setDI($di);
        $this->registration();
    }

    /**
     * @return \Phalcon\Di\FactoryDefault
     */
    public function getDI()
    {
        return $this->options;
    }

    /**
     * @param \Phalcon\Di\FactoryDefault $options
     */
    public function setDI(\Phalcon\Di\FactoryDefault $options)
    {
        $this->options = $options;
    }

    /**
     * service registration
     */
    public function registration()
    {
        $di     = $this->getDI();
        $config = $di->get("config");

        foreach ($config->get("database") as $db => $arguments)
        {

            // set
            $di->setShared($db, function () use ($arguments)
            {
                return new DbAdapter($arguments->toArray());
            });

            // transaction
            if (isset($arguments['transaction']) && $arguments['transaction']) {

                $service = $arguments["host"] .":". $arguments["port"];
                $di->setShared($service, function() use ($db)
                {
                    $manager = new Manager();
                    if ($db !== null) {
                        $manager->setDbService($db);
                    }
                    return $manager;
                });

            }
        }
    }

}