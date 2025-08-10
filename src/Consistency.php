<?php

declare(strict_types=1);

namespace LaravelCassandraDriver;

enum Consistency: string {
    case ALL = 'ALL';
    case ANY = 'ANY';
    case EACH_QUORUM = 'EACH_QUORUM';
    case LOCAL_ONE = 'LOCAL_ONE';
    case LOCAL_QUORUM = 'LOCAL_QUORUM';
    case LOCAL_SERIAL = 'LOCAL_SERIAL';
    case ONE = 'ONE';
    case QUORUM = 'QUORUM';
    case SERIAL = 'SERIAL';
    case THREE = 'THREE';
    case TWO = 'TWO';
}
