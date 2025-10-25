<?php

test('tasks index route is registered', function () {
    $expected = rtrim(config('app.url'), '/').'/tasks';

    expect(route('tasks.index'))->toBe($expected);
});
