<?php

namespace RonasIT\AutoDoc\Tests\Support\Mock;

use RonasIT\AutoDoc\Tests\Support\Models\User;
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

    public function deleteProfile(TestRequest $request): \Illuminate\Http\Response
    {
        return response()->noContent();
    }

    public function userAliasResource(TestRequest $request)
    {
        $user = User::factory()->create();

        return AliasResource::make(collect([$user]));
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

    public function __invoke(TestEmptyRequest $request)
    {
    }
}
