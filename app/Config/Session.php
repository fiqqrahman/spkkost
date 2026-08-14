<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Session extends BaseConfig
{
    public string $driver = \CodeIgniter\Session\Handlers\DatabaseHandler::class;

    public string $cookieName = 'ci_session';

    public int $expiration = 7200;

    public string $savePath = 'ci_sessions';

    public bool $matchIP = false;

    public int $timeToUpdate = 300;

    public bool $regenerateDestroy = false;

    public ?string $DBGroup = 'default';
}
