<?php

namespace RonasIT\AutoDoc\Tests\Support\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UsersCollectionResource extends ResourceCollection
{
    public $collects = UserResource::class;
}
