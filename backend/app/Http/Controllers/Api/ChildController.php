<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChildRequest;
use App\Http\Requests\UpdateChildRequest;
use App\Http\Resources\ChildResource;
use App\Models\Child;
use App\Models\Family;
use Packages\Family\Application\Command\AddChild\AddChildCommand;
use Packages\Family\Application\Command\AddChild\AddChildHandler;
use Packages\Family\Application\Command\RemoveChild\RemoveChildCommand;
use Packages\Family\Application\Command\RemoveChild\RemoveChildHandler;
use Packages\Family\Application\Command\UpdateChild\UpdateChildCommand;
use Packages\Family\Application\Command\UpdateChild\UpdateChildHandler;
use Packages\Family\Application\Query\ListChildren\ListChildrenHandler;
use Packages\Family\Application\Query\ListChildren\ListChildrenQuery;

class ChildController extends Controller
{
    public function index(Family $family, ListChildrenHandler $handler)
    {
        $this->authorize('view', $family);

        $children = $handler->handle(new ListChildrenQuery($family->id));

        return ChildResource::collection($children);
    }

    public function store(StoreChildRequest $request, Family $family, AddChildHandler $handler)
    {
        $this->authorize('view', $family);

        $child = $handler->handle(new AddChildCommand(
            familyId: $family->id,
            name: $request->validated('name'),
            birthday: $request->validated('birthday'),
        ));

        $eloquentChild = Child::find($child->id()->value());

        return (new ChildResource($eloquentChild))->response()->setStatusCode(201);
    }

    public function update(UpdateChildRequest $request, Family $family, Child $child, UpdateChildHandler $handler)
    {
        $this->authorize('manage', $child);

        $handler->handle(new UpdateChildCommand(
            childId: $child->id,
            name: $request->validated('name'),
            birthday: $request->validated('birthday'),
        ));

        return new ChildResource($child->fresh());
    }

    public function destroy(Family $family, Child $child, RemoveChildHandler $handler)
    {
        $this->authorize('manage', $child);

        $handler->handle(new RemoveChildCommand($child->id));

        return response()->json(null, 204);
    }
}
