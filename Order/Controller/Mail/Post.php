<?php
namespace Hello\Order\Controller\Mail;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;

class Post extends \Magento\Framework\App\Action\Action
{
    protected $_inlineTranslation;
    protected $_scopeConfig;
    protected $_logLoggerInterface;
    protected $storeManager;
    protected $reSourceConnection;
    /**
     * @var TransportBuilder
     */
    private TransportBuilder $transportBuilder;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $loggerInterface,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        ResourceConnection $reSourceConnection,
        array $data = []
    )
    {
        $this->_resource = $resource;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_scopeConfig = $scopeConfig;
        $this->_logLoggerInterface = $loggerInterface;
        $this->messageManager = $context->getMessageManager();
        $this->storeManager = $storeManager;
        $this->reSourceConnection = $reSourceConnection;

        parent::__construct($context);
        $this->transportBuilder = $transportBuilder;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPost();
        $id = $post['order_id'];
        $msg = $post['msg'];

        $connection = $this->reSourceConnection->getConnection();

        $length = $connection->select()->from(
            'sales_order', 'entity_id');
        $result = $connection->fetchCol($length);
        $arrlength = count($result);
        $num = number_format($arrlength);

        $select = $connection->select()->from(
            'sales_order', 'customer_email'
        )->where(
            "sales_order.entity_id =" . $id
        );
        $result = $connection->fetchOne($select);

        if ($num >= $id) {
            try {
                $store = $this->storeManager->getStore();
                $templateParams = ['store' => $store, 'customer_name' => $msg
                ];

                $transport = $this->transportBuilder->setTemplateIdentifier(
                    'order_email_template'
                )->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )->addTo(
                    $result,
                    $msg
                )->setTemplateVars(
                    $templateParams
                )->setFrom(
                    'general'
                )->getTransport();

                $transport->sendMessage();
                echo "success";
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('order');
        return $redirect;
        }
        else {
            echo "wrong order id";
        }
    }
}
