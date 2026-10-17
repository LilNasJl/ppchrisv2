<?php

test('the application displays the landing page', function () {
    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertSee('Human Resource Information System')
        ->assertSee('HRIS: Admin')
        ->assertSee('HRIS: Self-Service');
});
