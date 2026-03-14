<?php

declare(strict_types=1);

namespace Packages\Family\Domain\Entity;

use Packages\Family\Domain\ValueObject\Birthday;
use Packages\Family\Domain\ValueObject\ChildName;
use Packages\Shared\ValueObject\ChildId;
use Packages\Shared\ValueObject\FamilyId;

final class Child
{
    private function __construct(
        private readonly ?ChildId $id,
        private readonly FamilyId $familyId,
        private ChildName $name,
        private ?Birthday $birthday,
    ) {}

    public static function create(
        FamilyId $familyId,
        ChildName $name,
        ?Birthday $birthday,
    ): self {
        return new self(null, $familyId, $name, $birthday);
    }

    public static function reconstruct(
        ChildId $id,
        FamilyId $familyId,
        ChildName $name,
        ?Birthday $birthday,
    ): self {
        return new self($id, $familyId, $name, $birthday);
    }

    public function update(ChildName $name, ?Birthday $birthday): void
    {
        $this->name = $name;
        $this->birthday = $birthday;
    }

    public function id(): ?ChildId
    {
        return $this->id;
    }

    public function familyId(): FamilyId
    {
        return $this->familyId;
    }

    public function name(): ChildName
    {
        return $this->name;
    }

    public function birthday(): ?Birthday
    {
        return $this->birthday;
    }
}
