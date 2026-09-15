<?php

declare(strict_types=1);

arch('src uses strict types')
    ->expect('LaravelBoilerplate')
    ->toUseStrictTypes();

arch('no debug helpers')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray'])
    ->not->toBeUsed();
