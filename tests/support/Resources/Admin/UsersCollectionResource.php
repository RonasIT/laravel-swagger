<?php

namespace RonasIT\AutoDoc\Tests\Support\Resources\Admin;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UsersCollectionResource extends ResourceCollection
{
    public $collects = UserResource::class;
}
