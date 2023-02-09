<?php

namespace Tests\Feature;

use App\Repositories\InboundsDB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{

    public function test_example()
    {
        InboundsDB::updateUserVol('br12.1', 5);
    }
}
