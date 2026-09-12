<?php

namespace Tests\Unit;

use App\Support\DomainName;
use Tests\TestCase;

class DomainNameTest extends TestCase
{
    public function test_split_returns_sld_and_tld_for_simple_domain(): void
    {
        $this->assertSame(
            ['sld' => 'francisuzoigwe', 'tld' => '.com'],
            DomainName::split('francisuzoigwe.com'),
        );
    }

    public function test_split_handles_multi_part_tld(): void
    {
        $this->assertSame(
            ['sld' => 'brightmedia', 'tld' => '.com.ng'],
            DomainName::split('brightmedia.com.ng'),
        );
    }
}
