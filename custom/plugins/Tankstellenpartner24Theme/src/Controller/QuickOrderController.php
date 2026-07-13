<?php declare(strict_types=1);

namespace Tankstellenpartner24Theme\Controller;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItemFactoryRegistry;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
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
 * "Schnellbestellung" — B2B hızlı sipariş: müşteri ürün numaralarını (SKU) +
 * adetleri girip tek seferde sepete ekler. Katalogdan tek tek aramaya gerek kalmaz.
 */
#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StorefrontRouteScope::ID]])]
class QuickOrderController extends StorefrontController
{
    public function __construct(
        private readonly SalesChannelRepository $productRepository,
        private readonly CartService $cartService,
        private readonly LineItemFactoryRegistry $lineItemFactory,
        private readonly GenericPageLoaderInterface $genericPageLoader
    ) {
    }

    #[Route(
        path: '/account/schnellbestellung',
        name: 'frontend.account.vapor.quick-order.page',
        defaults: [
            PlatformRequest::ATTRIBUTE_LOGIN_REQUIRED => true,
            PlatformRequest::ATTRIBUTE_NO_STORE => true,
        ],
        methods: [Request::METHOD_GET]
    )]
    public function index(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->genericPageLoader->load($request, $context);

        return $this->renderStorefront('@Storefront/storefront/page/account/vapor-quick-order.html.twig', [
            'page' => $page,
        ]);
    }

    #[Route(
        path: '/account/schnellbestellung/add',
        name: 'frontend.account.vapor.quick-order.add',
        defaults: [
            PlatformRequest::ATTRIBUTE_LOGIN_REQUIRED => true,
            PlatformRequest::ATTRIBUTE_NO_STORE => true,
        ],
        methods: [Request::METHOD_POST]
    )]
    public function add(Request $request, SalesChannelContext $context): Response
    {
        /** @var array<int, array{sku?: string, qty?: string}> $lines */
        $lines = $request->request->all('lines');

        // Girilen SKU + adetleri topla (boş satırları atla)
        $wanted = [];
        foreach ($lines as $line) {
            $sku = trim((string) ($line['sku'] ?? ''));
            $qty = (int) ($line['qty'] ?? 0);
            if ($sku === '') {
                continue;
            }
            $wanted[$sku] = ($wanted[$sku] ?? 0) + max(1, $qty);
        }

        if (!$wanted) {
            $this->addFlash(self::DANGER, 'Bitte geben Sie mindestens eine Artikelnummer ein.');

            return $this->redirectToRoute('frontend.account.vapor.quick-order.page');
        }

        // SKU → ürün eşleştir
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('productNumber', array_keys($wanted)));
        $products = $this->productRepository->search($criteria, $context)->getEntities();

        $bySku = [];
        foreach ($products as $product) {
            $bySku[$product->getProductNumber()] = $product;
        }

        $lineItems = [];
        $notFound = [];

        foreach ($wanted as $sku => $qty) {
            if (!isset($bySku[$sku])) {
                $notFound[] = $sku;
                continue;
            }
            $product = $bySku[$sku];
            $lineItems[] = $this->lineItemFactory->create([
                'id' => $product->getId(),
                'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                'referencedId' => $product->getId(),
                'quantity' => $qty,
            ], $context);
        }

        if ($lineItems) {
            // Her eklemede sepeti TAZE çek (cache'lenmiş cart ilk item'ı düşürüyordu).
            foreach ($lineItems as $lineItem) {
                $cart = $this->cartService->getCart($context->getToken(), $context);
                $this->cartService->add($cart, $lineItem, $context);
            }
            $this->addFlash(self::SUCCESS, count($lineItems) . ' Artikel wurden in den Warenkorb gelegt.');
        }
        if ($notFound) {
            $this->addFlash(self::WARNING, 'Nicht gefunden: ' . implode(', ', $notFound));
        }

        return $this->redirectToRoute('frontend.account.vapor.quick-order.page');
    }
}
