<?php

declare(strict_types = 1);

namespace Graphpinator;

final class Graphpinator implements \Psr\Log\LoggerAwareInterface
{
    use \Nette\SmartObject;

    public static bool $validateSchema = true;
    private \Graphpinator\Module\ModuleSet $modules;
    private \Psr\Log\LoggerInterface $logger;
    private \Graphpinator\Parser\Parser $parser;
    private \Graphpinator\Normalizer\Normalizer $normalizer;
    private \Graphpinator\Normalizer\Finalizer $finalizer;
    private \Graphpinator\Resolver\Resolver $resolver;

    public function __construct(
        \Graphpinator\Typesystem\Schema $schema,
        private bool $catchExceptions = false,
        ?\Graphpinator\Module\ModuleSet $modules = null,
        ?\Psr\Log\LoggerInterface $logger = null,
    )
    {
        $schema->getQuery()->addMetaField(\Graphpinator\Typesystem\Field\ResolvableField::create(
            '__schema',
            $schema->getContainer()->getType('__Schema')->notNull(),
            static function() use ($schema) : \Graphpinator\Typesystem\Schema {
                return $schema;
            },
        ));
        $schema->getQuery()->addMetaField(\Graphpinator\Typesystem\Field\ResolvableField::create(
            '__type',
            $schema->getContainer()->getType('__Type'),
            static function($parent, string $name) use ($schema) : ?\Graphpinator\Typesystem\Contract\Type {
                return $schema->getContainer()->getType($name);
            },
        )->setArguments(new \Graphpinator\Typesystem\Argument\ArgumentSet([
            \Graphpinator\Typesystem\Argument\Argument::create('name', \Graphpinator\Typesystem\Container::String()->notNull()),
        ])));

        $this->modules = $modules instanceof \Graphpinator\Module\ModuleSet
            ? $modules
            : new \Graphpinator\Module\ModuleSet([]);
        $this->logger = $logger instanceof \Psr\Log\LoggerInterface
            ? $logger
            : new \Psr\Log\NullLogger();
        $this->parser = new \Graphpinator\Parser\Parser();
        $this->normalizer = new \Graphpinator\Normalizer\Normalizer($schema);
        $this->finalizer = new \Graphpinator\Normalizer\Finalizer();
        $this->resolver = new \Graphpinator\Resolver\Resolver();
    }

    public function run(\Graphpinator\Request\RequestFactory $requestFactory) : \Graphpinator\Result
    {
        try {
            $request = $requestFactory->create();
            $result = $request;

            $this->logger->debug($request->getQuery());

            foreach ($this->modules as $module) {
                $result = $module->processRequest($request);

                if (!$result instanceof \Graphpinator\Request\Request) {
                    break;
                }
            }

            if ($result instanceof \Graphpinator\Request\Request) {
                $result = $this->parser->parse(new \Graphpinator\Source\StringSource($request->getQuery()));

                foreach ($this->modules as $module) {
                    $result = $module->processParsed($result);

                    if (!$result instanceof \Graphpinator\Parser\ParsedRequest) {
                        break;
                    }
                }
            }

            if ($result instanceof \Graphpinator\Parser\ParsedRequest) {
                $result = $this->normalizer->normalize($result);

                foreach ($this->modules as $module) {
                    $result = $module->processNormalized($result);

                    if (!$result instanceof \Graphpinator\Normalizer\NormalizedRequest) {
                        break;
                    }
                }
            }

            if ($result instanceof \Graphpinator\Normalizer\NormalizedRequest) {
                $result = $this->finalizer->finalize($result, $request->getVariables(), $request->getOperationName());

                foreach ($this->modules as $module) {
                    $result = $module->processFinalized($result);
                }
            }

            $result = $this->resolver->resolve($result);

            foreach ($this->modules as $module) {
                $result = $module->processResult($result);
            }

            return $result;
        } catch (\Throwable $exception) {
            if (!$this->catchExceptions) {
                throw $exception;
            }

            $this->logger->log(self::getLogLevel($exception), self::getLogMessage($exception));

            return new \Graphpinator\Result(null, [
                $exception instanceof \Graphpinator\Exception\GraphpinatorBase
                    ? $exception
                    : \Graphpinator\Exception\GraphpinatorBase::notOutputableResponse(),
            ]);
        }
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger) : void
    {
        $this->logger = $logger;
    }

    private static function getLogMessage(\Throwable $exception) : string
    {
        return $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine();
    }

    private static function getLogLevel(\Throwable $exception) : string
    {
        if ($exception instanceof \Graphpinator\Exception\GraphpinatorBase) {
            return $exception->isOutputable()
                ? \Psr\Log\LogLevel::INFO
                : \Psr\Log\LogLevel::ERROR;
        }

        return \Psr\Log\LogLevel::EMERGENCY;
    }
}
