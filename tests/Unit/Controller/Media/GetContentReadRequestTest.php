<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Controller\Media;

use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\GetContent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class GetContentReadRequestTest extends TestCase
{
    use GetContent;

    public function testAGetRequestIsReadFromItsQueryString(): void
    {
        $request = Request::create('/get-files', 'GET', ['folder' => '12', 'search' => 'logo']);

        self::assertSame(['folder' => '12', 'search' => 'logo'], $this->readRequestData($request));
    }

    public function testAPostRequestIsReadFromItsJsonBody(): void
    {
        $request = Request::create('/get-files', 'POST', [], [], [], [], '{"folder":12,"path":"/a"}');

        self::assertSame(['folder' => 12, 'path' => '/a'], $this->readRequestData($request));
    }

    public function testAPostRequestWithoutBodyHasNoParameter(): void
    {
        self::assertSame([], $this->readRequestData(Request::create('/get-files', 'POST')));
    }
}
