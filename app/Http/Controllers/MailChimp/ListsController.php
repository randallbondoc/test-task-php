<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Http\Controllers\Controller;
use App\Http\Traits\MailChimpTrait;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mailchimp\Mailchimp;

class ListsController extends Controller
{
    use MailChimpTrait;
    /**
     * @var \Mailchimp\Mailchimp
     */
    private $mailChimp;

    /**
     * ListsController constructor.
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
        $list = new MailChimpList($request->all());
        // Validate entity
        $validator = $this->getValidationFactory()->make($list->toMailChimpArray(), $list->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
//            // Save list into db
//            $this->saveEntity($list);
//            // Save list into MailChimp
//            $response = $this->mailChimp->post('lists', $list->toMailChimpArray());
//            // Set MailChimp id on the list and save list into db
//            $this->saveEntity($list->setMailChimpId($response->get('id')));

            // Save list into MailChimp
            $data['url'] = $this->generateURL('lists/' . $list->getMailChimpId());
            $data['request_type'] = 'POST';
            $data['data'] = $list->toMailChimpArray();
            $response = json_decode($this->request($data));

            // Check response
            if (isset($response->status) && !is_string($response->status) && $response->status != 200) {
                if (isset($response->errors)) {
                    $errors = $this->parseError($response);
                    return $this->errorResponse(['message' => $errors]);
                }
                return $this->errorResponse(['message' => $response->title]);
            } else {
                // Save list into db
                $this->saveEntity($list);
                // Set MailChimp id on the list and save list into db
                $this->saveEntity($list->setMailChimpId($response->id));
            }
        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($list->toArray());
    }

    /**
     * Remove MailChimp list.
     *
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(string $listId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        try {
//            // Remove list from database
//            $this->removeEntity($list);
//            // Remove list from MailChimp
//            $this->mailChimp->delete(\sprintf('lists/%s', $list->getMailChimpId()));

            // Remove list from MailChimp
            $data['url'] = $this->generateURL('lists/' . $list->getMailChimpId());
            $data['request_type'] = 'DELETE';
            $data['data'] = $list->toMailChimpArray();
            $response = json_decode($this->request($data));

            // Check response
            if (isset($response->status) && !is_string($response->status) && $response->status != 200) {
                if (isset($response->errors)) {
                    $errors = $this->parseError($response);
                    return $this->errorResponse(['message' => $errors]);
                }
                return $this->errorResponse(['message' => $response->title]);
            } else {
                // Remove list from database
                $this->removeEntity($list);
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
    public function show(string $listId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        return $this->successfulResponse($list->toArray());
    }

    /**
     * Update MailChimp list.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $listId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        // Update list properties
        $list->fill($request->all());

        // Validate entity
        $validator = $this->getValidationFactory()->make($list->toMailChimpArray(), $list->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
//            // Update list into database
//            $this->saveEntity($list);
//            // Update list into MailChimp
//            $this->mailChimp->patch(\sprintf('lists/%s', $list->getMailChimpId()), $list->toMailChimpArray());

            // Save list into MailChimp
            $data['url'] = $this->generateURL('lists/' . $list->getMailChimpId() . '/lists/' . $list->getMailChimpId());
            $data['request_type'] = 'PATCH';
            $data['data'] = $list->toMailChimpArray();
            $response = json_decode($this->request($data));

            // Check response
            if (isset($response->status) && !is_string($response->status) && $response->status != 200) {
                if (isset($response->errors)) {
                    $errors = $this->parseError($response);
                    return $this->errorResponse(['message' => $errors]);
                }
                return $this->errorResponse(['message' => $response->title]);
            } else {
                // Save list into db
                $this->saveEntity($list);
                // Set MailChimp id on the list and save list into db
                $this->saveEntity($list->setMailChimpId($response->id));
            }
        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($list->toArray());
    }
}
