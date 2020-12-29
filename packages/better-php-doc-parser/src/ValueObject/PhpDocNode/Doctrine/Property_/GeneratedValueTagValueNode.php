<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\ValueObject\PhpDocNode\Doctrine\Property_;

use Rector\BetterPhpDocParser\Contract\PhpDocNode\SilentKeyNodeInterface;
use Rector\BetterPhpDocParser\ValueObject\PhpDocNode\Doctrine\AbstractDoctrineTagValueNode;
use Rector\PhpAttribute\Contract\PhpAttributableTagNodeInterface;

/**
 * @see \Rector\BetterPhpDocParser\Tests\PhpDocParser\TagValueNodeReprint\TagValueNodeReprintTest
 */
final class GeneratedValueTagValueNode extends AbstractDoctrineTagValueNode implements PhpAttributableTagNodeInterface, SilentKeyNodeInterface
{
    public function getShortName(): string
    {
        return '@ORM\GeneratedValue';
    }

    public function getSilentKey(): string
    {
        return 'strategy';
    }

    public function getAttributeClassName(): string
    {
        return 'TBA';
    }
}
