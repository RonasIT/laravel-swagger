<?php

namespace RonasIT\AutoDoc\Tests\Support\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Traits\ModelTrait;

class User extends Model
{
    use ModelTrait, HasFactory;

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    public function getConnectionName(): string
    {
        return 'pgsql';
    }

    protected $fillable = [
        'name',
        'email',
    ];
}
