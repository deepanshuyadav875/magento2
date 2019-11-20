<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LayeredNavigation\Block\Navigation;

use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Provides tests for filters block on category page.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation disabled
 */
class CategoryTest extends AbstractCategoryTest
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/category.php
     * @return void
     */
    public function testCanShowBlockWithoutProducts(): void
    {
        $this->prepareNavigationBlock('Category 1');
        $this->assertFalse($this->navigationBlock->canShowBlock());
        $this->assertCount(0, $this->navigationBlock->getLayer()->getProductCollection()->getItems());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/category_product.php
     * @return void
     */
    public function testCanShowBlockWithoutFilterOptions(): void
    {
        $this->prepareNavigationBlock('Category 1');
        $this->assertFalse($this->navigationBlock->canShowBlock());
        $this->assertCount(1, $this->navigationBlock->getLayer()->getProductCollection()->getItems());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/category_with_different_price_products.php
     * @dataProvider canShowBlockWithDisplayModeDataProvider
     * @param string $displayMode
     * @param bool $canShow
     * @return void
     */
    public function testCanShowBlockWithDisplayMode(string $displayMode, bool $canShow): void
    {
        $this->updateCategoryDisplayMode('Category 999', $displayMode);
        $this->prepareNavigationBlock('Category 999');
        $this->assertEquals($canShow, $this->navigationBlock->canShowBlock());
    }

    /**
     * @return array
     */
    public function canShowBlockWithDisplayModeDataProvider(): array
    {
        return [
            'with_mode_products' => ['mode' => CategoryModel::DM_PRODUCT, 'can_show' => true],
            'with_mode_cms_block' => ['mode' => CategoryModel::DM_PAGE, 'can_show' => false],
            'with_mode_cms_block_and_products' => ['mode' => CategoryModel::DM_MIXED, 'can_show' => true],
        ];
    }

    /**
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoDataFixture Magento/Catalog/_files/category_with_different_price_products.php
     * @dataProvider canShowBlockWithDisplayModeDataProviderOnStoreView
     * @param string $defaultMode
     * @param string $storeMode
     * @param bool $canShow
     * @return void
     */
    public function testCanShowBlockWithDisplayModeOnStoreView(
        string $defaultMode,
        string $storeMode,
        bool $canShow
    ): void {
        $secondStoreId = (int)$this->storeManager->getStore('fixture_second_store')->getId();
        $this->updateCategoryDisplayMode('Category 999', $defaultMode);
        $this->updateCategoryDisplayMode('Category 999', $storeMode, $secondStoreId);
        $this->prepareNavigationBlock('Category 999', $secondStoreId);
        $this->assertEquals($canShow, $this->navigationBlock->canShowBlock());
    }

    /**
     * @return array
     */
    public function canShowBlockWithDisplayModeDataProviderOnStoreView(): array
    {
        return [
            'with_mode_products' => [
                'default_mode' => CategoryModel::DM_PAGE,
                'store_mode' => CategoryModel::DM_PRODUCT,
                'can_show' => true,
            ],
            'with_mode_cms_block' => [
                'default_mode' => CategoryModel::DM_PRODUCT,
                'store_mode' => CategoryModel::DM_PAGE,
                'can_show' => false
            ],
            'with_mode_cms_block_and_products' => [
                'default_mode' => CategoryModel::DM_PAGE,
                'store_mode' => CategoryModel::DM_MIXED,
                'can_show' => true
            ],
        ];
    }

    /**
     * Updates category display mode.
     *
     * @param string $categoryName
     * @param string $displayMode
     * @param int $storeId
     * @return void
     */
    private function updateCategoryDisplayMode(
        string $categoryName,
        string $displayMode,
        int $storeId = Store::DEFAULT_STORE_ID
    ): void {
        $category = $this->loadCategory($categoryName, $storeId);
        $category->setData('display_mode', $displayMode);

        if ($category->dataHasChangedFor('display_mode')) {
            $this->categoryResource->save($category);
        }
    }
}
