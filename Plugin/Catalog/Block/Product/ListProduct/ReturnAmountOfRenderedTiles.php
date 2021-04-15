<?php

namespace MageSuite\ProductTileWarmup\Plugin\Catalog\Block\Product\ListProduct;

class ReturnAmountOfRenderedTiles
{
    const TILE_WARMUP_FULL_ACTION_NAME = 'tile_warmup_index';

    /**
     * @var \Magento\Framework\App\Response\Http
     */
    protected $response;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    public function __construct(
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->response = $response;
        $this->request = $request;
    }

    public function afterGetLoadedProductCollection(\Magento\Catalog\Block\Product\ListProduct $subject, $result)
    {
        if ($this->request->getFullActionName() !== self::TILE_WARMUP_FULL_ACTION_NAME) {
            return $result;
        }

        $this->response->setHeader('X-Rendered-Tiles-Count', count($result->getItems()));

        return $result;
    }
}
