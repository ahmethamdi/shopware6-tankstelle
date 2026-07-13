<?php declare(strict_types=1);

namespace Tankstellenpartner24Theme\Controller;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * "Meine Top-Produkte" — müşterinin geçmiş siparişlerinde EN ÇOK aldığı ürünler.
 * B2B müşteri her zaman aldıklarını tek yerden görüp hızlıca tekrar sipariş eder.
 * Hesaplama: order_line_item'ları müşteri bazlı grupla (toplam adet desc).
 */
#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StorefrontRouteScope::ID]])]
class TopProductsController extends StorefrontController
{
    private const LIMIT = 12;

    public function __construct(
        private readonly Connection $connection,
        private readonly SalesChannelRepository $productRepository,
        private readonly GenericPageLoaderInterface $genericPageLoader
    ) {
    }

    #[Route(
        path: '/account/top-produkte',
        name: 'frontend.account.vapor.top-products.page',
        defaults: [
            PlatformRequest::ATTRIBUTE_LOGIN_REQUIRED => true,
            PlatformRequest::ATTRIBUTE_NO_STORE => true,
        ],
        methods: [Request::METHOD_GET]
    )]
    public function index(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->genericPageLoader->load($request, $context);

        $customer = $context->getCustomer();
        $rows = $customer ? $this->loadTopRows($customer->getId()) : [];

        // En çok alınan product_id'ler → güncel ürün entity'leri (görsel/fiyat/isim/stok)
        $products = [];
        $ids = array_keys($rows);
        if ($ids) {
            $criteria = new Criteria($ids);
            $criteria->addAssociation('cover');
            $criteria->addAssociation('manufacturer');
            $found = $this->productRepository->search($criteria, $context)->getEntities();

            // DB sırasını (en çok alınan önce) koru
            foreach ($ids as $id) {
                $product = $found->get($id);
                if ($product === null) {
                    continue; // ürün silinmiş olabilir
                }
                $products[] = [
                    'product' => $product,
                    'totalQuantity' => (int) $rows[$id]['total_quantity'],
                    'orderCount' => (int) $rows[$id]['order_count'],
                ];
            }
        }

        return $this->renderStorefront('@Storefront/storefront/page/account/vapor-top-products.html.twig', [
            'page' => $page,
            'vaporTopProducts' => $products,
        ]);
    }

    /**
     * @return array<string, array{total_quantity: int, order_count: int}> product_id(hex) => sayılar
     */
    private function loadTopRows(string $customerId): array
    {
        // LIMIT sabit (self::LIMIT, kod-içi int) → SQL'e doğrudan gömülür (parametre bind etmiyoruz).
        $limit = (int) self::LIMIT;
        $result = $this->connection->fetchAllAssociative(
            'SELECT LOWER(HEX(oli.product_id)) AS product_id,
                    SUM(oli.quantity) AS total_quantity,
                    COUNT(DISTINCT oli.order_id) AS order_count
             FROM order_line_item oli
             INNER JOIN order_customer oc
                 ON oc.order_id = oli.order_id
                 AND oc.order_version_id = oli.order_version_id
             WHERE oc.customer_id = :customerId
               AND oli.type = :type
               AND oli.product_id IS NOT NULL
             GROUP BY oli.product_id
             ORDER BY total_quantity DESC
             LIMIT ' . $limit,
            [
                'customerId' => Uuid::fromHexToBytes($customerId),
                'type' => 'product',
            ]
        );

        $rows = [];
        foreach ($result as $row) {
            $rows[$row['product_id']] = $row;
        }

        return $rows;
    }
}
