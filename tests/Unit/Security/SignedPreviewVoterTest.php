<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Security;

use Adeliom\SyliusHappyCMSPlugin\Entity\Page\Page as BasePage;
use Adeliom\SyliusHappyCMSPlugin\Security\ContentDocumentVoter;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\SignedPreviewContext;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\SignedPreviewRequestValidatorInterface;
use Adeliom\SyliusHappyCMSPlugin\Security\SignedPreviewVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\Page;

final class SignedPreviewVoterTest extends TestCase
{
    private const string RESOURCE = 'sylius_happy_cms.page';

    private const string ADMIN = 'http://admin.localhost:8099';

    public function testItGrantsPageBuilderForAMatchingSignedRequest(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote(new SignedPreviewContext(self::RESOURCE, 42, 'fr_FR', self::ADMIN)));
    }

    public function testItAbstainsForAnotherId(): void
    {
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote(new SignedPreviewContext(self::RESOURCE, 43, 'fr_FR', self::ADMIN)));
    }

    public function testItAbstainsForAnUnknownResource(): void
    {
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote(new SignedPreviewContext('app.unknown', 42, 'fr_FR', self::ADMIN)));
    }

    public function testItAbstainsForTheResourceOfAnotherClass(): void
    {
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote(new SignedPreviewContext('app.post', 42, 'fr_FR', self::ADMIN)));
    }

    public function testItAbstainsForAnotherLocale(): void
    {
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote(new SignedPreviewContext(self::RESOURCE, 42, 'fr_FR', self::ADMIN), requestLocale: 'en_US'));
    }

    public function testItAbstainsWithoutSignature(): void
    {
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote(null));
    }

    public function testItAbstainsForAnotherAttribute(): void
    {
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote(new SignedPreviewContext(self::RESOURCE, 42, 'fr_FR', self::ADMIN), attribute: 'EDIT'));
    }

    public function testItAbstainsForASubjectThatIsNotRoutable(): void
    {
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote(new SignedPreviewContext(self::RESOURCE, 42, 'fr_FR', self::ADMIN), subject: new \stdClass()));
    }

    public function testItAbstainsWithoutMainRequest(): void
    {
        $voter = new SignedPreviewVoter(new RequestStack(), $this->validator(new SignedPreviewContext(self::RESOURCE, 42, 'fr_FR', self::ADMIN)), self::resources());

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote(new NullToken(), self::page(), [ContentDocumentVoter::PAGE_BUILDER]));
    }

    private function vote(?SignedPreviewContext $context, string $requestLocale = 'fr_FR', string $attribute = ContentDocumentVoter::PAGE_BUILDER, mixed $subject = null): int
    {
        $request = Request::create('http://localhost:8099/ma-page');
        $request->setLocale($requestLocale);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $voter = new SignedPreviewVoter($requestStack, $this->validator($context), self::resources());

        return $voter->vote(new NullToken(), $subject ?? self::page(), [$attribute]);
    }

    private function validator(?SignedPreviewContext $context): SignedPreviewRequestValidatorInterface
    {
        $validator = $this->createStub(SignedPreviewRequestValidatorInterface::class);
        $validator->method('validate')->willReturn($context);

        return $validator;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function resources(): array
    {
        return [
            self::RESOURCE => ['classes' => ['model' => Page::class]],
            'app.post' => ['classes' => ['model' => \ArrayObject::class]],
        ];
    }

    private static function page(): BasePage
    {
        $page = new Page();
        (new \ReflectionProperty($page, 'id'))->setValue($page, 42);

        return $page;
    }
}
