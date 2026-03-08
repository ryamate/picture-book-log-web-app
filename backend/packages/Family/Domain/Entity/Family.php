<?php

declare(strict_types=1);

namespace Packages\Family\Domain\Entity;

use Packages\Family\Domain\ValueObject\FamilyId;
use Packages\Family\Domain\ValueObject\FamilyName;

final class Family
{
    private function __construct(
        private readonly ?FamilyId $id,
        private FamilyName $name,
    ) {}

    public static function create(FamilyName $name): self
    {
        return new self(null, $name);
    }

    public static function reconstruct(FamilyId $id, FamilyName $name): self
    {
        return new self($id, $name);
    }

    public function rename(FamilyName $name): void
    {
        $this->name = $name;
    }

    public function id(): ?FamilyId
    {
        return $this->id;
    }

    public function name(): FamilyName
    {
        return $this->name;
    }
}
