<?php

declare(strict_types=1);

namespace Copy2Cloud\Base\Constants;

class CommonConstants
{
    const REMOTE_ADDR = 'REMOTE_ADDR';
    const REQUEST = 'REQUEST';
    const RESPONSE = 'RESPONSE';
    const CREATE = 'create';
    const READ = 'read';
    const UPDATE = 'update';
    const DELETE = 'delete';
    const SEARCH = 'search';
    const MASKED_FIELDS = [
        'pass',
        'password',
        'access_token',
        'token',
        'authorization',
    ];
    const NAMESPACE_CONTENTS = 'contents';
}
