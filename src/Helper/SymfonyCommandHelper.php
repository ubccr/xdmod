<?php

namespace CCR\Helper;

use CCR\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\FormatException;
use Symfony\Component\Dotenv\Exception\PathException;

/**
 * The purpose of this class is to serve as a bridge to allow our existing code in `classes` to execute Symfony Commands
 * without relying on `exec`ing php scripts.
 *
 * NOTE: Once we have all of our existing code migrated to `src` then we can change `executeCommand` from a static
 * function to a regular function and use Dependency Injection to add it to the classes that need it.
 */
class SymfonyCommandHelper
{
    /**
     * Execute the provided Symfony $command.
     *
     * @param string $command the Symfony command to run.
     * @param array $options for the Symfony command.
     * @throws \LogicException if the command is empty.
     * @throws \Exception if an error is encountered while running the specified command.
     * @throws \RuntimeException if a non-zero exit code is returned by the Symfony Command.
     */
    public static function executeCommand(string $command, array $options = [], string $env = 'prod', bool $debug = false): void
    {
        list($statusCode, $output) = self::doExecuteCommand($command, $options, $env, $debug);
        if ($statusCode !== 0) {
            throw new \RuntimeException("Error Running Symfony Command $command\n$output");
        }
    }

    /**
     * Execute the provided Symfony console command.
     *
     * @param string $command
     * @param array $options
     * @param string $env
     * @param bool $debug
     * @throws \LogicException if the command is empty.
     * @throws \RuntimeException if the environment file cannot be loaded or
     *  if a non-zero exit code is returned by the Symfony console command
     */
    private static function doExecuteCommand(string $command, array $options, string $env, bool $debug): array
    {
        if (empty($command)) {
            throw new \LogicException('Command must not be empty.');
        }

        try {
            $envPath = BASE_DIR . '/.env';
            (new Dotenv())->bootEnv($envPath);
        } catch(FormatException | PathException $e) {
            throw new \RuntimeException('Unable to load environment file', $e->getCode(), $e);
        }

        // Setup our Kernel / Application
        $kernel = new Kernel($env, $debug);
        $application = new Application($kernel);

        // we set this so that it doesn't `exit` whatever php script is calling this function.
        $application->setAutoExit(false);

        // Set the Symfony command to execute.
        array_unshift($options, $command);

        $input = new ArrayInput($options);
        $output = new BufferedOutput();
        try {
            $statusCode = $application->run($input, $output);
            return [$statusCode, $output->fetch()];
        } catch(\Exception $e) {
            throw new \RuntimeException("Error while running Symfony Command", $e->getCode(), $e);
        }
    }

    public static function dumpDotEnv(): void
    {
        // Make sure to clear the cache before dumping the dotenv so we start clean.
        SymfonyCommandHelper::executeCommand('cache:clear');

        // Dump dotenv data so we don't read .env each time in prod.
        // Note: this means that if you want to start debugging stuff you'll need to delete the generated .env.
        SymfonyCommandHelper::executeCommand('dotenv:dump');
    }
}
