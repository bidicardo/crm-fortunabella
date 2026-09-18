<?php

namespace App\Enums;

enum Role: string
{
    case Creator = 'creator';
    case Member = 'member';
}
