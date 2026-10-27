<?php
/**
 * Amweb SwatchImage
 */
declare(strict_types=1);

namespace Amweb\SwatchImage\Plugin;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\RequestInterface;


class ReplaceListingImageByFilter
{
    private const LISTING_IMAGE_IDS = ['category_page_grid', 'category_page_list'];
    private const IMAGE_ATTRIBUTES = ['small_image', 'image', 'thumbnail'];

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var string[]
     */
    private $filterCodes;

    /**
     * @var array|null
     */
    private $activeFilters;

    /**
     * @var array
     */
    private $childCache = [];

    /**
     * @param RequestInterface $request
     * @param Configurable $configurableType
     * @param string[] $filterCodes
     */
    public function __construct(
        RequestInterface $request,
        Configurable $configurableType,
        array $filterCodes = []
    ) {
        $this->request = $request;
        $this->configurableType = $configurableType;
        $this->filterCodes = array_values($filterCodes);
    }

    /**
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param AbstractProduct $subject
     * @param Product $product
     * @param string $imageId
     * @param array $attributes
     * @return array|null
     */
    public function beforeGetImage(
        AbstractProduct $subject,
        $product,
        $imageId,
        $attributes = []
    ) {
        if (
            !$product instanceof Product
            || $product->getTypeId() !== Configurable::TYPE_CODE
            || !in_array($imageId, self::LISTING_IMAGE_IDS, true)
        ) {
            return null;
        }

        $filters = $this->getActiveFilters();
        if (!$filters) {
            return null;
        }

        $child = $this->getMatchingChild($product, $filters);
        if (!$child) {
            return null;
        }

        // Clone so name, url, price, labels of the parent stay untouched
        $clonedProduct = clone $product;
        foreach (self::IMAGE_ATTRIBUTES as $code) {
            $file = $child->getData($code);
            if ($file && $file !== 'no_selection') {
                $clonedProduct->setData($code, $file);
            }
        }

        return [$clonedProduct, $imageId, (array) $attributes];
    }

    /**
     *
     * @return array
     */
    private function getActiveFilters(): array
    {
        if ($this->activeFilters !== null) {
            return $this->activeFilters;
        }

        $this->activeFilters = [];
        foreach ($this->filterCodes as $code) {
            $value = $this->request->getParam($code);
            if ($value === null || $value === '' || is_array($value)) {
                continue;
            }

            $ids = array_filter(array_map('intval', explode(',', (string) $value)));
            if ($ids) {
                $this->activeFilters[$code] = array_values(array_unique($ids));
            }
        }

        return $this->activeFilters;
    }

    /**
     *
     * @param Product $product
     * @param array $filters
     * @return Product|null
     */
    private function getMatchingChild(Product $product, array $filters): ?Product
    {
        $productId = (int) $product->getId();
        if (array_key_exists($productId, $this->childCache)) {
            return $this->childCache[$productId];
        }

        $collection = $this->configurableType->getUsedProductCollection($product)
            ->addAttributeToSelect(self::IMAGE_ATTRIBUTES)
            ->addAttributeToFilter('status', Status::STATUS_ENABLED);

        foreach ($filters as $code => $ids) {
            $collection->addAttributeToFilter($code, ['in' => $ids]);
        }

        $collection->addAttributeToFilter('small_image', ['nin' => ['no_selection', '']])
            ->setPageSize(1)
            ->setCurPage(1);

        $child = $collection->getFirstItem();
        $this->childCache[$productId] = ($child && $child->getId()) ? $child : null;

        return $this->childCache[$productId];
    }
}
