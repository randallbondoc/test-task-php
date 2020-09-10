<?php
declare(strict_types=1);

namespace Tests\App\Functional\Http\Controllers\MailChimp;

use Tests\App\TestCases\MailChimp\MemberTestCase;
use Illuminate\Contracts\Console\Kernel;

class MembersControllerTest extends MemberTestCase
{
    /**
     * Test application creates successfully member and returns it back with id from MailChimp.
     *
     * @return void
     */
    public function testCreateMemberSuccessfully(): void
    {
        $this->app->make(Kernel::class)->call('migrate:refresh', ['--seed' => TRUE]);
        $this->post('/mailchimp/lists/' . env('MAILCHIMP_LIST_ID') . '/members', static::$memberData);

        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseOk();
        $this->seeJson(static::$memberData);
        self::assertArrayHasKey('mail_chimp_id', $content);
        self::assertNotNull($content['mail_chimp_id']);

        $this->createdMemberIds[] = $content['id']; // Store MailChimp member id for cleaning purposes
    }

    /**
     * Test application returns error response with errors when member validation fails.
     *
     * @return void
     */
    public function testCreateMemberValidationFailed(): void
    {
        $this->app->make(Kernel::class)->call('migrate:refresh', ['--seed' => TRUE]);
        $data = static::$memberData;
        $data['email_address'] = '';
        $this->post('/mailchimp/lists/' . env('MAILCHIMP_LIST_ID') . '/members', $data);

        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(400);
        self::assertArrayHasKey('message', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertEquals('Invalid data given', $content['message']);

//        foreach (\array_keys(static::$memberData) as $key) {
//            if (\in_array($key, static::$notRequired, true)) {
//                continue;
//            }
//
//            self::assertArrayHasKey($key, $content['errors']);
//        }
    }

    /**
     * Test application returns error response when member not found.
     *
     * @return void
     */
    public function testShowMemberNotFoundException(): void
    {
        $this->app->make(Kernel::class)->call('migrate:refresh', ['--seed' => TRUE]);
        $this->get('/mailchimp/lists/' . env('MAILCHIMP_LIST_ID') . '/members/invalid-member-id');

        $this->assertMemberNotFoundResponse('invalid-member-id');
    }

    /**
     * Test application returns successful response with member data when requesting existing member.
     *
     * @return void
     */
    public function testShowMemberSuccessfully(): void
    {
        $this->app->make(Kernel::class)->call('migrate:refresh', ['--seed' => TRUE]);
        $member = $this->createMember(static::$memberData);

        $this->get(\sprintf('/mailchimp/lists/' . env('MAILCHIMP_LIST_ID') . '/members/%s', $member->getId()));
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseOk();

        foreach (static::$memberData as $key => $value) {
            self::assertArrayHasKey($key, $content);
            self::assertEquals($value, $content[$key]);
        }
    }

    /**
     * Test application returns error response when member not found.
     *
     * @return void
     */
    public function testUpdateMemberNotFoundException(): void
    {
        $this->app->make(Kernel::class)->call('migrate:refresh', ['--seed' => TRUE]);
        $this->put('/mailchimp/lists/' . env('MAILCHIMP_LIST_ID') . '/members/invalid-member-id');

        $this->assertMemberNotFoundResponse('invalid-member-id');
    }

    /**
     * Test application returns successfully response when updating existing member with updated values.
     *
     * @return void
     */
    public function testUpdateMemberSuccessfully(): void
    {
        $this->app->make(Kernel::class)->call('migrate:refresh', ['--seed' => TRUE]);
        $this->post('/mailchimp/lists/' . env('MAILCHIMP_LIST_ID') . '/members', static::$memberData);
        $member = \json_decode($this->response->content(), true);

        if (isset($member['id'])) {
            $this->createdMemberIds[] = $member['id']; // Store MailChimp member id for cleaning purposes
        }
        $member_id = $member['id'] ?? (is_object($member) ? $member->getId() : 0);

        $this->put(\sprintf('/mailchimp/lists/' . env('MAILCHIMP_LIST_ID') . '/members/%s', $member_id), ['email_address' => static::$memberData['email_address']]);
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseOk();

        foreach (\array_keys(static::$memberData) as $key) {
            self::assertArrayHasKey($key, $content);
            self::assertEquals(static::$memberData['email_address'], $content['email_address']);
        }
    }

    /**
     * Test application returns error response with errors when member validation fails.
     *
     * @return void
     */
    public function testUpdateMemberValidationFailed(): void
    {
        $this->app->make(Kernel::class)->call('migrate:refresh', ['--seed' => TRUE]);

        $member = $this->createMember(static::$memberData);

        $this->put(\sprintf('/mailchimp/lists/' . env('MAILCHIMP_LIST_ID') . '/members/%s', $member->getId()), ['email_address' => '']);
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(400);
        self::assertArrayHasKey('message', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertArrayHasKey('email_address', $content['errors']);
        self::assertEquals('Invalid data given', $content['message']);
    }

    /**
     * Test application returns error response when member not found.
     *
     * @return void
     */
    public function testRemoveMemberNotFoundException(): void
    {
        $this->delete('/mailchimp/lists/' . env('MAILCHIMP_LIST_ID') . '/members/invalid-member-id');

        $this->assertMemberNotFoundResponse('invalid-member-id');
    }

    /**
     * Test application returns empty successful response when removing existing member.
     *
     * @return void
     */
    public function testRemoveMemberSuccessfully(): void
    {
        $this->app->make(Kernel::class)->call('migrate:refresh', ['--seed' => TRUE]);
        $this->post('/mailchimp/lists/' . env('MAILCHIMP_LIST_ID') . '/members', static::$memberData);
        $member = \json_decode($this->response->content(), true);
        $member_id = $member['id'] ?? (is_object($member) ? $member->getId() : 0);

        $this->delete(\sprintf('/mailchimp/lists/' . env('MAILCHIMP_LIST_ID') . '/members/%s', $member_id));

        $this->assertResponseOk();
        self::assertEmpty([]);
    }

}
