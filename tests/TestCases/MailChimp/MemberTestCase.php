<?php
declare(strict_types=1);

namespace Tests\App\TestCases\MailChimp;

use App\Database\Entities\MailChimp\MailChimpMember;
use Illuminate\Http\JsonResponse;
use Mailchimp\Mailchimp;
use Mockery;
use Mockery\MockInterface;
use Tests\App\TestCases\WithDatabaseTestCase;

abstract class MemberTestCase extends WithDatabaseTestCase
{
    protected const MAILCHIMP_EXCEPTION_MESSAGE = 'MailChimp exception';

    /**
     * @var array
     */
    protected $createdMemberIds = [];

    /**
     * @var array
     */
    protected static $memberData = [
        'email_address' => 'johndoe24@gmail.com',
        'status' => 'subscribed',
        'list_id' => '6e6dd766-f1ce-11ea-9e92-1831bf96e34c',
    ];

    /**
     * @var array
     */
    protected static $notRequired = [
        'list_id',
        'mail_chimp_id'
    ];

    /**
     * Call MailChimp to delete members created during test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        /** @var Mailchimp $mailChimp */
//        $mailChimp = $this->app->make(Mailchimp::class);

        foreach ($this->createdMemberIds as $memberId) {
            // Delete member on MailChimp after test
            $this->delete(\sprintf('/mailchimp/lists/' . env('MAILCHIMP_LIST_ID') . '/members/%s', $memberId));
        }

        parent::tearDown();
    }

    /**
     * Asserts error response when member not found.
     *
     * @param string $memberId
     *
     * @return void
     */
    protected function assertMemberNotFoundResponse(string $memberId): void
    {
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals(\sprintf('MailChimpMember[%s] not found', $memberId), $content['message']);
    }

    /**
     * Asserts error response when MailChimp exception is thrown.
     *
     * @param \Illuminate\Http\JsonResponse $response
     *
     * @return void
     */
    protected function assertMailChimpExceptionResponse(JsonResponse $response): void
    {
        $content = \json_decode($response->content(), true);

        self::assertEquals(400, $response->getStatusCode());
        self::assertArrayHasKey('message', $content);
        self::assertEquals(self::MAILCHIMP_EXCEPTION_MESSAGE, $content['message']);
    }

    /**
     * Create MailChimp member into database.
     *
     * @param array $data
     *
     * @return \App\Database\Entities\MailChimp\MailChimpMember
     */
    protected function createMember(array $data): MailChimpMember
    {
        $member = new MailChimpMember($data);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * Returns mock of MailChimp to trow exception when requesting their API.
     *
     * @param string $method
     *
     * @return \Mockery\MockInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess) Mockery requires static access to mock()
     */
    protected function mockMailChimpForException(string $method): MockInterface
    {
        $mailChimp = Mockery::mock(Mailchimp::class);

        $mailChimp
            ->shouldReceive($method)
            ->once()
            ->withArgs(function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andThrow(new \Exception(self::MAILCHIMP_EXCEPTION_MESSAGE));

        return $mailChimp;
    }
}
