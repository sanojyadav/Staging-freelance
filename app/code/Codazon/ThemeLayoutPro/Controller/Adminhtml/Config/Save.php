<?php
/**
 * Copyright © 2017 Codazon, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codazon\ThemeLayoutPro\Controller\Adminhtml\Config;

use Codazon\ThemeLayoutPro\Controller\Adminhtml\ConfigAbstract;

/**
 * System Configuration Save Controller
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends ConfigAbstract
{
    /**
     * Backend Config Model Factory
     *
     * @var \Magento\Config\Model\Config\Factory
     */
    protected $_configFactory;

    /**
     * @var \Magento\Framework\Cache\FrontendInterface
     */
    protected $_cache;

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;
    
    protected $_themeFactory;
    
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param \Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker $sectionChecker
     * @param \Magento\Config\Model\Config\Factory $configFactory
     * @param \Magento\Framework\Cache\FrontendInterface $cache
     * @param \Magento\Framework\Stdlib\StringUtils $string
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Codazon\ThemeLayoutPro\Model\Config\Structure $configStructure,
        \Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker $sectionChecker,
        \Codazon\ThemeLayoutPro\Model\Config\Factory $configFactory,
        \Magento\Framework\Cache\FrontendInterface $cache,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Codazon\ThemeLayoutPro\Model\DesignFactory $themeFactory,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context, $configStructure, $sectionChecker);
        $this->_configFactory = $configFactory;
        $this->_cache = $cache;
        $this->string = $string;
        $this->_themeFactory = $themeFactory;
        $this->_coreRegistry = $registry;
    }

    /**
     * Get groups for save
     *
     * @return array|null
     */
    protected function _getGroupsForSave()
    {
        $groups = $this->getRequest()->getPost('groups');
        $files = $this->getRequest()->getFiles('groups');

        if ($files && is_array($files)) {
            /**
             * Carefully merge $_FILES and $_POST information
             * None of '+=' or 'array_merge_recursive' can do this correct
             */
            foreach ($files as $groupName => $group) {
                $data = $this->_processNestedGroups($group);
                if (!empty($data)) {
                    if (!empty($groups[$groupName])) {
                        $groups[$groupName] = array_merge_recursive((array)$groups[$groupName], $data);
                    } else {
                        $groups[$groupName] = $data;
                    }
                }
            }
        }
        return $groups;
    }

    /**
     * Process nested groups
     *
     * @param mixed $group
     * @return array
     */
    protected function _processNestedGroups($group)
    {
        $data = [];

        if (isset($group['fields']) && is_array($group['fields'])) {
            foreach ($group['fields'] as $fieldName => $field) {
                if (!empty($field['value'])) {
                    $data['fields'][$fieldName] = ['value' => $field['value']];
                }
            }
        }

        if (isset($group['groups']) && is_array($group['groups'])) {
            foreach ($group['groups'] as $groupName => $groupData) {
                $nestedGroup = $this->_processNestedGroups($groupData);
                if (!empty($nestedGroup)) {
                    $data['groups'][$groupName] = $nestedGroup;
                }
            }
        }

        return $data;
    }

    /**
     * Custom save logic for section
     *
     * @return void
     */
    protected function _saveSection()
    {
        $method = '_save' . $this->string->upperCaseWords($this->getRequest()->getParam('section'), '_', '');
        if (method_exists($this, $method)) {
            $this->{$method}();
        }
    }

    /**
     * Advanced save procedure
     *
     * @return void
     */
    protected function _saveAdvanced()
    {
        $this->_cache->clean();
    }

    /**
     * Save configuration
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        try {
            // custom save logic
            $this->_saveSection();
            $section = $this->getRequest()->getParam('section');
            $website = $this->getRequest()->getParam('website');
            $store = $this->getRequest()->getParam('store');
            $themeId = $this->getRequest()->getParam('theme_id');
            if (!$themeId) {
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath(
                    'themelayoutpro/config/index',
                    [
                        '_current' => ['website', 'store'],
                        '_nosid' => true
                    ]
                );
            }
            
            $currentTheme = $this->_themeFactory->create()->load($themeId);
            $this->_coreRegistry->register('current_theme', $currentTheme);

            $configData = [
                'section'   => $section,
                'website'   => $website,
                'store'     => $store,
                'groups'    => $this->_getGroupsForSave(),
                'theme_id'  => $themeId
            ];
           
            /** @var \Magento\Config\Model\Config $configModel  */
            $configModel = $this->_configFactory->create(['data' => $configData]);
            $configModel->save();

            $this->messageManager->addSuccess(__('You saved the configuration.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $messages = explode("\n", $e->getMessage());
            foreach ($messages as $message) {
                $this->messageManager->addError($message);
            }
        } catch (\Exception $e) {
            $this->messageManager->addException(
                $e,
                __('Something went wrong while saving this configuration:') . ' ' . $e->getMessage()
            );
        }

        $this->_saveState($this->getRequest()->getPost('config_state'));
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath(
            'themelayoutpro/config/edit',
            [
                '_current' => ['website', 'store', 'theme_id', 'section'],
                '_nosid' => true
            ]
        );
    }
}
