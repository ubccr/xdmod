<?php

namespace CCR\Helper;

use CCR\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Dotenv\Dotenv;

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
    public static function executeCommand(string $command, string $env = 'prod', bool $debug = false, array $options = []): void
    {
        list($statusCode, $output) = self::doExecuteCommand($command, $env, $debug, $options);
        if ($statusCode !== 0) {
            throw new \RuntimeException("Error Running $command\n$output");
        }
    }

    /**
     * The function that actually executes the Symfony commmand.
     *
     * @param string $command
     * @param string $env
     * @param bool $debug
     * @param array $options
     * @return array
     * @throws \LogicException
     * @throws \Exception
     * @throws \RuntimeException
     */
    private static function doExecuteCommand(string $command, string $env, bool $debug, array $options): array
    {
        if (empty($command)) {
            throw new \LogicException('Command must not be empty.');
        }

        try {
            $envPath = BASE_DIR . "/.env";
            (new Dotenv())->bootEnv($envPath);
        } catch(\Exception $e) {
            throw new \RuntimeException('Error booting the Symfony Environment', $e->getCode(), $e);
        }

        // Setup our Kernel / Application.
        $kernel = new Kernel($env, $debug);
        $application = new Application($kernel);

        // we set this so that it doesn't `exit` whatever php script is calling this function.
        $application->setAutoExit(false);

        // Set the Symfony command that is to be executed.
        $options['command'] = $command;

        $input = new ArrayInput($options);
        $output = new BufferedOutput();
        try {
            $statusCode = $application->run($input, $output);
            return [$statusCode, $output->fetch()];
        } catch(\Exception $e) {
            throw new \RuntimeException("Error while running Symfony Command", $e->getCode(), $e);
        }
    }
}
