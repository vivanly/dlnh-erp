<?php

namespace Tests\Feature;

use Tests\TestCase;

class SystemTimezoneTest extends TestCase
{
    public function test_application_and_database_connections_use_vietnam_timezone(): void
    {
        $this->assertSame('Asia/Ho_Chi_Minh', config('app.timezone'));
        $this->assertSame('Asia/Ho_Chi_Minh', date_default_timezone_get());
        $this->assertSame(420, now()->utcOffset());

        $this->assertSame('+07:00', config('database.connections.mysql.timezone'));
        $this->assertSame('+07:00', config('database.connections.mariadb.timezone'));
        $this->assertSame('+07:00', config('database.connections.pgsql.timezone'));
    }
}
