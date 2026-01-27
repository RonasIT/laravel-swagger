<?php

namespace RonasIT\AutoDoc\Tests\Support\Mock;

use RonasIT\AutoDoc\Tests\Support\Models\User;
use RonasIT\AutoDoc\Tests\Support\Resources\UserResource;
use RonasIT\AutoDoc\Tests\Support\Resources\UsersCollectionResource;

class TestController
{
    public function test(TestRequest $request)
    {
        $user = User::factory()->create();

        return UserResource::make($user);
    }

    public function users(TestRequest $request): UsersCollectionResource
    {
        $user = User::factory()->create();

        return UsersCollectionResource::make(collect([$user]));
    }

    public function deleteProfile(TestRequest $request): \Illuminate\Http\Response
    {
        return response()->noContent();
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
