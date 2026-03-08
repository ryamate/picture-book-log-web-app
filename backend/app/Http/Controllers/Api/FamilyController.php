<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFamilyRequest;
use App\Http\Requests\UpdateFamilyRequest;
use App\Http\Resources\FamilyResource;
use App\Http\Resources\MemberResource;
use App\Models\Family;
use Packages\Family\Application\Command\CreateFamily\CreateFamilyCommand;
use Packages\Family\Application\Command\CreateFamily\CreateFamilyHandler;
use Packages\Family\Application\Command\UpdateFamily\UpdateFamilyCommand;
use Packages\Family\Application\Command\UpdateFamily\UpdateFamilyHandler;
use Packages\Family\Application\Query\GetFamily\GetFamilyHandler;
use Packages\Family\Application\Query\GetFamily\GetFamilyQuery;
use Packages\Family\Application\Query\ListMembers\ListMembersHandler;
use Packages\Family\Application\Query\ListMembers\ListMembersQuery;

class FamilyController extends Controller
{
    public function store(StoreFamilyRequest $request, CreateFamilyHandler $handler)
    {
        $family = $handler->handle(new CreateFamilyCommand(
            name: $request->validated('name'),
            userId: $request->user()->id,
        ));

        $eloquentFamily = Family::find($family->id()->value());

        return (new FamilyResource($eloquentFamily))->response()->setStatusCode(201);
    }

    public function show(Family $family, GetFamilyHandler $handler)
    {
        $this->authorize('view', $family);

        $eloquentFamily = $handler->handle(new GetFamilyQuery($family->id));

        return new FamilyResource($eloquentFamily);
    }

    public function update(UpdateFamilyRequest $request, Family $family, UpdateFamilyHandler $handler)
    {
        $this->authorize('update', $family);

        $handler->handle(new UpdateFamilyCommand(
            familyId: $family->id,
            name: $request->validated('name'),
        ));

        return new FamilyResource($family->fresh());
    }

    public function members(Family $family, ListMembersHandler $handler)
    {
        $this->authorize('view', $family);

        $members = $handler->handle(new ListMembersQuery($family->id));

        return MemberResource::collection($members);
    }
}
