<?php

namespace CCR\Helper;

use CCR\Helper\Exception\NonZeroStatusCodeException;
use CCR\Kernel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\Output;
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
readonly class SymfonyCommandHelper
{

    public function __construct(private ?LoggerInterface $logger = null)
    {
    }

    /**
     * Execute the provided Symfony $command.
     *
     * @param string|array $command the Symfony command to run.
     * @throws \LogicException if the command is empty.
     * @throws \Exception if an error is encountered while running the specified command.
     * @throws \RuntimeException if a non-zero exit code is returned by the Symfony Command.
     */
    public function executeCommand(string|array $command, string $env = 'prod', bool $debug = false): string
    {
        $this->logger?->debug('Executing Symfony String Command', ['command' => var_export($command, true)]);

        if (empty($command)) {
            throw new \LogicException('Command must not be empty.');
        }

        // Boot up the environment for Symfony to use when executing the provided command.
        $envPath = BASE_DIR . '/.env';
        (new Dotenv())->bootEnv($envPath);


        // Setup our Kernel / Application
        $kernel = new Kernel($env, $debug);
        $application = new Application($kernel);

        // we set this so that it doesn't `exit` whatever php script is calling this function.
        $application->setAutoExit(false);

        // Determine what type of Input to use based on the type of $command.
        if (is_array($command)) {
            $input = new ArgvInput($command);
        } else {
            $input = new StringInput($command);
        }
        $output = new BufferedOutput();

        // Attempt to execute the Symfony command.
        $statusCode = $application->run($input, $output);

        $this->logger?->debug('Symfony Command Executed', ['status_code' => $statusCode]);

        // Make sure to snag the output now just in case the application returned a non-zero statusCode so we can
        // include it with the exception.
        $commandOutput = $output->fetch();
        if ($statusCode !== 0) {
            throw new NonZeroStatusCodeException($commandOutput, "Error Running Symfony Command");
        }

        return $commandOutput;
    }

    /**
     * A helper function that first executes cache:clear followed by dotenv:dump. This ensures that we have an up to
     * date `.env.local.php` file.
     *
     * @return void
     * @throws \Exception
     */
    public function dumpDotEnv(): void
    {
        // Make sure to clear the cache before dumping the dotenv so we start clean.
        $this->executeCommand('cache:clear');

        // Dump dotenv data so we don't read .env each time in prod.
        // Note: this means that if you want to start debugging stuff you'll need to delete or modify the generated
        // .env.local.php.
        $this->executeCommand('dotenv:dump');
    }
}
