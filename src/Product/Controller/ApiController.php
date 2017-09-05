<?php declare(strict_types=1);

namespace Shopware\Product\Controller;

use Shopware\Category\Gateway\CategoryDenormalization;
use Shopware\Context\Struct\TranslationContext;
use Shopware\Framework\Api2\Resource\ResourceRegistry;
use Shopware\Product\ProductRepository;
use Shopware\Search\Criteria;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiController
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var CategoryDenormalization
     */
    private $categoryDenormalization;

    /**
     * @var ResourceRegistry
     */
    private $resourceRegistry;

    /**
     * @param ProductRepository $productRepository
     */
    public function __construct(
        ProductRepository $productRepository,
        CategoryDenormalization $categoryDenormalization,
        ResourceRegistry $resourceRegistry
    )
    {
        $this->productRepository = $productRepository;
        $this->categoryDenormalization = $categoryDenormalization;
        $this->resourceRegistry = $resourceRegistry;
    }

    public function listAction(Request $request, ApiContext $context): Response
    {
        $criteria = new Criteria();

        $translationContext = new TranslationContext('SWAG-SHOP-UUID-1', true, null);
        $result = $this->productRepository->search($criteria, $translationContext);

        return $this->createResponse($result, $context);
    }

    public function detailAction(string $uuid, ApiContext $apiContext)
    {
        $result = $this->productRepository->read([$uuid], $apiContext);

        return new JsonResponse($result);
    }

    public function createAction(Request $request, ApiContext $context): Response
    {
        $result = [];
        foreach ($context->rawData as $product) {
            $result[] = $this->productRepository
                ->create($product);
        }

        return $this->createResponse($result, $context);
    }

    /**
     * @param Request $request
     * @param ApiContext $context
     * @return Response
     */
    public function updateAction(Request $request, ApiContext $context): Response
    {
        $result = [];
        foreach ($context->rawData as $product) {
            // todo check data types from xml
            $product['lastStock'] = (int) $product['lastStock'];
            $product['crossbundlelook'] = (int) $product['crossbundlelook'];
            $product['notification'] = (int) $product['notification'];
            $product['mode'] = (int) $product['mode'];

            $result[] = $this->productRepository
                ->update($product);
        }

        return $this->createResponse($result, $context);
    }

    public function deleteAction(Request $request, ApiContext $context): Response
    {
        $result = [];
        foreach ($context->rawData as $product) {
            $result[] = $this->productRepository
                ->delete($product);
        }

        return $this->createResponse($result, $context);
    }

    private function createResponse($result, ApiContext $context): Response
    {
        // TODO - Fix please
        $context->apiFormat = 'json';

        if ($context->apiFormat === 'json') {
            $response = new JsonResponse($result);
        } elseif ($context->apiFormat === 'xml') {
            $response = (new XmlResponse())->createResponse('products', 'product', $result);
        } else {
            return new Response('invalid api format');
        }

        // TODO -  Use paginated information
        $response->headers->set('SW-COUNT', count($result));
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,PUT');
        $response->headers->set('Access-Control-Allow-Headers', 'X-Header-One,X-Header-Two');

        return $response;
    }
}
