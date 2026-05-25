<?php

it('boots the application', function () {
    expect(app()->version())->toStartWith('12.');
});

it('renders a basic 404 for unknown routes', function () {
    $this->get('/this-route-does-not-exist')->assertNotFound();
});
