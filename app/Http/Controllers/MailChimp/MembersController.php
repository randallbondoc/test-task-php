<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpMember;
use App\Database\Entities\MailChimp\MailChimpList;
use App\Http\Controllers\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mailchimp\Mailchimp;
use App\Http\Traits\MailChimpTrait;

class MembersController extends Controller
{

    use MailChimpTrait;

    /**
     * @var \Mailchimp\Mailchimp
     */
    private $mailChimp;

    /**
     * MembersController constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Mailchimp\Mailchimp $mailchimp
     */
    public function __construct(EntityManagerInterface $entityManager, Mailchimp $mailchimp)
    {
        parent::__construct($entityManager);

        $this->mailChimp = $mailchimp;
    }

    /**
     * Create MailChimp list.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        // Instantiate entity
        $member = new MailChimpMember($request->all());

        // Get list from member list id
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($member->getListId());

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $member->getListId())],
                404
            );
        }

        // Validate entity
        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
            // Save list into MailChimp
            $data['url'] = $this->generateURL('lists/' . $list->getMailChimpId() . '/members');
            $data['request_type'] = 'POST';
            $data['data'] = $member->toMailChimpArray();
            $response = json_decode($this->request($data));

            // Check response
            if (isset($response->status) && !is_string($response->status) && $response->status != 200) {
                if (isset($response->errors)) {
                    $errors = $this->parseError($response);
                    return $this->errorResponse(['message' => $errors], $response->status);
                }
                return $this->errorResponse(['message' => $response->title . ($response->detail ? ': ' . $response->detail : '') ], $response->status);
            } else {
                // Save member into db
                $this->saveEntity($member);
                // Set MailChimp id on the member and save member into db
                $this->saveEntity($member->setMailChimpId($response->id));
            }
        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }

    /**
     * Remove MailChimp list.
     *
     * @param string $memberId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(string $listId, string $memberId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpMember|null $member */
        $member = $this->entityManager->getRepository(MailChimpMember::class)->findOneBy([
            'listId' => $listId,
            'id' => $memberId
        ]);

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpMember[%s] not found', $memberId)],
                404
            );
        }

        // Get list from member list id
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($member->getListId());

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $member->getListId())],
                404
            );
        }

        try {
            // Remove list from MailChimp
            $data['url'] = $this->generateURL('lists/' . $list->getMailChimpId() . '/members/' . md5(strtolower($member->getEmailAddress())));
            $data['request_type'] = 'DELETE';
            $data['data'] = $member->toMailChimpArray();
            $response = json_decode($this->request($data));

            // Check response
            if (isset($response->status) && !is_string($response->status) && $response->status != 200) {
                if (isset($response->errors)) {
                    $errors = $this->parseError($response);
                    return $this->errorResponse(['message' => $errors]);
                }
                return $this->errorResponse(['message' => $response->title . ($response->detail ? ': ' . $response->detail : '') ]);
            } else {
                // Remove list from database
                $this->removeEntity($member);
            }
        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse(['message' => 'Successfully deleted!']);
    }

    /**
     * Retrieve and return MailChimp list.
     *
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showAll(string $listId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpMember|null $members */
        $members = $this->entityManager->getRepository(MailChimpMember::class)->findBy(['listId' => $listId]);

        // Get list
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        if (!count($members)) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpMembers not found for [%s]', $listId)],
                404
            );
        }

        return $this->successfulResponse($members);
    }

    /**
     * Retrieve and return MailChimp list.
     *
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $listId, string $memberId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpMember|null $member */
        $member = $this->entityManager->getRepository(MailChimpMember::class)->findOneBy([
            'listId' => $listId,
            'id' => $memberId
        ]);

        // Get list
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpMember[%s] not found', $memberId)],
                404
            );
        }

        return $this->successfulResponse($member->toArray());
    }

    /**
     * Update MailChimp list.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $listId
     * @param string $memberId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $listId, string $memberId): JsonResponse
    {
        // Get list
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        /** @var \App\Database\Entities\MailChimp\MailChimpMember|null $member */
        $member = $this->entityManager->getRepository(MailChimpMember::class)->findOneBy([
            'listId' => $listId,
            'id' => $memberId
        ]);

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpMember[%s] not found', $memberId)],
                404
            );
        }

        $email_address = $member->getEmailAddress();
        // Update member properties
        $member->fill($request->all());

        // Validate entity
        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
            // Save member into MailChimp
            $data['url'] = $this->generateURL('lists/' . $list->getMailChimpId() . '/members/' . md5(strtolower($email_address)));
            $data['request_type'] = 'PATCH';
            $data['data'] = $member->toMailChimpArray();
            $response = json_decode($this->request($data));

            // Check response
            if (isset($response->status) && !is_string($response->status) && $response->status != 200) {
                if (isset($response->errors)) {
                    $errors = $this->parseError($response);
                    return $this->errorResponse(['message' => $errors]);
                }
                return $this->errorResponse(['message' => $response->title . ($response->detail ? ': ' . $response->detail : '') ]);
            } else {
                // Save member into db
                $this->saveEntity($member);
                // Set MailChimp id on the member and save member into db
                $this->saveEntity($member->setMailChimpId($response->id));
            }
        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }
}
