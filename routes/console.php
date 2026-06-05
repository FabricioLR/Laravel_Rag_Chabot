<?php

Schedule::command('app:fetch-word-press-posts')
    ->everyMinute()
    ->withoutOverlapping();