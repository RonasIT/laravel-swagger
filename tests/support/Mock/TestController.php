<?php

namespace RonasIT\AutoDoc\Tests\Support\Mock;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RonasIT\AutoDoc\Tests\Support\Models\User;
use RonasIT\AutoDoc\Tests\Support\Resources\Admin\UserResource as AdminResource;
use RonasIT\AutoDoc\Tests\Support\Resources\Admin\UsersCollectionResource as AdminCollectionResource;
use RonasIT\AutoDoc\Tests\Support\Resources\User\UserResource as UserFromSubDirResource;
use RonasIT\AutoDoc\Tests\Support\Resources\UserResource;
use RonasIT\AutoDoc\Tests\Support\Resources\UserResource as AliasResource;
use RonasIT\AutoDoc\Tests\Support\Resources\UsersCollectionResource;

class TestController
{
    public function test(TestRequest $request)
    {
    }

    public function users(TestRequest $request): UsersCollectionResource
    {
        $users = collect([
            User::factory()->create(),
            User::factory()->create(),
        ]);

        return UsersCollectionResource::make($users);
    }

    public function user(TestRequest $request)
    {
        $user = User::factory()->create();

        return UserResource::make($user);
    }

    public function userWithUnionReturnType(TestRequest $request): UserResource|JsonResponse
    {
        $user = User::factory()->create();

        return UserResource::make($user);
    }

    public function userWithNewResource(TestRequest $request)
    {
        $user = User::factory()->create();

        return new UserResource($user);
    }

    public function makeResourceAsCollect(TestRequest $request): AnonymousResourceCollection
    {
        $users = collect([
            User::factory()->create(),
            User::factory()->create(),
        ]);

        return UserResource::collection($users);
    }

    public function deleteProfile(TestRequest $request): Illuminate\Http\Response
    {
        return response()->noContent();
    }

    public function userAliasResource(TestRequest $request)
    {
        $user = User::factory()->create();

        return AliasResource::make(collect([$user]));
    }

    public function userFromSubDirResource(TestRequest $request)
    {
        $user = User::factory()->create();

        return UserFromSubDirResource::make(collect([$user]));
    }

    public function getAdmin(TestRequest $request): AdminResource
    {
        $user = User::factory()->create();

        return AdminResource::make(collect([$user]));
    }

    public function getAdminCollection(TestRequest $request): AdminCollectionResource
    {
        $user = User::factory()->create();

        return AdminCollectionResource::make(collect([$user]));
    }

    public function testRequestWithoutRuleType(TestRequestWithoutRuleType $request)
    {
    }

    public function testRequestWithAnnotations(TestRequestWithAnnotations $request)
    {
    }

    public function testRequestWithContract(TestContract $contract, string $param)
    {
    }

    public function testRequestWithArrayParams(TestRequestWithArrayParams $request)
    {
    }

    public function __invoke(TestEmptyRequest $request)
    {
    }
}
