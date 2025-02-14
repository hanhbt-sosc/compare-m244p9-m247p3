<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Swatches\Test\Unit\Block\Product\Renderer\Listing;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\ConfigurableProduct\Helper\Data;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Pricing\PriceInfo\Base;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Swatches\Block\Product\Renderer\Configurable;
use Magento\Swatches\Block\Product\Renderer\Listing\Configurable as ConfigurableRenderer;
use Magento\Swatches\Helper\Media;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ConfigurableTest extends TestCase
{
    /** @var Configurable */
    private $configurable;

    /** @var ArrayUtils|MockObject */
    private $arrayUtils;

    /** @var EncoderInterface|MockObject */
    private $jsonEncoder;

    /** @var Data|MockObject */
    private $helper;

    /** @var \Magento\Swatches\Helper\Data|MockObject */
    private $swatchHelper;

    /** @var Media|MockObject */
    private $swatchMediaHelper;

    /** @var Product|MockObject */
    private $catalogProduct;

    /** @var CurrentCustomer|MockObject */
    private $currentCustomer;

    /** @var PriceCurrencyInterface|MockObject */
    private $priceCurrency;

    /** @var ConfigurableAttributeData|MockObject */
    private $configurableAttributeData;

    /** @var \Magento\Catalog\Model\Product|MockObject */
    private $product;

    /** @var AbstractType|MockObject */
    private $typeInstance;

    /** @var ScopeConfigInterface|MockObject */
    private $scopeConfig;

    /** @var Image|MockObject */
    private $imageHelper;

    /** @var UrlBuilder|MockObject  */
    private $imageUrlBuilder;

    /** @var MockObject */
    private $variationPricesMock;

    protected function setUp(): void
    {
        $this->arrayUtils = $this->createMock(ArrayUtils::class);
        $this->jsonEncoder = $this->getMockForAbstractClass(EncoderInterface::class);
        $this->helper = $this->createMock(Data::class);
        $this->swatchHelper = $this->createMock(\Magento\Swatches\Helper\Data::class);
        $this->swatchMediaHelper = $this->createMock(Media::class);
        $this->catalogProduct = $this->createMock(Product::class);
        $this->currentCustomer = $this->createMock(CurrentCustomer::class);
        $this->priceCurrency = $this->getMockForAbstractClass(PriceCurrencyInterface::class);
        $this->configurableAttributeData = $this->createMock(
            ConfigurableAttributeData::class
        );
        $this->product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $this->typeInstance = $this->createMock(AbstractType::class);
        $this->scopeConfig = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->imageHelper = $this->createMock(Image::class);
        $this->imageUrlBuilder = $this->createMock(UrlBuilder::class);
        $this->variationPricesMock = $this->createMock(
            Prices::class
        );

        $deploymentConfig = $this->createPartialMock(
            DeploymentConfig::class,
            ['get']
        );

        $deploymentConfig->expects($this->any())
            ->method('get')
            ->with(ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY)
            ->willReturn('448198e08af35844a42d3c93c1ef4e03');

        $objectManagerHelper = new ObjectManager($this);
        $this->configurable = $objectManagerHelper->getObject(
            ConfigurableRenderer::class,
            [
                'scopeConfig' => $this->scopeConfig,
                'imageHelper' => $this->imageHelper,
                'imageUrlBuilder' => $this->imageUrlBuilder,
                'arrayUtils' => $this->arrayUtils,
                'jsonEncoder' => $this->jsonEncoder,
                'helper' => $this->helper,
                'swatchHelper' => $this->swatchHelper,
                'swatchMediaHelper' => $this->swatchMediaHelper,
                'catalogProduct' => $this->catalogProduct,
                'currentCustomer' => $this->currentCustomer,
                'priceCurrency' => $this->priceCurrency,
                'configurableAttributeData' => $this->configurableAttributeData,
                'data' => [],
                'variationPrices' => $this->variationPricesMock,
                'deploymentConfig' => $deploymentConfig,
            ]
        );
    }

    /**
     * @covers Magento\Swatches\Block\Product\Renderer\Listing\Configurable::getSwatchAttributesData
     */
    public function testGetJsonSwatchConfigWithoutSwatches()
    {
        $this->prepareGetJsonSwatchConfig();
        $this->configurable->setProduct($this->product);
        $this->swatchHelper->expects($this->once())->method('getSwatchAttributesAsArray')
            ->with($this->product)
            ->willReturn([]);
        $this->swatchHelper->expects($this->once())->method('getSwatchesByOptionsId')
            ->willReturn([]);
        $this->jsonEncoder->expects($this->once())->method('encode')->with([]);
        $this->configurable->getJsonSwatchConfig();
    }

    /**
     * @covers Magento\Swatches\Block\Product\Renderer\Listing\Configurable::getSwatchAttributesData
     */
    public function testGetJsonSwatchNotUsedInProductListing()
    {
        $this->prepareGetJsonSwatchConfig();
        $this->configurable->setProduct($this->product);
        $this->swatchHelper->expects($this->once())->method('getSwatchAttributesAsArray')
            ->with($this->product)
            ->willReturn(
                [
                    1 => [
                        'options' => [1 => 'testA', 3 => 'testB'],
                        'use_product_image_for_swatch' => true,
                        'used_in_product_listing' => false,
                        'attribute_code' => 'code',
                    ],
                ]
            );
        $this->swatchHelper->expects($this->once())->method('getSwatchesByOptionsId')
            ->willReturn([]);
        $this->jsonEncoder->expects($this->once())->method('encode')->with([]);
        $this->configurable->getJsonSwatchConfig();
    }

    /**
     * @covers Magento\Swatches\Block\Product\Renderer\Listing\Configurable::getSwatchAttributesData
     */
    public function testGetJsonSwatchUsedInProductListing()
    {
        $products = [
            1 => 'testA',
            3 => 'testB'
        ];
        $expected =
            [
                'type' => null,
                'value' => 'hello',
                'label' => $products[3]
            ];
        $this->prepareGetJsonSwatchConfig();
        $this->configurable->setProduct($this->product);
        $this->swatchHelper->expects($this->once())->method('getSwatchAttributesAsArray')
            ->with($this->product)
            ->willReturn(
                [
                    1 => [
                        'options' => $products,
                        'use_product_image_for_swatch' => true,
                        'used_in_product_listing' => true,
                        'attribute_code' => 'code',
                    ],
                ]
            );
        $this->swatchHelper->expects($this->once())->method('getSwatchesByOptionsId')
            ->with([1, 3])
            ->willReturn([3 => ['type' => $expected['type'], 'value' => $expected['value']]]);
        $this->jsonEncoder->expects($this->once())->method('encode');
        $this->configurable->getJsonSwatchConfig();
    }

    private function prepareGetJsonSwatchConfig()
    {
        $product1 = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product1->expects($this->any())->method('isSaleable')->willReturn(true);
        $product1->expects($this->atLeastOnce())->method('getStatus')->willReturn(Status::STATUS_ENABLED);
        $product1->expects($this->any())->method('getData')->with('code')->willReturn(1);

        $product2 = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product2->expects($this->any())->method('isSaleable')->willReturn(true);
        $product2->expects($this->atLeastOnce())->method('getStatus')->willReturn(Status::STATUS_ENABLED);
        $product2->expects($this->any())->method('getData')->with('code')->willReturn(3);

        $simpleProducts = [$product1, $product2];
        $configurableType = $this->createMock(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::class);
        $configurableType->expects($this->atLeastOnce())->method('getUsedProducts')->with($this->product, null)
            ->willReturn($simpleProducts);
        $this->product->expects($this->any())->method('getTypeInstance')->willReturn($configurableType);

        $productAttribute1 = $this->createMock(AbstractAttribute::class);
        $productAttribute1->expects($this->any())->method('getId')->willReturn(1);
        $productAttribute1->expects($this->any())->method('getAttributeCode')->willReturn('code');

        $attribute1 = $this->getMockBuilder(Attribute::class)
            ->addMethods(['getProductAttribute'])
            ->disableOriginalConstructor()
            ->getMock();
        $attribute1->expects($this->any())->method('getProductAttribute')->willReturn($productAttribute1);

        $this->helper->expects($this->any())->method('getAllowAttributes')->with($this->product)
            ->willReturn([$attribute1]);
    }

    public function testGetPricesJson()
    {
        $expectedPrices = [
            'oldPrice' => [
                'amount' => 10,
            ],
            'basePrice' => [
                'amount' => 15,
            ],
            'finalPrice' => [
                'amount' => 20,
            ],
        ];

        $priceInfoMock = $this->createMock(Base::class);
        $this->configurable->setProduct($this->product);
        $this->product->expects($this->once())->method('getPriceInfo')->willReturn($priceInfoMock);
        $this->variationPricesMock->expects($this->once())
            ->method('getFormattedPrices')
            ->with($priceInfoMock)
            ->willReturn($expectedPrices);

        $this->jsonEncoder->expects($this->once())->method('encode')->with($expectedPrices);
        $this->configurable->getPricesJson();
    }
}
