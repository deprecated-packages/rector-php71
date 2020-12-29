<?php

declare(strict_types=1);

namespace Rector\Utils\ProjectValidator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symplify\PackageBuilder\Console\ShellCode;
use Symplify\SmartFileSystem\Finder\FinderSanitizer;
use Symplify\SmartFileSystem\SmartFileInfo;

final class ValidateFixtureSuffixCommand extends Command
{
    /**
     * @var FinderSanitizer
     */
    private $finderSanitizer;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    public function __construct(FinderSanitizer $finderSanitizer, SymfonyStyle $symfonyStyle)
    {
        $this->finderSanitizer = $finderSanitizer;
        $this->symfonyStyle = $symfonyStyle;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('[CI] Validate tests fixtures suffix');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $invalidFilePaths = [];
        foreach ($this->getInvalidFixtureFileInfos() as $invalidFixtureFileInfo) {
            $invalidFilePaths[] = $invalidFixtureFileInfo->getRelativeFilePathFromCwd();
        }
        if ($invalidFilePaths !== []) {
            $this->symfonyStyle->listing($invalidFilePaths);
            $message = sprintf('Found %d files with invalid suffix. Must be *.php.inc', count($invalidFilePaths));

            $this->symfonyStyle->error($message);

            return ShellCode::ERROR;
        }
        $this->symfonyStyle->success('All fixtures are correct');
        return ShellCode::SUCCESS;
    }

    /**
     * @return SmartFileInfo[]
     */
    private function getInvalidFixtureFileInfos(): array
    {
        $finder = new Finder();
        $finder = $finder->files()->name('#\.inc$#')->name('#\.php#')->notName('#\.php\.inc$#')->path('#/Fixture/#')->notPath('#TagValueNodeReprint#')->notPath('#PhpSpecToPHPUnitRector#')->notPath('#Source#')->notPath('#expected#')->notPath('DoctrineRepositoryAsService/Fixture/PostController.php')->notPath('Namespace_/ImportFullyQualifiedNamesRector/Fixture/SharedShortName.php')->notPath('Name/RenameClassRector/Fixture/DuplicatedClass.php')->notPath('Rector/FileNode/RenameSpecFileToTestFileRector/Fixture/some_file_Spec.php')->in(__DIR__ . '/../../../../tests')->in(__DIR__ . '/../../../../packages/*/tests')->in(__DIR__ . '/../../../../rules/*/tests');

        return $this->finderSanitizer->sanitize($finder);
    }
}
