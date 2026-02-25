<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future.
 *
 * @author    Danilo Cavalcanti <danilo-cm@hotmail.com>
 */

declare(strict_types=1);

namespace Omnik\Core\Plugin\Magento\Catalog\Controller\Category;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\Session;
use Magento\Framework\App\DeploymentConfig;

class ViewPlugin
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var Session
     */
    private Session $catalogSession;

    /**
     * @var \Cm_Cache_Backend_Redis
     */
    private \Cm_Cache_Backend_Redis $redis;

    /**
     * @var DeploymentConfig
     */
    private DeploymentConfig $deploymentConfig;

    /**
     * @param RequestInterface $request
     * @param Session $catalogSession
     * @param DeploymentConfig $deploymentConfig
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     * @throws \Zend_Cache_Exception
     */
    public function __construct(
        RequestInterface $request,
        Session $catalogSession,
        DeploymentConfig $deploymentConfig
    ) {
        $this->request = $request;
        $this->catalogSession = $catalogSession;

        $options = $deploymentConfig->get('cache/frontend/default/backend_options');

        if (isset($options['remote_backend_options'])) {
            $options = $options['remote_backend_options'];
        }

        $this->redis = new \Cm_Cache_Backend_Redis($options);
    }

    /**
     * @param \Magento\Catalog\Controller\Category\View $subject
     * @return void
     * @throws \CredisException
     */
    public function beforeExecute(\Magento\Catalog\Controller\Category\View $subject): void
    {
        $key = 'curPage_' . $this->catalogSession->getSessionId();
        $this->redis->save($this->request->getParam('p') === null ? 1 : $this->request->getParam('p'), $key);
    }
}
