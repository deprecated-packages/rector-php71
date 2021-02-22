<?php

declare(strict_types=1);

// decoupled in own "*.php" file, so ECS, Rector and PHPStan works out of the box here

use PHP_CodeSniffer\Util\Tokens;
use Symfony\Component\Console\Input\ArgvInput;
use Symplify\EasyCodingStandard\Console\EasyCodingStandardConsoleApplication;
use Symplify\EasyCodingStandard\DependencyInjection\EasyCodingStandardContainerFactory;
use Symplify\PackageBuilder\Console\ShellCode;
use Symplify\PackageBuilder\Console\Style\SymfonyStyleFactory;
use Symplify\SetConfigResolver\Bootstrap\InvalidSetReporter;
use Symplify\SetConfigResolver\Exception\SetNotFoundException;

// performance boost
gc_disable();

# 1. autoload
$autoloadIncluder = new AutoloadIncluder();
$autoloadIncluder->includeCwdVendorAutoloadIfExists();
$autoloadIncluder->autoloadProjectAutoloaderFile('/../../autoload.php');
$autoloadIncluder->includeDependencyOrRepositoryVendorAutoloadIfExists();
$autoloadIncluder->includePhpCodeSnifferAutoloadIfNotInPharAndInitliazeTokens();

try {
    $input = new ArgvInput();
    $ecsContainerFactory = new EasyCodingStandardContainerFactory();
    $container = $ecsContainerFactory->createFromFromInput($input);
} catch (SetNotFoundException $setNotFoundException) {
    $invalidSetReporter = new InvalidSetReporter();
    $invalidSetReporter->report($setNotFoundException);
    exit(ShellCode::ERROR);
} catch (Throwable $throwable) {
    $symfonyStyleFactory = new SymfonyStyleFactory();
    $symfonyStyle = $symfonyStyleFactory->create();

    $symfonyStyle->error($throwable->getMessage());
    exit(ShellCode::ERROR);
}

$application = $container->get(EasyCodingStandardConsoleApplication::class);
exit($application->run());

/**
 * Inspired by https://github.com/rectorphp/rector/pull/2373/files#diff-0fc04a2bb7928cac4ae339d5a8bf67f3
 */
final class AutoloadIncluder
{
    /**
     * @var string[]
     */
    private $alreadyLoadedAutoloadFiles = [];

    public function includeCwdVendorAutoloadIfExists(): void
    {
        $cwdVendorAutoload = getcwd() . '/vendor/autoload.php';
        if (! is_file($cwdVendorAutoload)) {
            return;
        }
        $this->loadIfNotLoadedYet($cwdVendorAutoload);
    }

    public function includeDependencyOrRepositoryVendorAutoloadIfExists(): void
    {
        // ECS' vendor is already loaded
        if (class_exists('\Symplify\EasyCodingStandard\HttpKernel\EasyCodingStandardKernel')) {
            return;
        }

        $devVendorAutoload = __DIR__ . '/../vendor/autoload.php';
        if (! is_file($devVendorAutoload)) {
            return;
        }

        $this->loadIfNotLoadedYet($devVendorAutoload);
    }

    public function autoloadProjectAutoloaderFile(string $file): void
    {
        $path = dirname(__DIR__) . $file;
        if (! is_file($path)) {
            return;
        }
        $this->loadIfNotLoadedYet($path);
    }

    public function includePhpCodeSnifferAutoloadIfNotInPharAndInitliazeTokens(): void
    {
        // file is autoloaded with classmap in PHAR
        // without phar, we still need to autoload it
        # 1. autoload
        $possibleAutoloadPaths = [
            // after split package
            __DIR__ . '/../vendor',
            // dependency
            __DIR__ . '/../../..',
            // monorepo
            __DIR__ . '/../../../vendor',
        ];

        foreach ($possibleAutoloadPaths as $possibleAutoloadPath) {
            $possiblePhpCodeSnifferAutoloadPath = $possibleAutoloadPath . '/squizlabs/php_codesniffer/autoload.php';
            if (! is_file($possiblePhpCodeSnifferAutoloadPath)) {
                continue;
            }

            require_once $possiblePhpCodeSnifferAutoloadPath;
        }

        // initalize PHPCS tokens
        new Tokens();
    }

    private function loadIfNotLoadedYet(string $file): void
    {
        if (in_array($file, $this->alreadyLoadedAutoloadFiles, true)) {
            return;
        }

        $this->alreadyLoadedAutoloadFiles[] = realpath($file);
        require_once $file;
    }
}
