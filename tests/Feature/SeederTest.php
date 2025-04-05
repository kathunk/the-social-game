<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('can seed the database', function () {
    $this->artisan('db:seed')->assertSuccessful();
});
