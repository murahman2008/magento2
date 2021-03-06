<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magento\Catalog\Controller\Adminhtml\Product;


class BuilderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Magento\Catalog\Controller\Adminhtml\Product\Builder
     */
    protected $builder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $wysiwygConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productMock;

    protected function setUp()
    {
        $this->loggerMock = $this->getMock('Magento\Logger', array(), array(), '', false);
        $this->productFactoryMock
            = $this->getMock('Magento\Catalog\Model\ProductFactory', array('create'));
        $this->registryMock = $this->getMock('Magento\Registry', array(), array(), '', false);
        $this->wysiwygConfigMock
            = $this->getMock('Magento\Cms\Model\Wysiwyg\Config', array('setStoreId'), array(), '', false);
        $this->requestMock = $this->getMock('Magento\App\Request\Http', array(), array(), '', false);
        $methods = array('setStoreId', 'setData', 'load', '__wakeup', 'setAttributeSetId', 'setTypeId');
        $this->productMock = $this->getMock('Magento\Catalog\Model\Product', $methods, array(), '', false);

        $this->builder = new Builder(
            $this->productFactoryMock,
            $this->loggerMock,
            $this->registryMock,
            $this->wysiwygConfigMock
        );
    }

    public function testBuildWhenProductExistAndPossibleToLoadProduct()
    {
        $valueMap = array(
            array('id', null, 2),
            array('store', 0, 'some_store'),
            array('type', null, 'type_id'),
            array('set', null, 3),
            array('store', null, 'store')
        );
        $this->requestMock->expects($this->any())->method('getParam')->will($this->returnValueMap($valueMap));
        $this->productFactoryMock
            ->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->productMock));
        $this->productMock->expects($this->once())->method('setStoreId')->with('some_store')->will($this->returnSelf());
        $this->productMock->expects($this->never())->method('setTypeId');
        $this->productMock->expects($this->once())->method('load')->with(2)->will($this->returnSelf());
        $this->productMock->expects($this->once())->method('setAttributeSetId')->with(3)->will($this->returnSelf());
        $registryValueMap = array(
            array('product', $this->productMock, $this->registryMock),
            array('current_product', $this->productMock, $this->registryMock)
        );
        $this->registryMock->expects($this->any())->method('register')->will($this->returnValueMap($registryValueMap));
        $this->wysiwygConfigMock->expects($this->once())->method('setStoreId')->with('store');
        $this->assertEquals($this->productMock, $this->builder->build($this->requestMock));
    }

    public function testBuildWhenImpossibleLoadProduct()
    {
        $valueMap = array(
            array('id', null, 15),
            array('store', 0, 'some_store'),
            array('type', null, 'type_id'),
            array('set', null, 3),
            array('store', null, 'store')
        );
        $this->requestMock->expects($this->any())->method('getParam')->will($this->returnValueMap($valueMap));
        $this->productFactoryMock
            ->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->productMock));
        $this->productMock->expects($this->once())->method('setStoreId')->with('some_store')->will($this->returnSelf());
        $this->productMock->expects($this->once())
            ->method('setTypeId')
            ->with(\Magento\Catalog\Model\Product\Type::DEFAULT_TYPE)
            ->will($this->returnSelf());
        $this->productMock
            ->expects($this->once())
            ->method('load')
            ->with(15)
            ->will($this->throwException(new \Exception()));
        $this->loggerMock->expects($this->once())->method('logException');
        $this->productMock->expects($this->once())->method('setAttributeSetId')->with(3)->will($this->returnSelf());
        $registryValueMap = array(
            array('product', $this->productMock, $this->registryMock),
            array('current_product', $this->productMock, $this->registryMock)
        );
        $this->registryMock->expects($this->any())->method('register')->will($this->returnValueMap($registryValueMap));
        $this->wysiwygConfigMock->expects($this->once())->method('setStoreId')->with('store');
        $this->assertEquals($this->productMock, $this->builder->build($this->requestMock));
    }

    public function testBuildWhenProductNotExist()
    {
        $valueMap = array(
            array('id', null, null),
            array('store', 0, 'some_store'),
            array('type', null, 'type_id'),
            array('set', null, 3),
            array('store', null, 'store')
        );
        $this->requestMock->expects($this->any())->method('getParam')->will($this->returnValueMap($valueMap));
        $this->productFactoryMock
            ->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->productMock));
        $this->productMock->expects($this->once())->method('setStoreId')->with('some_store')->will($this->returnSelf());
        $productValueMap = array(
            array('type_id', $this->productMock),
            array(\Magento\Catalog\Model\Product\Type::DEFAULT_TYPE, $this->productMock)
        );
        $this->productMock->expects($this->any())->method('setTypeId')->will($this->returnValueMap($productValueMap));
        $this->productMock->expects($this->never())->method('load');
        $this->productMock->expects($this->once())->method('setAttributeSetId')->with(3)->will($this->returnSelf());
        $registryValueMap = array(
            array('product', $this->productMock, $this->registryMock),
            array('current_product', $this->productMock, $this->registryMock)
        );
        $this->registryMock->expects($this->any())->method('register')->will($this->returnValueMap($registryValueMap));
        $this->wysiwygConfigMock->expects($this->once())->method('setStoreId')->with('store');
        $this->assertEquals($this->productMock, $this->builder->build($this->requestMock));
    }

}
