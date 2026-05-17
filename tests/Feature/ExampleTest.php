<?php

test('the application redirects to the HR panel', function () {
    $response = $this->get('/');

    $response->assertRedirect('/hr');
});
