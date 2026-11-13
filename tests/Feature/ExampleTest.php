<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_describes_the_api(): void
    {
        $this->get('/')->assertOk()->assertJsonPath('name', 'Non Stop API');
    }
}
